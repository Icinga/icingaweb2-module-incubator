<?php

namespace gipfl\Curl;

use Psr\Http\Message\RequestInterface;

class CurlHandle
{
    protected static $curlOptions = [
        CURLOPT_HEADER         => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_ENCODING       => 'gzip',
        CURLOPT_TCP_NODELAY    => true,
        CURLINFO_HEADER_OUT    => true,
        CURLOPT_TCP_KEEPALIVE  => 1,
        CURLOPT_BUFFERSIZE     => 512 * 1024,
    ];

    public static function createForRequest(RequestInterface $request, $curlOptions = [])
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = "$name: $value";
            }
        }
        $body = $request->getBody();
        if ($body->getSize() > 0) {
            $body = $body->getContents();
        } else {
            $body = null;
        }


        $curl = curl_init();
        $opts = static::prepareCurlOptions(
            $request->getMethod(),
            (string) $request->getUri(),
            $body,
            $headers,
            $curlOptions
        );
        curl_setopt_array($curl, $opts);

        return $curl;
    }

    protected static function prepareCurlOptions($method, $url, $body = null, $headers = [], $curlOptions = [])
    {
        $opts = $curlOptions + [
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_URL            => $url,
            ] + self::$curlOptions;

        if (isset($opts[CURLOPT_HTTPHEADER])) {
            $opts[CURLOPT_HTTPHEADER] = array_merge($opts[CURLOPT_HTTPHEADER], $headers);
        } else {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }

        return $opts;
    }
}
