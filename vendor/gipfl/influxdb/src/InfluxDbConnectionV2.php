<?php

namespace gipfl\InfluxDb;

use gipfl\Curl\CurlAsync;
use gipfl\Json\JsonString;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;

class InfluxDbConnectionV2 implements InfluxDbConnection
{
    const API_VERSION = 'v2';

    const USER_AGENT = 'gipfl-InfluxDB/0.5';

    /** @var CurlAsync */
    protected $curl;

    /** @var string */
    protected $baseUrl;

    /** @var string|null */
    protected $token;

    /** @var string|null */
    protected $org;

    /**
     * AsyncInfluxDbWriter constructor.
     * @param $baseUrl string InfluxDB base URL
     * @param LoopInterface $loop
     */
    public function __construct(CurlAsync $curl, $baseUrl, $org, $token)
    {
        $this->baseUrl = $baseUrl;
        $this->setOrg($org);
        $this->setToken($token);
        $this->curl = $curl;
    }

    /**
     * @param string|null $token
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @param string|null $org
     * @return $this
     */
    public function setOrg($org)
    {
        $this->org = $org;

        return $this;
    }

    public function ping($verbose = false)
    {
        $params = [];
        if ($verbose) {
            $params['verbose'] = 'true';
        }
        return $this->getUrl('ping', $params);
    }

    public function getVersion()
    {
        return $this->getHealth()->then(function ($result) {
            return $result->version;
        });
    }

    public function getMyOrgId()
    {
        return $this->getUrl('api/v2/orgs', ['org' => urlencode($this->org)])->then(function ($result) {
            foreach ($result->orgs as $org) {
                if ($org->name === $this->org) {
                    return $org->id;
                }
            }

            throw new \RuntimeException('Org "' . $this->org . '" not found');
        });
    }

    public function listDatabases()
    {
        // ->links->self = "/api/v2/buckets?descending=false\u0026limit=2\u0026offset=0"
        // ->links->next = "next": "/api/v2/buckets?descending=false\u0026limit=2\u0026offset=2"
        // 100 -> maxlimit
        return $this->getUrl('api/v2/buckets', ['limit' => 100])->then(function ($result) {
            $list = [];
            foreach ($result->buckets as $bucket) {
                $list[] = $bucket->name;
            }

            return $list;
        });
    }

    public function createDatabase($name)
    {
        return $this->getMyOrgId()->then(function ($orgId) use ($name) {
            return $this->postUrl('api/v2/buckets', [
                'orgID' => $orgId,
                'name'  => $name,
                'retentionRules' => [(object) [
                    'type' => 'expire',
                    'everySeconds' => 86400 * 7,
                ]]
            ]);
        })->then(function ($result) {
            return $result;
        });
    }

    public function getHealth()
    {
        // Works without Auth
        return $this->getUrl('health');
    }

    /**
     * TODO: unfinished
     * @param $query
     * @throws \gipfl\Json\JsonEncodeException
     */
    protected function query($query)
    {
        $prefix = "api/v2/";
        $headers = ['Content-Type' => 'application/json'];
        $body = JsonString::encode(['query' => $query]);
        $urlParams = ['org' => $this->org];
    }

    /**
     * @param string $dbName
     * @param DataPoint[] $dataPoints
     * @param string|null $precision ns,u,ms,s,m,h
     * @return \React\Promise\Promise
     */
    public function writeDataPoints($dbName, array $dataPoints, $precision = null)
    {
        $body = gzencode(\implode($dataPoints), 6);
        $params = [
            'org'    => $this->org,
            'bucket' => $dbName,
            // TODO: Figure out, whether 2.0.0 also supports bucket. If so, drop db
            'db'     => $dbName,
        ];
        $headers = [
            'X-Request-Id'     => Uuid::uuid4()->toString(),
            'Content-Encoding' => 'gzip',
            'Content-Length'   => strlen($body),
        ];
        if ($precision !== null) {
            $params['precision'] = $precision;
        }
        // params['rp'] = $retentionPolicy
        return $this->curl->post(
            $this->url('api/v2/write', $params),
            $this->defaultHeaders() + $headers,
            $body,
            $this->getDefaultCurlOptions()
        );
    }

    protected function getDefaultCurlOptions()
    {
        return [
            // Hint: avoid 100/Continue
            CURLOPT_HTTPHEADER => [
                'Expect:',
            ]
        ];
    }

    protected function defaultHeaders()
    {
        $headers = [
            'User-Agent' => static::USER_AGENT,
        ];
        if ($this->token) {
            $headers['Authorization'] = 'Token ' . $this->token;
        }

        return $headers;
    }

    protected function get($url, $params = null)
    {
        return $this->curl->get(
            $this->url($url, $params),
            $this->defaultHeaders()
        );
    }

    protected function getRaw($url, $params = null)
    {
        /** @var Promise $promise */
        $promise = $this
            ->get($url, $params)
            ->then(function (ResponseInterface $response) {
                if ($response->getStatusCode() < 300) {
                    return (string) $response->getBody();
                } else {
                    try {
                        $body = JsonString::decode($response->getBody());
                    } catch (\Exception $e) {
                        throw new \Exception($response->getReasonPhrase());
                    }
                    if (isset($body->message)) {
                        throw new \Exception($body->message);
                    } else {
                        throw new \Exception($response->getReasonPhrase());
                    }
                }
            });

        return $promise;
    }

    protected function postRaw($url, $body, $headers = [], $urlParams = [])
    {
        /** @var Promise $promise */
        $promise = $this->curl->post(
            $this->url($url, $urlParams),
            $this->defaultHeaders() + $headers + [
                'Content-Type' => 'application/json'
            ],
            $body
        )->then(function (ResponseInterface $response) {
            return (string) $response->getBody();
        });

        return $promise;
    }

    protected function getUrl($url, $params = null)
    {
        return $this->getRaw($url, $params)->then(function ($raw) {
            return JsonString::decode((string) $raw);
        });
    }

    protected function postUrl($url, $body, $headers = [], $urlParams = [])
    {
        return $this->postRaw($url, JsonString::encode($body), $headers, $urlParams)->then(function ($raw) {
            return JsonString::decode((string) $raw);
        });
    }

    protected function url($path, $params = [])
    {
        $url = $this->baseUrl . "/$path";
        if (! empty($params)) {
            $url .= '?' . \http_build_query($params);
        }

        return $url;
    }
}
