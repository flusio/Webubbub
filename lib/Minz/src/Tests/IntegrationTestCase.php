<?php

namespace Minz\Tests;

use PHPUnit\Framework\TestCase;

class IntegrationTestCase extends TestCase
{
    protected static $application;

    /** @var string */
    protected static $schema;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        $app_name = \Minz\Configuration::$app_name;
        $application_class_name = "\\{$app_name}\\Application";
        self::$application = new $application_class_name();
    }

    /**
     * @beforeClass
     */
    public static function loadSchema()
    {
        $configuration_path = \Minz\Configuration::$configuration_path;
        self::$schema = file_get_contents($configuration_path . '/schema.sql');
    }

    /**
     * @before
     */
    public function initDatabase()
    {
        $database = \Minz\Database::get();
        $database->exec(self::$schema);
    }

    /**
     * @after
     */
    public function dropDatabase()
    {
        \Minz\Database::drop();
    }

    /**
     * Assert that a Response is matching the given conditions.
     *
     * @param \Minz\Response $response
     * @param integer $code The HTTP code that the response must match with
     * @param string $output The rendered output that the response must match
     *                       with (optional)
     * @param string[] $headers The headers that the response must contain (optional)
     */
    public function assertResponse($response, $code, $output = null, $headers = null)
    {
        $response_output = $response->render();
        $this->assertSame($code, $response->code(), 'Output is: ' . $response_output);
        if ($output !== null) {
            $this->assertSame($output, $response_output);
        }
        if ($headers !== null) {
            // I would use assertArraySubset, but it's deprecated in PHPUnit 8
            // and will be removed in PHPUnit 9.
            $response_headers = $response->headers();
            foreach ($headers as $header => $value) {
                $this->assertArrayHasKey($header, $response_headers);
                $this->assertSame($value, $response_headers[$header]);
            }
        }
    }
}
