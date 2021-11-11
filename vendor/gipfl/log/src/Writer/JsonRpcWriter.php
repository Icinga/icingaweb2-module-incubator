<?php

namespace gipfl\Log\Writer;

use gipfl\Log\LogWriterWithContext;
use gipfl\Protocol\JsonRpc\Connection;
use function iconv;
use function microtime;

class JsonRpcWriter implements LogWriterWithContext
{
    const DEFAULT_RPC_METHOD = 'logger.log';

    /** @var Connection */
    protected $connection;

    /** @var string */
    protected $method = self::DEFAULT_RPC_METHOD;

    /** @var array */
    protected $defaultContext;

    /**
     * JsonRpcWriter constructor.
     * @param Connection $connection
     */
    public function __construct(Connection $connection, $defaultContext = [])
    {
        $this->connection = $connection;
        $this->defaultContext = $defaultContext;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function write($level, $message, $context = [])
    {
        $message = iconv('UTF-8', 'UTF-8//IGNORE', $message);
        $this->connection->notification($this->method, $this->defaultContext + [
            'level'     => $level,
            'timestamp' => microtime(true),
            'message'   => $message,
            'context'   => $context,
        ]);
    }
}
