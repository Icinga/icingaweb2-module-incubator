<?php

namespace gipfl\InfluxDb;

use gipfl\Curl\CurlAsync;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use RuntimeException;

abstract class InfluxDbConnectionFactory
{
    /**
     * AsyncInfluxDbWriter constructor.
     * @param LoopInterface $loop
     * @param $baseUrl string InfluxDB base URL
     * @param string|null $username
     * @param string|null $password
     * @return Promise <InfluxDbConnection>
     */
    public static function create(CurlAsync $curl, $baseUrl, $username = null, $password = null)
    {
        $v1 = new InfluxDbConnectionV1($curl, $baseUrl);
        return $v1->getVersion()->then(function ($version) use ($baseUrl, $username, $password, $curl, $v1) {
            if ($version === null || preg_match('/^v?2\./', $version)) {
                $v2 = new InfluxDbConnectionV2($curl, $baseUrl, $username, $password);
                return $v2->getVersion()->then(function ($version) use ($v2) {
                    if ($version === null) {
                        throw new RuntimeException('Unable to detect InfluxDb version');
                    } else {
                        return $v2;
                    }
                });
            } else {
                return $v1;
            }
        });
    }
}
