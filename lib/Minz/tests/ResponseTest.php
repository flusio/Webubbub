<?php

namespace Minz;

use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
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
        $view = new Output\View('rabbits/items.phtml');
        $response = new Response(200, $view);

        $this->assertSame(200, $response->code());
        $this->assertSame(['Content-Type' => 'text/html'], $response->headers());
    }

    public function testConstructorAdaptsTheContentTypeFromView()
    {
        $view = new Output\View('rabbits/items.txt');
        $response = new Response(200, $view);

        $this->assertSame(['Content-Type' => 'text/plain'], $response->headers());
    }

    public function testConstructorAcceptsNoViews()
    {
        $response = new Response(200, null);

        $this->assertSame(200, $response->code());
        $this->assertSame(['Content-Type' => 'text/plain'], $response->headers());
    }

    public function testConstructorFailsIfInvalidCode()
    {
        $this->expectException(Errors\ResponseError::class);
        $this->expectExceptionMessage('666 is not a valid HTTP code.');

        $response = new Response(666);
    }

    public function testOk()
    {
        $response = Response::ok();

        $this->assertSame(200, $response->code());
    }

    public function testAccepted()
    {
        $response = Response::accepted();

        $this->assertSame(202, $response->code());
    }

    public function testFound()
    {
        $response = Response::found('https://example.com');

        $this->assertSame(302, $response->code());
        $headers = $response->headers();
        $this->assertSame('https://example.com', $headers['Location']);
    }

    public function testRedirect()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#items');
        Url::setRouter($router);

        $response = Response::redirect('rabbits#items');

        $this->assertSame(302, $response->code());
        $headers = $response->headers();
        $this->assertSame('/rabbits', $headers['Location']);
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
