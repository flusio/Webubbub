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

        $headers = $response->headers(true);
        $this->assertSame('application/xml', $headers['Content-Type']);
    }

    public function testSetContentSecurityPolicy()
    {
        $response = new Response(200, '');

        $response->setContentSecurityPolicy('script-src', "'self' 'unsafe-eval'");

        $headers = $response->headers(true);
        $csp = $headers['Content-Security-Policy'];
        $this->assertArrayHasKey('script-src', $csp);
        $this->assertSame("'self' 'unsafe-eval'", $csp['script-src']);
    }

    public function testConstructor()
    {
        $view = new Output\View('rabbits/items.phtml');
        $response = new Response(200, $view);

        $this->assertSame(200, $response->code());
        $this->assertSame([
            'Content-Type' => 'text/html',
            'Content-Security-Policy' => [
                'default-src' => "'self'",
            ]
        ], $response->headers(true));
    }

    public function testConstructorAdaptsTheContentTypeFromView()
    {
        $view = new Output\View('rabbits/items.txt');
        $response = new Response(200, $view);

        $headers = $response->headers(true);
        $this->assertSame('text/plain', $headers['Content-Type']);
    }

    public function testConstructorAcceptsNoViews()
    {
        $response = new Response(200, null);

        $this->assertSame(200, $response->code());
        $headers = $response->headers(true);
        $this->assertSame('text/plain', $headers['Content-Type']);
    }

    public function testConstructorFailsIfInvalidCode()
    {
        $this->expectException(Errors\ResponseError::class);
        $this->expectExceptionMessage('666 is not a valid HTTP code.');

        $response = new Response(666);
    }

    public function testHeaders()
    {
        $response = new Response(200);
        $response->setHeader('Content-Type', 'image/png');

        $headers = $response->headers();

        $content_type_header = current(array_filter($headers, function ($header) {
            return strpos($header, 'Content-Type') === 0;
        }));
        $this->assertSame('Content-Type: image/png', $content_type_header);
    }

    public function testHeadersWithComplexStructure()
    {
        $response = new Response(200);
        $response->setHeader('Content-Security-Policy', [
            'default-src' => "'self'",
            'style-src' => "'self' 'unsafe-inline'",
        ]);

        $headers = $response->headers();

        $csp_header = current(array_filter($headers, function ($header) {
            return strpos($header, 'Content-Security-Policy') === 0;
        }));
        $this->assertSame(
            "Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'",
            $csp_header
        );
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
        $headers = $response->headers(true);
        $this->assertSame('https://example.com', $headers['Location']);
    }

    public function testRedirect()
    {
        $router = new Router();
        $router->addRoute('get', '/rabbits', 'rabbits#items');
        Url::setRouter($router);

        $response = Response::redirect('rabbits#items');

        $this->assertSame(302, $response->code());
        $headers = $response->headers(true);
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
