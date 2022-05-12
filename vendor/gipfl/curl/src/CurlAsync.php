<?php

namespace gipfl\Curl;

use Exception;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use RuntimeException;
use Throwable;
use function array_shift;
use function count;
use function curl_close;
use function curl_error;
use function curl_multi_add_handle;
use function curl_multi_close;
use function curl_multi_exec;
use function curl_multi_getcontent;
use function curl_multi_info_read;
use function curl_multi_init;
use function curl_multi_remove_handle;
use function curl_multi_select;
use function curl_multi_setopt;
use function curl_multi_strerror;

/**
 * This class provides an async CURL abstraction layer fitting into a ReactPHP
 * reality, implemented based on curl_multi.
 *
 * As long as there are requests pending, a timer fires
 *
 */
class CurlAsync
{
    const DEFAULT_POLLING_INTERVAL = 0.03;

    /** @var false|resource */
    protected $handle;

    /** @var Deferred[] resourceIdx => Deferred */
    protected $running = [];

    /** @var [ [0 => resourceIdx, 1 => Deferred], ... ] */
    protected $pending = [];

    /** @var array[] resourceIdx => options */
    protected $pendingOptions = [];

    /** @var RequestInterface[] resourceIdx => RequestInterface */
    protected $pendingRequests = [];

    /** @var array resourceIdx => resource */
    protected $curl = [];

    /** @var int */
    protected $maxParallelRequests = 30;

    /** @var LoopInterface */
    protected $loop;

    /** @var float */
    protected $fastInterval = self::DEFAULT_POLLING_INTERVAL;

    /** @var TimerInterface */
    protected $fastTimer;

