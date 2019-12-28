<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    private $default_url_options;

    public function setUp(): void
    {
        $this->default_url_options = Configuration::$url_options;
    }

    public function tearDown(): void
    {
        Configuration::$url_options = $this->default_url_options;
        Url::setRouter(null);
    }

    public function testFor()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');
        Url::setRouter($router);

        $url = Url::for('rabbits#list');

        $this->assertSame('/rabbits', $url);
    }

    /**
     * @dataProvider viaProvider
     */
    public function testForWithAnyVia($via)
    {
        $router = new Router();
        $router->addRoute($via, '/rabbits', 'rabbits#list');
        Url::setRouter($router);

        $url = Url::for('rabbits#list');

        $this->assertSame('/rabbits', $url);
    }

    public function testForWithParams()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits/:id', 'rabbits#list');
        Url::setRouter($router);

        $url = Url::for('rabbits#list', ['id' => 42]);

        $this->assertSame('/rabbits/42', $url);
    }

    public function testForWithAdditionalParams()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');
        Url::setRouter($router);

        $url = Url::for('rabbits#list', ['id' => 42]);

        $this->assertSame('/rabbits?id=42', $url);
    }

    public function testForWithUrlOptionsPath()
    {
        Configuration::$url_options['path'] = '/path';

        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');
        Url::setRouter($router);

        $url = Url::for('rabbits#list');

        $this->assertSame('/path/rabbits', $url);

        Configuration::$url_options['path'] = '';
    }

    public function testForFailsIfRouterIsNotRegistered()
    {
        $this->expectException(Errors\UrlError::class);
        $this->expectExceptionMessage(
            'You must set a Router to the Url class before using it.'
        );

        Url::for('rabbits#list');
    }

    public function testForFailsIfActionPointerDoesNotExist()
    {
        $this->expectException(Errors\UrlError::class);
        $this->expectExceptionMessage(
            'rabbits#list action pointer does not exist in the router.'
        );

        $router = new Router();
        Url::setRouter($router);

        Url::for('rabbits#list');
    }

    public function testForWithParamsFailsIfParameterIsMissing()
    {
        $this->expectException(Errors\UrlError::class);
        $this->expectExceptionMessage('Required `id` parameter is missing.');

        $router = new Router();
        $router->addRoute('get', '/rabbits/:id', 'rabbits#list');
        Url::setRouter($router);

        Url::for('rabbits#list');
    }

    public function testAbsoluteFor()
    {
        Configuration::$url_options['host'] = 'my-domain.com';

        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');
        Url::setRouter($router);

        $url = Url::absoluteFor('rabbits#list');

        $this->assertSame('http://my-domain.com/rabbits', $url);
    }

    /**
     * @dataProvider defaultPortProvider
     */
    public function testAbsoluteForWithDefaultPort($protocol, $port)
    {
        Configuration::$url_options['host'] = 'my-domain.com';
        Configuration::$url_options['port'] = $port;
        Configuration::$url_options['protocol'] = $protocol;

        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');
        Url::setRouter($router);

        $url = Url::absoluteFor('rabbits#list');

        $this->assertSame($protocol . '://my-domain.com/rabbits', $url);
    }

    public function testAbsoluteForWithCustomPort()
    {
        Configuration::$url_options['host'] = 'my-domain.com';
        Configuration::$url_options['port'] = 8080;

        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');
        Url::setRouter($router);

        $url = Url::absoluteFor('rabbits#list');

        $this->assertSame('http://my-domain.com:8080/rabbits', $url);
    }

    public function testAbsoluteForWithPath()
    {
        Configuration::$url_options['host'] = 'my-domain.com';
        Configuration::$url_options['path'] = '/path';

        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#list');
        Url::setRouter($router);

        $url = Url::absoluteFor('rabbits#list');

        $this->assertSame('http://my-domain.com/path/rabbits', $url);
    }

    public function viaProvider()
    {
        return [
            ['get'],
            ['post'],
            ['patch'],
            ['put'],
            ['delete'],
            ['cli'],
        ];
    }

    public function defaultPortProvider()
    {
        return [
            ['http', 80],
            ['https', 443],
        ];
    }
}
