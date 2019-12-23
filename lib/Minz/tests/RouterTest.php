<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    public function testListRoutes()
    {
        $router = new Router();

        $routes = $router->routes();

        $this->assertSame(0, count($routes));
    }

    public function testAddRoute()
    {
        $router = new Router();

        $router->addRoute('/rabbits', 'rabbits#list', 'get');

        $routes = $router->routes();
        $this->assertSame([
            'get' => [
                '/rabbits' => 'rabbits#list',
            ],
        ], $routes);
    }

    public function testAddRouteWithSeveralVias()
    {
        $router = new Router();

        $router->addRoute('/rabbits', 'rabbits#list', ['get', 'post']);

        $routes = $router->routes();
        $this->assertSame([
            'get' => [
                '/rabbits' => 'rabbits#list',
            ],
            'post' => [
                '/rabbits' => 'rabbits#list',
            ],
        ], $routes);
    }

    public function testAddRouteAcceptsCliVia()
    {
        $router = new Router();

        $router->addRoute('/rabbits', 'rabbits#list', 'cli');

        $routes = $router->routes();
        $this->assertSame([
            'cli' => [
                '/rabbits' => 'rabbits#list',
            ],
        ], $routes);
    }

    /**
     * @dataProvider emptyValuesProvider
     */
    public function testAddRouteFailsIfPathIsEmpty($emptyPath)
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Route "pattern" cannot be empty.');

        $router = new Router();

        $router->addRoute($emptyPath, 'rabbits#list', 'get');
    }

    public function testAddRouteFailsIfPathDoesntStartWithSlash()
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Route "pattern" must start by a slash (/).');

        $router = new Router();

        $router->addRoute('rabbits', 'rabbits#list', 'get');
    }

    /**
     * @dataProvider emptyValuesProvider
     */
    public function testAddRouteFailsIfToIsEmpty($emptyTo)
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Route "action_pointer" cannot be empty.');

        $router = new Router();

        $router->addRoute('/rabbits', $emptyTo, 'get');
    }

    public function testAddRouteFailsIfToDoesntContainHash()
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Route "action_pointer" must contain a hash (#).');

        $router = new Router();

        $router->addRoute('/rabbits', 'rabbits_list', 'get');
    }

    public function testAddRouteFailsIfToContainsMoreThanOneHash()
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            'Route "action_pointer" must contain at most one hash (#).'
        );

        $router = new Router();

        $router->addRoute('/rabbits', 'rabbits#list#more', 'get');
    }

    /**
     * @dataProvider emptyValuesProvider
     */
    public function testAddRouteFailsIfViaIsEmpty($emptyVia)
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Route "via" cannot be empty.');

        $router = new Router();

        $router->addRoute('/rabbits', 'rabbits#list', $emptyVia);
    }

    /**
     * @dataProvider invalidViaProvider
     */
    public function testAddRouteFailsIfViaIsInvalid($invalidVia)
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            "{$invalidVia} via is invalid (get, post, patch, put, delete, cli)."
        );

        $router = new Router();

        $router->addRoute('/rabbits', 'rabbits#list', $invalidVia);
    }

    public function testAddRouteFailsIfContainsInvalidVia()
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            "invalid via is invalid (get, post, patch, put, delete, cli)."
        );

        $router = new Router();

        $router->addRoute('/rabbits', 'rabbits#list', ['get', 'invalid']);
    }

    public function testMatch()
    {
        $router = new Router();
        $router->addRoute('/rabbits', 'rabbits#list', 'get');

        $action_pointer = $router->match('get', '/rabbits');

        $this->assertSame('rabbits#list', $action_pointer);
    }

    public function testMatchWithParam()
    {
        $router = new Router();
        $router->addRoute('/rabbits/:id', 'rabbits#get', 'get');

        $action_pointer = $router->match('get', '/rabbits/42');

        $this->assertSame('rabbits#get', $action_pointer);
    }

    public function testMatchFailsIfNotMatchingVia()
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage('Path "post /rabbits" doesn’t match any route.');

        $router = new Router();
        $router->addRoute('/rabbits', 'rabbits#list', 'get');

        $router->match('post', '/rabbits');
    }

    public function testMatchFailsIfIncorrectPath()
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage('Path "get /no-rabbits" doesn’t match any route.');

        $router = new Router();
        $router->addRoute('/rabbits', 'rabbits#list', 'get');

        $router->match('get', '/no-rabbits');
    }

    public function testMatchWithParamFailsIfIncorrectPath()
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage('Path "get /rabbits/42/details" doesn’t match any route.');

        $router = new Router();
        $router->addRoute('/rabbits/:id', 'rabbits#get', 'get');

        $router->match('get', '/rabbits/42/details');
    }

    /**
     * @dataProvider invalidViaProvider
     */
    public function testMatchFailsIfViaIsInvalid($invalidVia)
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            "{$invalidVia} via is invalid (get, post, patch, put, delete, cli)."
        );

        $router = new Router();
        $router->addRoute('/rabbits', 'rabbits#list', 'get');

        $router->match($invalidVia, '/rabbits');
    }

    public function testUriFor()
    {
        $router = new Router();
        $router->addRoute('/rabbits', 'rabbits#list', 'get');

        $uri = $router->uriFor('get', 'rabbits#list');

        $this->assertSame('/rabbits', $uri);
    }

    public function testUriForWithParams()
    {
        $router = new Router();
        $router->addRoute('/rabbits/:id', 'rabbits#details', 'get');

        $uri = $router->uriFor('get', 'rabbits#details', ['id' => 42]);

        $this->assertSame('/rabbits/42', $uri);
    }

    public function testUriWithAdditionalParameters()
    {
        $router = new Router();
        $router->addRoute('/rabbits', 'rabbits#details', 'get');

        $uri = $router->uriFor('get', 'rabbits#details', ['id' => 42]);

        $this->assertSame('/rabbits?id=42', $uri);
    }

    public function testUriForWithUrlOptionPath()
    {
        Configuration::$url_options['path'] = '/path';
        $router = new Router();
        $router->addRoute('/rabbits', 'rabbits#list', 'get');

        $uri = $router->uriFor('get', 'rabbits#list');

        $this->assertSame('/path/rabbits', $uri);

        Configuration::$url_options['path'] = '';
    }

    public function testUriForFailsIfParameterIsMissing()
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage('Required `id` parameter is missing.');

        $router = new Router();
        $router->addRoute('/rabbits/:id', 'rabbits#details', 'get');

        $uri = $router->uriFor('get', 'rabbits#details');
    }

    public function testUriForFailsIfActionPointerNotRegistered()
    {
        $this->expectException(Errors\RouteNotFoundError::class);
        $this->expectExceptionMessage(
            'Action pointer "get rabbits#list" doesn’t match any route.'
        );

        $router = new Router();

        $router->uriFor('get', 'rabbits#list');
    }

    /**
     * @dataProvider invalidViaProvider
     */
    public function testUriForFailsIfViaIsInvalid($invalid_via)
    {
        $this->expectException(Errors\RoutingError::class);
        $this->expectExceptionMessage(
            "{$invalid_via} via is invalid (get, post, patch, put, delete, cli)."
        );

        $router = new Router();
        $router->addRoute('/rabbits', 'rabbits#list', 'get');

        $router->uriFor($invalid_via, 'rabbits#list');
    }

    public function emptyValuesProvider()
    {
        return [
            [''],
            [null],
            [false],
            [[]],
        ];
    }

    public function invalidViaProvider()
    {
        return [
            ['invalid'],
            ['postpost'],
            [' get'],
        ];
    }
}
