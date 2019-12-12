<?php

namespace Minz\Tests;

use PHPUnit\Framework\TestCase;

class ActionControllerTestCase extends TestCase
{
    /**
     * Load the controller code, based on the called class.
     *
     * It should be called in `setUpBeforeClass`.
     *
     * @throws LogicException if the controller file cannot be included
     *
     * @return void
     */
    public static function includeController()
    {
        $function = new \ReflectionClass(get_called_class());
        $class = $function->getShortName();
        $class_without_test = strtolower(substr($class, 0, -4));
        $included = \Minz\ActionController::loadControllerCode($class_without_test);
        if (!$included) {
            throw new \LogicException(
                "{$class_without_test} controller file cannot be loaded."
            );
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
        $this->assertSame($code, $response->code());
        if ($output !== null) {
            $this->assertSame($output, $response->render());
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
