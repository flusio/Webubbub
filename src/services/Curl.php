<?php

namespace Webubbub\services;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Curl
{
    /** @var self|callable|null */
    private static mixed $mock = null;

    /**
     * @param array<int, mixed> $options
     */
    public static function get(string $url, array $options = []): self
    {
        if (self::$mock) {
            $mock = self::$mock;
            if (is_callable($mock)) {
                $mock = $mock($url, $options);
            }

            if (isset($options[CURLOPT_HTTPHEADER]) && is_array($options[CURLOPT_HTTPHEADER])) {
                $mock->setReceivedHeaders($options[CURLOPT_HTTPHEADER]);
            }

            return $mock;
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
        curl_setopt(
            $curl_session,
            CURLOPT_HEADERFUNCTION,
            function ($curl, $header) use (&$headers): int {
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

        if ($result === true) {
            $result = '';
        }

        return new self($result, $http_code, $headers);
    }

    /**
     * @param string|array<string, mixed> $post_fields
     * @param array<int, mixed> $options
     */
    public static function post(string $url, mixed $post_fields, array $options = []): self
    {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = $post_fields;
        return self::get($url, $options);
    }

    /**
     * @param array<string, string[]> $headers
     */
    public static function mock(string $content = '', int $http_code = 200, array $headers = []): self
    {
        self::$mock = new self($content, $http_code, $headers);
        return self::$mock;
    }

    public static function mockCallback(callable $mock): void
    {
        self::$mock = $mock;
    }

    public static function resetMock(): void
    {
        self::$mock = null;
    }

    public int $http_code;

    public string $content;

    /** @var array<string, string[]> */
    public array $headers;

    /** @var array<string, string> */
    public array $received_headers;

    /**
     * @param array<string, string[]> $headers
     */
    public function __construct(string $content, int $http_code, array $headers)
    {
        $this->content = $content;
        $this->http_code = $http_code;
        $this->headers = $headers;
    }

    /**
     * @param string[] $headers
     */
    public function setReceivedHeaders(array $headers): void
    {
        foreach ($headers as $header) {
            $header_exploded = explode(':', $header, 2);
            if (count($header_exploded) < 2) {
                continue;
            }

            $header_key = trim($header_exploded[0]);
            $header_value = trim($header_exploded[1]);
            $this->received_headers[$header_key] = $header_value;
        }
    }
}
