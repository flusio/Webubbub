<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class EngineTest extends TestCase
{
    public function testRun()
    {
        $router = new \Minz\Router();
        $router->addRoute('get', '/rabbits', 'rabbits#items');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request('GET', '/rabbits');

        $response = $engine->run($request);

        $output = $response->render();
        $this->assertSame(200, $response->code());
        $this->assertStringContainsString("<h1>The rabbits</h1>\n", $output);
        $this->assertStringContainsString("Bugs", $output);
        $this->assertStringContainsString("Clémentine", $output);
        $this->assertStringContainsString("Jean-Jean", $output);
    }

    public function testRunReturnsErrorIfRouteNotFound()
    {
        $router = new \Minz\Router();
        $router->addRoute('get', '/rabbits', 'rabbits#items');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request('GET', '/not-found');

        $response = $engine->run($request);

        $this->assertSame(404, $response->code());
        $this->assertSame('not_found.phtml', $response->output()->pointer());
    }

    public function testRunReturnsErrorIfControllerFileIsMissing()
    {
        $router = new \Minz\Router();
        $router->addRoute('get', '/rabbits', 'missing#items');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request('GET', '/rabbits');

        $response = $engine->run($request);

        $this->assertSame(500, $response->code());
        $this->assertSame('internal_server_error.phtml', $response->output()->pointer());
    }

    public function testRunReturnsErrorIfActionIsMissing()
    {
        $router = new \Minz\Router();
        $router->addRoute('get', '/rabbits', 'rabbits#missing');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request('GET', '/rabbits');

        $response = $engine->run($request);

        $this->assertSame(500, $response->code());
        $this->assertSame('internal_server_error.phtml', $response->output()->pointer());
    }

    public function testRunReturnsErrorIfViewFileIsMissing()
    {
        $router = new \Minz\Router();
        $router->addRoute('get', '/rabbits', 'rabbits#missingViewFile');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request('GET', '/rabbits');

        $response = $engine->run($request);

        $this->assertSame(500, $response->code());
        $this->assertSame('internal_server_error.phtml', $response->output()->pointer());
    }
}
