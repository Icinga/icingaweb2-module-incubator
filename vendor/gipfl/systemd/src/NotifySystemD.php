<?php

namespace gipfl\SystemD;

use Exception;
use InvalidArgumentException;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use RuntimeException;

class NotifySystemD
{
    /** @var LoopInterface */
    protected $loop;

    /** @var TimerInterface */
    protected $timer;

    /** @var resource */
    protected $socket;

    /** @var string|null */
    protected $path;

    /** @var string|null */
    protected $status;

    /** @var float */
    protected $interval;

    /** @var bool */
    protected $reloading = false;

    /** @var bool */
    protected $ready = false;

    /** @var bool */
    protected $failed = false;

    /** @var string|null The Invocation ID (128bit UUID) if available */
    protected $invocationId;

    /**
     * NotifySystemD constructor.
     * @param string $notifySocket
     * @param int $intervalSecs
     */
    public function __construct($notifySocket, $intervalSecs = 1)
    {
        $this->interval = $intervalSecs;
        if (@\file_exists($notifySocket) && \is_writable($notifySocket)) {
            $this->path = $notifySocket;
        } else {
            throw new RuntimeException("Unix Socket '$notifySocket' is not writable");
        }

        $this->connectToSocket();
    }

    /**
     * Starts sending WatchDog pings
     *
     * @param LoopInterface $loop
     */
    public function run(LoopInterface $loop)
    {
        $this->loop = $loop;
        $ping = function () {
            try {
                $this->pingWatchDog();
            } catch (Exception $e) {
                printf(
                    "<%d>Failed to ping systemd watchdog: %s\n",
                    LOG_ERR,
                    $e->getMessage()
                );
            }
        };
        $loop->futureTick($ping);
        $this->timer = $loop->addPeriodicTimer($this->interval, $ping);

        return $this;
    }

    /**
     * Stop sending watchdog notifications
     *
     * Usually there is no need to do so, and the destructor does this anyways
     */
    public function stop()
    {
        if ($this->timer !== null) {
            $this->loop->cancelTimer($this->timer);
        }
    }

    public static function ifRequired(LoopInterface $loop, $env = null)
    {
        if ($env === null) {
            $env = $_SERVER;
        }

        if (! isset($env['NOTIFY_SOCKET'])) {
            return false;
        }

        if (isset($env['WATCHDOG_USEC'])) {
            $interval = $env['WATCHDOG_USEC'] / 2000000; // Half of that time
        } else {
            $interval = 1;
        }

        return (new static($env['NOTIFY_SOCKET'], $interval))
            ->eventuallySetInvocationIdFromEnv($env)
            ->run($loop);
    }

    /**
     * Extend the Watchdog timeout
     *
     * Useful to inform systemd before slow startup/shutdown operations. This
     * is available since systemd v236. Older versions silently ignore this.
     *
     * @param float $seconds
     */
    public function extendTimeout($seconds)
    {
        $this->send(['EXTEND_TIMEOUT_USEC' => (int) $seconds * 1000000]);
    }

    /**
     * Send a notification to the systemd watchdog
     */
    public function pingWatchDog()
    {
        $this->send(['WATCHDOG' => '1']);
    }

    /**
     * Set the (visible) service status
     *
     * @param $status
     */
    public function setStatus($status)
    {
        if ($status !== $this->status) {
            $this->status = $status;
            $this->send(['STATUS' => $status]);
        }
    }

    public function setReloading($status = null)
    {
        $this->ready = false;
        $this->status = $status;
        $params = ['RELOADING' => '1'];
        if ($status !== null) {
            $params['STATUS'] = $status;
        }
        $this->send($params);
    }

    public function setError($error, $status = null)
    {
        $this->ready = false;
        $this->status = $status;
        if ($error instanceof Exception) {
            $errNo = $error->getCode();
            $status = $status ?: $error->getMessage();
        } elseif (\is_int($error)) {
            $errNo = $error;
        } else {
            throw new InvalidArgumentException(
                'Error has to be an Exception or an Integer'
            );
        }
        $params = [];
        if ($status !== null) {
            $params['STATUS'] = $status;
            $this->status = $status;
        }

        $params = ['ERRNO' => (string) $errNo];
        $this->send($params);
        $this->failed = true;
    }

