<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testSetViewFilename()
    {
        $response = new Response();

        $response->setViewFilename('rabbits/items.phtml');

        $this->assertSame('rabbits/items.phtml', $response->viewFilename());
    }

    public function testSetViewFilenameFailsIfViewFileDoesntExist()
    {
        $this->expectException(Errors\ResponseError::class);
        $this->expectExceptionMessage(
            'tests/fixtures/views/rabbits/missing.phtml file cannot be found.'
        );

        $response = new Response();

        $response->setViewFilename('rabbits/missing.phtml');
    }

    public function testSetCode()
    {
        $response = new Response();

        $response->setCode(404);

        $this->assertSame(404, $response->code());
    }

    public function testSetCodeFailsIfCodeIsInvalid()
    {
        $this->expectException(Errors\ResponseError::class);
        $this->expectExceptionMessage('666 is not a valid HTTP code.');

        $response = new Response();

        $response->setCode(666);
    }

    public function testSetHeader()
    {
        $response = new Response();

        $response->setHeader('Content-Type', 'application/xml');

        $headers = $response->headers();
        $this->assertSame([
            'Content-Type' => 'application/xml',
        ], $headers);
    }

    public function testOk()
    {
        $response = Response::ok('rabbits/items.phtml');

        $this->assertSame(200, $response->code());
        $this->assertSame(['Content-Type' => 'text/html'], $response->headers());
        $this->assertSame('rabbits/items.phtml', $response->viewFilename());
    }

    public function testNotFound()
    {
        $response = Response::notFound();

        $this->assertSame(404, $response->code());
        $this->assertSame(['Content-Type' => 'text/html'], $response->headers());
        $this->assertSame('errors/not_found.phtml', $response->viewFilename());
    }

    public function testInternalServerError()
    {
        $response = Response::internalServerError();

        $this->assertSame(500, $response->code());
        $this->assertSame(['Content-Type' => 'text/html'], $response->headers());
        $this->assertSame('errors/internal_server_error.phtml', $response->viewFilename());
    }

    public function testOutput()
    {
        $response = Response::ok('rabbits/items.phtml');

        $output = $response->render();

        $this->assertSame("<h1>The rabbits</h1>\n", $output);
    }
}
