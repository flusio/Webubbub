<?php

namespace Minz\Tests;

use PHPUnit\Framework\TestCase;

class IntegrationTestCase extends TestCase
{
    protected static $application;

    /** @var string */
    protected static $schema;

    /** @var array */
    protected static $factories;

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
        $app_path = \Minz\Configuration::$app_path;
        $schema_path = $app_path . '/src/schema.sql';
        if (file_exists($schema_path)) {
            self::$schema = file_get_contents($schema_path);
        }
    }

    /**
     * @beforeClass
     */
    public static function loadFactories()
    {
        $factories = DatabaseFactory::factories();
        foreach ($factories as $factory_name => $_) {
            self::$factories[$factory_name] = new DatabaseFactory($factory_name);
        }
    }

    /**
     * @before
     */
    public function initDatabase()
    {
        if (self::$schema) {
            $database = \Minz\Database::get();
            $database->exec(self::$schema);
        }
    }

    /**
     * @after
     */
    public function dropDatabase()
    {
        \Minz\Database::drop();
    }

    /**
     * @after
     */
    public function resetSession()
    {
        if (\Minz\Configuration::$use_session) {
            session_unset();
        }
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
            $response_headers = $response->headers(true);
            foreach ($headers as $header => $value) {
                $this->assertArrayHasKey($header, $response_headers);
                $this->assertSame($value, $response_headers[$header]);
            }
        }
    }
}
