<?php

namespace Webubbub\services;

class Curl
{
    private static $mock;

    public static function get($url, $options = [])
    {
        if (self::$mock) {
            return self::$mock;
        }

        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $url);
        curl_setopt($curl_session, CURLOPT_HEADER, false);
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_session, CURLOPT_TIMEOUT, 5);
        foreach ($options as $option_key => $option_value) {
            curl_setopt($curl_session, $option_key, $option_value);
        }

        // @see https://stackoverflow.com/a/41135574
        $headers = [];
        curl_setopt($curl_session, CURLOPT_HEADERFUNCTION,
            function($curl, $header) use (&$headers)
            {
                $len = strlen($header);
                $header_exploded = explode(':', $header, 2);
                if (count($header_exploded) < 2) {
                    return $len;
                }

                $header_key = strtolower(trim($header_exploded[0]));
                $header_value = trim($header_exploded[1]);
                if (!isset($headers[$header_key])) {
                    $headers[$header_key] = [];
                }
                $headers[$header_key][] = $header_value;

                return $len;
            }
        );

        $result = curl_exec($curl_session);
        $http_code = curl_getinfo($curl_session, CURLINFO_RESPONSE_CODE);

        if ($result === false) {
            $result = '';
            $error = curl_error($curl_session);
            \Minz\Log::error("Curl error: {$error}.");
        }

        curl_close($curl_session);

        return new self($result, $http_code, $headers);
    }

    public static function mock($content = '', $http_code = 200, $headers = [])
    {
        self::$mock = new self($content, $http_code, $headers);
    }

    public static function resetMock()
    {
        self::$mock = null;
    }

    /** @var integer */
    public $http_code;

    /** @var string */
    public $content;

    /** @var array */
    public $headers;

    public function __construct($content, $http_code, $headers)
    {
        $this->content = $content;
        $this->http_code = $http_code;
        $this->headers = $headers;
    }
}
