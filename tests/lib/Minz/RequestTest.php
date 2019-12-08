<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
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