    /**
     * @param LoopInterface $loop
     */
    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
        $this->handle = curl_multi_init();
        // Hint: I had no specific reason to disable pipelining, nothing but
        // the desire to ease debugging. So, in case you feel confident you might
        // want to remove this line
        curl_multi_setopt($this->handle, CURLMOPT_PIPELINING, 0);
        if (! $this->handle) {
            throw new RuntimeException('Failed to initialize curl_multi');
        }
    }

    public function get($url, $headers = [], $curlOptions = [])
    {
        return $this->send(new Request('GET', $url, $headers), $curlOptions);
    }

    public function post($url, $headers = [], $body = null, $curlOptions = [])
    {
        return $this->send(new Request('POST', $url, $headers, $body), $curlOptions);
    }

    public function head($url, $headers = [])
    {
        return $this->send(new Request('HEAD', $url, $headers));
    }

    public function send(RequestInterface $request, $curlOptions = [])
    {
        $curl = CurlHandle::createForRequest($request, $curlOptions);
        $idx = (int) $curl;
        $this->curl[$idx] = $curl;
        $this->pendingOptions[$idx] = $curlOptions;
        $this->pendingRequests[$idx] = $request;
        $deferred = new Deferred(function () use ($idx) {
            $this->freeByResourceReference($idx);
        });
        $this->pending[] = [$idx, $deferred];
        $this->loop->futureTick(function () {
            $this->enablePolling();
            $this->enqueueNextRequestIfAny();
        });

        return $deferred->promise();
    }

    /**
     * @param int $max
     * @return $this
     */
    public function setMaxParallelRequests($max)
    {
        $this->maxParallelRequests = (int) $max;

        return $this;
    }

    public function getPendingCurlHandles()
    {
        return $this->curl;
    }

    protected function enqueueNextRequestIfAny()
    {
        while (count($this->pending) > 0 && count($this->running) < $this->maxParallelRequests) {
            $next = array_shift($this->pending);
            $resourceIdx = $next[0];
            $this->running[$resourceIdx] = $next[1];
            $curl = $this->curl[$resourceIdx];
            // enqueued: curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
            curl_multi_add_handle($this->handle, $curl);
        }
    }

    public function rejectAllPendingRequests($reasonOrError = null)
    {
        $this->rejectAllRunningRequests($reasonOrError);
    }

    protected function rejectAllRunningRequests($reasonOrError = null)
    {
        $running = $this->running; // Hint: intentionally cloned
        foreach ($running as $resourceNum => $deferred) {
            $this->freeByResourceReference($resourceNum);
            $deferred->reject($reasonOrError);
        }
        if (! empty($this->running)) {
            throw new RuntimeException(
            // Hint: should never be reached
                'All running requests should have been removed, but something has been left'
            );
        }
    }

    protected function rejectAllDeferredRequests($reasonOrError = null)
    {
        foreach ($this->pending as $pending) {
            list($resourceNum, $deferred) = $pending;
            $this->freeByResourceReference($resourceNum);
            $deferred->reject($reasonOrError);
        }

        if (! empty($this->running)) {
            throw new RuntimeException(
            // Hint: should never be reached
                'All pending requests should have been removed, but something has been left'
            );
        }
    }

    /**
     * Returns true in case at least one request completed
     *
     * @return bool
     */
    protected function checkForResults()
    {
        if (empty($this->running)) {
            return false;
        } else {
            $handle = $this->handle;
            do {
                $status = curl_multi_exec($handle, $active);
            } while ($status > 0);
            // Hint: while ($status === CURLM_CALL_MULTI_PERFORM) ?

            if ($status !== CURLM_OK) {
                throw new RuntimeException(curl_multi_strerror($handle));
            }
            if ($active) {
                $fds = curl_multi_select($handle, 0.01);
                // We take no action here, we'll info_read anyways:
                // $fds === -1 ->  select failed, returning. Probably only happens when running out of FDs
                // $fds === 0  ->  Nothing to do
                // TODO: figure how often we get here -> https://bugs.php.net/bug.php?id=61141
            }
            $gotResult = false;
            while (false !== ($completed = curl_multi_info_read($handle))) {
                $this->requestCompleted($handle, $completed);
                if (empty($this->pending) && empty($this->running)) {
                    $this->disablePolling();
                }
                $gotResult = true;
            }

            return $gotResult;
        }
    }

    protected function requestCompleted($handle, $completed)
    {
        $curl = $completed['handle'];
        $resourceNum = (int) $curl; // Hint this is an object in PHP >= 8, a resource in older versions
        $deferred = $this->running[$resourceNum];
        $request = $this->pendingRequests[$resourceNum];
        $options = $this->pendingOptions[$resourceNum];
        $content = curl_multi_getcontent($curl);
        curl_multi_remove_handle($handle, $curl);
        $removeProxyHeaders = isset($options[CURLOPT_PROXYTYPE])
            && $options[CURLOPT_PROXYTYPE] === CURLPROXY_HTTP
            // We assume that CURLOPT_SUPPRESS_CONNECT_HEADERS has been set for the request
            && !defined('CURLOPT_SUPPRESS_CONNECT_HEADERS');

        if ($completed['result'] === CURLE_OK) {
            $this->freeByResourceReference($resourceNum);
            try {
                $deferred->resolve($this->parseResponse($content, $removeProxyHeaders));
            } catch (\Exception $e) {
                $deferred->reject(new ResponseParseError($e->getMessage(), $request, null, $e->getCode(), $e));
            }
        } else {
            try {
                $deferred->resolve($this->parseResponse($content, $removeProxyHeaders));
            } catch (\Exception $e) {
                $response = null;
            }
            try {
                $error = curl_error($curl);
                if ($error === '') {
                    $error = 'Curl failed, but got no CURL error';
                }
            } catch (Throwable $e) {
                $error = 'Unable to determine CURL error: ' . $e->getMessage();
            } catch (Exception $e) {
                $error = 'Unable to determine CURL error: ' . $e->getMessage();
            }
            $deferred->reject(new RequestError($error, $request, $response));
            $this->freeByResourceReference($resourceNum);
        }
    }

    protected function parseResponse($content, $stripProxyHeaders)
    {
        // This method can be removed once we support PHP 7.3+ only, as it
        // has CURLOPT_SUPPRESS_CONNECT_HEADERS
        $response = Message::parseResponse($content);
        if ($stripProxyHeaders) {
            $body = (string) $response->getBody();
            if (preg_match('/^HTTP\/.*? [0-9]{3}[^\n]*\r?\n/s', $body)) {
                // There is no such header on reused connections
                $response = Message::parseResponse($body);
            }
        }

        return $response;
    }

    /**
     * Set the polling interval used while requests are pending. Defaults to
     * self::DEFAULT_POLLING_INTERVAL
     *
     * @param float $interval
     */
    public function setInterval($interval)
    {
        if ($interval !== $this->fastInterval) {
            $this->fastInterval = $interval;
            $this->reEnableTimerIfActive();
        }
    }

    protected function reEnableTimerIfActive()
    {
        if ($this->fastTimer !== null) {
            $this->disablePolling();
            $this->enablePolling();
        }
    }

    /**
     * Polling timer should be active only while requests are pending
     */
    protected function enablePolling()
    {
        if ($this->fastTimer === null) {
            $this->fastTimer = $this->loop->addPeriodicTimer($this->fastInterval, function () {
                if ($this->checkForResults()) {
                    $this->enqueueNextRequestIfAny();
                }
            });
        }
    }

    protected function disablePolling()
    {
        if ($this->fastTimer) {
            $this->loop->cancelTimer($this->fastTimer);
            $this->fastTimer = null;
        }
    }

    protected function freeByResourceReference($ref)
    {
        unset($this->pendingRequests[$ref]);
        unset($this->pendingOptions[$ref]);
        unset($this->running[$ref]);
        curl_close($this->curl[$ref]);
        unset($this->curl[$ref]);
    }

    public function __destruct()
    {
        curl_multi_close($this->handle);
    }
}
