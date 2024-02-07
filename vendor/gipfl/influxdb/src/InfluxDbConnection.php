<?php

namespace gipfl\InfluxDb;

interface InfluxDbConnection
{
    public function ping($verbose = false);

    public function getVersion();

    public function listDatabases();

    public function createDatabase($name);

    public function getHealth();

    /**
     * @param string $dbName
     * @param DataPoint[] $dataPoints
     * @param string|null $precision ns,u,ms,s,m,h
     * @return \React\Promise\Promise
     */
    public function writeDataPoints($dbName, array $dataPoints, $precision = null);
}
