<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function setUp(): void
    {
        $_SERVER['REQUEST_METHOD'] = '';
        $_SERVER['REQUEST_URI'] = '';
        $_GET = [];
        $_POST = [];
    }

    public function testMethod()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $request = new Request();

        $method = $request->method();

        $this->assertSame('get', $method);
    }

    /**
     * @dataProvider requestToPathProvider
     */
    public function testPath($requestUri, $expectedPath)
    {
        $_SERVER['REQUEST_URI'] = $requestUri;
        $request = new Request();

        $path = $request->path();

        $this->assertSame($expectedPath, $path);
    }

    public function testParamWithGET()
    {
        $_GET['foo'] = 'bar';
        $request = new Request();

        $foo = $request->param('foo');

        $this->assertSame('bar', $foo);
    }

    public function testParamWithPOST()
    {
        $_POST['foo'] = 'bar';
        $request = new Request();

        $foo = $request->param('foo');

        $this->assertSame('bar', $foo);
    }

    public function testParamWithPOSTTakingPrecedenceOnGET()
    {
        $_GET['foo'] = 'bar';
        $_POST['foo'] = 'baz';
        $request = new Request();

        $foo = $request->param('foo');

        $this->assertSame('baz', $foo);
    }

    public function testParamWithDefaultValue()
    {
        $request = new Request();

        $foo = $request->param('foo', 'bar');

        $this->assertSame('bar', $foo);
    }

    public function requestToPathProvider()
    {
        return [
            ['/', '/'],
            ['/rabbits', '/rabbits'],
            ['/rabbits/details.html', '/rabbits/details.html'],
            ['/rabbits?id=42', '/rabbits'],
        ];
    }
}
