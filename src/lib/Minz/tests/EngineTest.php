<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class EngineTest extends TestCase
{
    public function testRun()
    {
        $router = new \Minz\Router();
        $router->addRoute('/rabbits', 'rabbits#items', 'get');
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
        $router->addRoute('/rabbits', 'rabbits#items', 'get');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request('GET', '/not-found');

        $response = $engine->run($request);

        $this->assertSame(404, $response->code());
        $this->assertSame('errors#not_found.phtml', $response->viewPointer());
    }

    public function testRunReturnsErrorIfControllerFileIsMissing()
    {
        $router = new \Minz\Router();
        $router->addRoute('/rabbits', 'missing#items', 'get');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request('GET', '/rabbits');

        $response = $engine->run($request);

        $this->assertSame(500, $response->code());
        $this->assertSame(
            'errors#internal_server_error.phtml',
            $response->viewPointer()
        );
    }

    public function testRunReturnsErrorIfActionIsMissing()
    {
        $router = new \Minz\Router();
        $router->addRoute('/rabbits', 'rabbits#missing', 'get');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request('GET', '/rabbits');

        $response = $engine->run($request);

        $this->assertSame(500, $response->code());
        $this->assertSame(
            'errors#internal_server_error.phtml',
            $response->viewPointer()
        );
    }

    public function testRunReturnsErrorIfViewFileIsMissing()
    {
        $router = new \Minz\Router();
        $router->addRoute('/rabbits', 'rabbits#missingViewFile', 'get');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request('GET', '/rabbits');

        $response = $engine->run($request);

        $this->assertSame(500, $response->code());
        $this->assertSame(
            'errors#internal_server_error.phtml',
            $response->viewPointer()
        );
    }
}