    public function setReady($status = null)
    {
        $this->ready = true;
        $params = [
            'READY'   => '1',
            'MAINPID' => \posix_getpid(),
        ];

        if ($status !== null) {
            $params['STATUS'] = $status;
            $this->status = $status;
        }

        $this->send($params);
    }

    /**
     * Returns a 128bit uuid (16 hex characters) Invocation ID if available,
     * null in case there isn't
     *
     * @return string|null
     */
    public function getInvocationId()
    {
        return $this->invocationId;
    }

    /**
     * Whether we got an Invocation ID
     *
     * @return bool
     */
    public function hasInvocationId()
    {
        return $this->invocationId !== null;
    }

    /**
     * Get the path to the the systemd notification socket
     *
     * Usually /run/systemd/notify or similar - or null if not available
     *
     * @return string|null
     */
    public function getSocketPath()
    {
        return $this->path;
    }

    /**
     * Our Watchdog interval in seconds
     *
     * This is a float value: half of WATCHDOG_USEC if given - otherwise 1 second
     *
     * @return float
     */
    public function getWatchdogInterval()
    {
        return $this->interval;
    }

    /**
     * Send custom parameters to systemd
     *
     * This is for internal use only, but might be used to test new functionality
     *
     * @param array $params
     * @internal
     */
    public function send(array $params)
    {
        if ($this->failed) {
            throw new RuntimeException('Cannot notify SystemD after failing');
        }
        $message = $this->buildMessage($params);
        $length = \strlen($message);
        $result = @socket_send($this->socket, $message, $length, 0);
        if ($result === false) {
            $error = \socket_last_error($this->socket);

            throw new RuntimeException(
                "Failed to send to SystemD: " . \socket_strerror($error),
                $error
            );
        }
        if ($result !== $length) {
            throw new RuntimeException(
                "Wanted to send $length Bytes to SystemD, only $result have been sent"
            );
        }
    }

    /**
     * Transforms a key/value array into a string like "key1=val1\nkey2=val2"
     *
     * @param array $params
     * @return string
     */
    protected function buildMessage(array $params)
    {
        $message = '';
        foreach ($params as $key => $value) {
            $message .= "$key=$value\n";
        }

        return $message;
    }

    /**
     * Connect to the discovered socket
     *
     * Will be /run/systemd/notify or similar. No async logic, as this
     * shouldn't block. If systemd blocks we're dead anyways, so who cares
     */
    protected function connectToSocket()
    {
        $path = $this->path;
        $socket = @\socket_create(AF_UNIX, SOCK_DGRAM, 0);
        if ($socket === false) {
            throw new RuntimeException('Unable to create socket');
        }

        if (! @\socket_connect($socket, $path)) {
            $error = \socket_last_error($socket);

            throw new RuntimeException(
                "Unable to connect to unix domain socket $path: " . \socket_strerror($error),
                $error
            );
        }

        $this->socket = $socket;
    }

    /**
     * If INVOCATION_ID is available in the given ENV array: keep it
     *
     * Fails in case we do not get an 128bit string
     *
     * @param array $env
     * @return $this
     */
    protected function eventuallySetInvocationIdFromEnv(array $env)
    {
        $key = 'INVOCATION_ID';
        if (isset($env[$key])) {
            if (\strlen($env[$key]) === 32) {
                $this->invocationId = $env[$key];
            } else {
                throw new RuntimeException(sprintf(
                    'Unsupported %s="%s"',
                    $key,
                    $env['$key']
                ));
            }
        }

        return $this;
    }

    public function __destruct()
    {
        if (\is_resource($this->socket)) {
            @\socket_close($this->socket);
        }
        $this->stop();

        unset($this->loop);
    }
}
