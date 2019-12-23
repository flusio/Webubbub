<?php

namespace Webubbub\controllers\contents;

use Minz\Tests\IntegrationTestCase;
use Webubbub\models;

class ContentsTest extends IntegrationTestCase
{
    public function tearDown(): void
    {
        \Webubbub\services\Curl::resetMock();
    }

    public function testFetch()
    {
        $dao = new models\dao\Content();
        $id = $dao->create([
            'url' => 'https://some.site.fr/feed',
            'created_at' => time(),
            'status' => 'new',
        ]);
        $request = new \Minz\Request('CLI', '/contents/fetch');

        \Webubbub\services\Curl::mock(
            '<some>xml</some>',
            200,
            [
                'content-type' => ['application/rss+xml'],
                'link' => [
                    '<https://my-hub.com>; rel="hub"',
                    '<https://some.site.fr/feed.xml>; rel="self"',
                ],
            ]
        );

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $expected_links = '<https://my-hub.com>; rel="hub", '
                        . '<https://some.site.fr/feed.xml>; rel="self"';
        $this->assertResponse($response, 200);
        $this->assertSame('fetched', $subscription['status']);
        $this->assertSame('<some>xml</some>', $subscription['content']);
        $this->assertSame('application/rss+xml', $subscription['type']);
        $this->assertSame($expected_links, $subscription['links']);
    }

    public function testFetchWithNoLinks()
    {
        $dao = new models\dao\Content();
        $id = $dao->create([
            'url' => 'https://some.site.fr/feed',
            'created_at' => time(),
            'status' => 'new',
        ]);
        $request = new \Minz\Request('CLI', '/contents/fetch');

        \Webubbub\services\Curl::mock(
            '<some>xml</some>',
            200,
            ['content-type' => ['application/rss+xml']]
        );

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $expected_links = '<http://localhost/>; rel="hub", '
                        . '<https://some.site.fr/feed>; rel="self"';
        $this->assertResponse($response, 200);
        $this->assertSame($expected_links, $subscription['links']);
    }

    public function testFetchWithMissingSelfLink()
    {
        $dao = new models\dao\Content();
        $id = $dao->create([
            'url' => 'https://some.site.fr/feed',
            'created_at' => time(),
            'status' => 'new',
        ]);
        $request = new \Minz\Request('CLI', '/contents/fetch');

        \Webubbub\services\Curl::mock(
            '<some>xml</some>',
            200,
            [
                'content-type' => ['application/rss+xml'],
                'link' => [
                    '<https://my-hub.com>; rel="hub"',
                ],
            ]
        );

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $expected_links = '<https://my-hub.com>; rel="hub", '
                        . '<https://some.site.fr/feed>; rel="self"';
        $this->assertResponse($response, 200);
        $this->assertSame($expected_links, $subscription['links']);
    }

    public function testFetchWithMissingHubLink()
    {
        $dao = new models\dao\Content();
        $id = $dao->create([
            'url' => 'https://some.site.fr/feed',
            'created_at' => time(),
            'status' => 'new',
        ]);
        $request = new \Minz\Request('CLI', '/contents/fetch');

        \Webubbub\services\Curl::mock(
            '<some>xml</some>',
            200,
            [
                'content-type' => ['application/rss+xml'],
                'link' => [
                    '<https://some.site.fr/feed.xml>; rel="self"',
                ],
            ]
        );

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $expected_links = '<https://some.site.fr/feed.xml>; rel="self", '
                        . '<http://localhost/>; rel="hub"';
        $this->assertSame($expected_links, $subscription['links']);
    }

    public function testFetchWithMissingContentType()
    {
        $dao = new models\dao\Content();
        $id = $dao->create([
            'url' => 'https://some.site.fr/feed',
            'created_at' => time(),
            'status' => 'new',
        ]);
        $request = new \Minz\Request('CLI', '/contents/fetch');

        \Webubbub\services\Curl::mock(
            '<some>xml</some>',
            200,
            [
                'link' => [
                    '<https://my-hub.com>; rel="hub"',
                    '<https://some.site.fr/feed.xml>; rel="self"',
                ],
            ]
        );

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('application/octet-stream', $subscription['type']);
    }

    public function testFetchWithErrorHttpCode()
    {
        $dao = new models\dao\Content();
        $id = $dao->create([
            'url' => 'https://some.site.fr/feed',
            'created_at' => time(),
            'status' => 'new',
        ]);
        $request = new \Minz\Request('CLI', '/contents/fetch');

        \Webubbub\services\Curl::mock(
            'Oops, not found',
            404,
            ['content-type' => ['text/html']]
        );

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('new', $subscription['status']);
        $this->assertNull($subscription['content']);
        $this->assertNull($subscription['type']);
        $this->assertNull($subscription['links']);
    }

    public function testItems()
    {
        $dao = new models\dao\Content();
        $dao->create([
            'url' => 'https://some.site.fr/feed.xml',
            'created_at' => time(),
            'status' => 'new',
        ]);
        $request = new \Minz\Request('CLI', '/contents');

        $response = self::$application->run($request);

        $output = $response->render();
        $this->assertResponse($response, 200);
        $this->assertStringContainsString('https://some.site.fr/feed.xml', $output);
    }
}
