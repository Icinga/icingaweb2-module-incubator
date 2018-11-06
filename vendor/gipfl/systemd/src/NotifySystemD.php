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

    /** @var string */
    protected $path;

    /** @var string|null */
    protected $status;

    /** @var int */
    protected $interval;

    /** @var bool */
    protected $reloading = false;

    /** @var bool */
    protected $ready = false;

    /** @var bool */
    protected $failed = false;

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
        $this->timer = $loop->addPeriodicTimer($this->interval, function () {
            try {
                $this->pingWatchDog();
            } catch (Exception $e) {
                // Silently ignore errors? What else should we do?
            }
        });
    }

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

        $notifier = new static($env['NOTIFY_SOCKET'], $interval);
        $notifier->run($loop);

        return $notifier;
    }

    public function pingWatchDog()
    {
        $this->send(['WATCHDOG' => '1']);
    }

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

    protected function send(array $params)
    {
        if ($this->failed) {
            throw new RuntimeException('Cannot notify SystemD after failing');
        }
        $message = $this->buildMessage($params);
        $length = strlen($message);
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

    protected function buildMessage(array $params)
    {
        $message = '';
        foreach ($params as $key => $value) {
            $message .= "$key=$value\n";
        }

        return $message;
    }

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

    public function __destruct()
    {
        if (\is_resource($this->socket)) {
            @\socket_close($this->socket);
        }
        $this->stop();

        unset($this->loop);
    }
}
