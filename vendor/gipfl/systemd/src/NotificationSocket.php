<?php

namespace gipfl\SystemD;

use RuntimeException;
use function file_exists;
use function is_writable;

class NotificationSocket
{
    /** @var resource */
    protected $socket;

    /** @var string */
    protected $path;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        if (@file_exists($path) && is_writable($path)) {
            $this->path = $path;
        } else {
            throw new RuntimeException("Unix Socket '$path' is not writable");
        }

        $this->connect();
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
     * Get the path to the the systemd notification socket
     *
     * Usually /run/systemd/notify or similar
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
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
     * shouldn't block. If systemd blocks we're dead anyway, so who cares
     */
    protected function connect()
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
     * Disconnect the socket if connected
     */
    public function disconnect()
    {
        if (\is_resource($this->socket)) {
            @\socket_close($this->socket);
            $this->socket = null;
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
