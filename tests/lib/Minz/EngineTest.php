<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class EngineTest extends TestCase
{
    public function testRun()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/rabbits';

        $router = new \Minz\Router();
        $router->addRoute('/rabbits', 'rabbits#items', 'get');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request();

        $response = $engine->run($request);

        $this->assertSame(200, $response->code());
        $this->assertSame('rabbits/items.phtml', $response->viewFilename());
    }

    public function testRunReturnsErrorIfRouteNotFound()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/not-found';

        $router = new \Minz\Router();
        $router->addRoute('/rabbits', 'rabbits#items', 'get');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request();

        $response = $engine->run($request);

        $this->assertSame(404, $response->code());
        $this->assertSame('errors/not_found.phtml', $response->viewFilename());
    }

    public function testRunReturnsErrorIfControllerFileIsMissing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/rabbits';

        $router = new \Minz\Router();
        $router->addRoute('/rabbits', 'missing#items', 'get');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request();

        $response = $engine->run($request);

        $this->assertSame(500, $response->code());
        $this->assertSame(
            'errors/internal_server_error.phtml',
            $response->viewFilename()
        );
    }

    public function testRunReturnsErrorIfActionIsMissing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/rabbits';

        $router = new \Minz\Router();
        $router->addRoute('/rabbits', 'rabbits#missing', 'get');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request();

        $response = $engine->run($request);

        $this->assertSame(500, $response->code());
        $this->assertSame(
            'errors/internal_server_error.phtml',
            $response->viewFilename()
        );
    }

    public function testRunReturnsErrorIfViewFileIsMissing()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/rabbits';

        $router = new \Minz\Router();
        $router->addRoute('/rabbits', 'rabbits#missingViewFile', 'get');
        $engine = new \Minz\Engine($router);
        $request = new \Minz\Request();

        $response = $engine->run($request);

        $this->assertSame(500, $response->code());
        $this->assertSame(
            'errors/internal_server_error.phtml',
            $response->viewFilename()
        );
    }
}
