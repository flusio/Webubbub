<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testSetViewPointer()
    {
        $response = new Response(200, '');

        $response->setViewPointer('rabbits/items.phtml');

        $this->assertSame('rabbits/items.phtml', $response->viewPointer());
    }

    public function testSetViewPointerCanBeEmpty()
    {
        $response = new Response(200, '');

        $response->setViewPointer('');

        $this->assertSame('', $response->viewPointer());
    }

    public function testSetViewPointerFailsIfViewFileDoesntExist()
    {
        $this->expectException(Errors\ResponseError::class);
        $this->expectExceptionMessage(
            'src/views/rabbits/missing.phtml file cannot be found.'
        );

        $response = new Response(200, '');

        $response->setViewPointer('rabbits/missing.phtml');
    }

    public function testSetCode()
    {
        $response = new Response(200, '');

        $response->setCode(404);

        $this->assertSame(404, $response->code());
    }

    public function testSetCodeFailsIfCodeIsInvalid()
    {
        $this->expectException(Errors\ResponseError::class);
        $this->expectExceptionMessage('666 is not a valid HTTP code.');

        $response = new Response(200, '');

        $response->setCode(666);
    }

    public function testSetHeader()
    {
        $response = new Response(200, '');

        $response->setHeader('Content-Type', 'application/xml');

        $headers = $response->headers();
        $this->assertSame([
            'Content-Type' => 'application/xml',
        ], $headers);
    }

    public function testConstructor()
    {
        $response = new Response(200, 'rabbits/items.phtml');

        $this->assertSame(200, $response->code());
        $this->assertSame(['Content-Type' => 'text/html'], $response->headers());
        $this->assertSame('rabbits/items.phtml', $response->viewPointer());
    }

    public function testConstructorAdaptsTheContentTypeFromFileType()
    {
        $response = new Response(200, 'rabbits/items.txt');

        $this->assertSame(['Content-Type' => 'text/plain'], $response->headers());
    }

    public function testConstructorAcceptsEmptyViewPointer()
    {
        $response = new Response(200, '');

        $this->assertSame(200, $response->code());
        $this->assertSame(['Content-Type' => 'text/plain'], $response->headers());
        $this->assertSame('', $response->viewPointer());
    }

    public function testConstructorFailsIfInvalidCode()
    {
        $this->expectException(Errors\ResponseError::class);
        $this->expectExceptionMessage('666 is not a valid HTTP code.');

        $response = new Response(666, 'rabbits/items.phtml');
    }

    public function testConstructorFailsIfViewFileDoesntExist()
    {
        $this->expectException(Errors\ResponseError::class);
        $this->expectExceptionMessage('src/views/missing.phtml file cannot be found.');

        $response = new Response(200, 'missing.phtml');
    }

    public function testConstructorFailsIfViewFileExtensionIsntSupported()
    {
        $this->expectException(Errors\ResponseError::class);
        $this->expectExceptionMessage(
            'nope is not a supported view file extension.'
        );

        $response = new Response(200, 'rabbits/items.nope');
    }

    public function testOk()
    {
        $response = Response::ok('rabbits/items.phtml');

        $this->assertSame(200, $response->code());
    }

    public function testAccepted()
    {
        $response = Response::accepted('rabbits/items.phtml');

        $this->assertSame(202, $response->code());
    }

    public function testBadRequest()
    {
        $response = Response::badRequest();

        $this->assertSame(400, $response->code());
    }

    public function testNotFound()
    {
        $response = Response::notFound();

        $this->assertSame(404, $response->code());
    }

    public function testInternalServerError()
    {
        $response = Response::internalServerError();

        $this->assertSame(500, $response->code());
    }

    public function testRender()
    {
        $rabbits = [
            'Bugs',
            'Clémentine',
            'Jean-Jean',
        ];
        $response = Response::ok('rabbits/items.phtml', [
            'rabbits' => $rabbits,
        ]);

        $output = $response->render();

        $this->assertStringContainsString("<h1>The rabbits</h1>\n", $output);
        $this->assertStringContainsString("Bugs", $output);
        $this->assertStringContainsString("Clémentine", $output);
        $this->assertStringContainsString("Jean-Jean", $output);
    }

    public function testRenderWithEmptyViewPointer()
    {
        $response = Response::ok('');

        $output = $response->render();

        $this->assertSame('', $output);
    }
}
