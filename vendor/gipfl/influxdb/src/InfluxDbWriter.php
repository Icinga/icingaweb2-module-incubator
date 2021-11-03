<?php

namespace gipfl\InfluxDb;

use gipfl\Curl\CurlAsync;
use function http_build_query;
use function implode;
use function rtrim;

class InfluxDbWriter
{
    /** @var string */
    protected $baseUrl;
    /**
     * @var CurlAsync
     */
    protected $curl;

    /**
     * AsyncInfluxDbWriter constructor.
     * @param $baseUrl string InfluxDB base URL
     */
    public function __construct($baseUrl, CurlAsync $curl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->curl = $curl;
    }

    protected function url($path, $params = [])
    {
        $url = $this->baseUrl . "/$path";
        if (! empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * @param string $dbName
     * @param DataPoint[] $dataPoints
     * @return \React\Promise\ExtendedPromiseInterface
     */
    public function send($dbName, array $dataPoints)
    {
        return $this->curl->post($this->url('write', ['db' => $dbName]), [
            'User-Agent' => 'Icinga-vSphereDB/1.0'
        ], implode($dataPoints));
    }
}
