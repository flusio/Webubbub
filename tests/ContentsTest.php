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
        $id = self::$factories['contents']->create([
            'url' => 'https://some.site.fr/feed',
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

        $content = $dao->find($id);
        $expected_links = '<https://my-hub.com>; rel="hub", '
                        . '<https://some.site.fr/feed.xml>; rel="self"';
        $this->assertResponse($response, 200);
        $this->assertSame('fetched', $content['status']);
        $this->assertSame('<some>xml</some>', $content['content']);
        $this->assertSame('application/rss+xml', $content['type']);
        $this->assertSame($expected_links, $content['links']);
    }

    public function testFetchWithVerifiedSubscription()
    {
        $topic_url = 'https://some.site.fr/feed';

        $content_delivery_dao = new models\dao\ContentDelivery();
        $subscription_id = self::$factories['subscriptions']->create([
            'topic' => $topic_url,
            'status' => 'verified',
        ]);
        $content_id = self::$factories['contents']->create([
            'url' => $topic_url,
            'status' => 'new',
        ]);
        $request = new \Minz\Request('CLI', '/contents/fetch');
        \Webubbub\services\Curl::mock('<some>xml</some>');

        $this->assertSame(0, $content_delivery_dao->count());

        $response = self::$application->run($request);

        $this->assertResponse($response, 200);
        $this->assertSame(1, $content_delivery_dao->count());
        $content_delivery = $content_delivery_dao->listAll()[0];
        $this->assertSame($subscription_id, intval($content_delivery['subscription_id']));
        $this->assertSame($content_id, intval($content_delivery['content_id']));
    }

    public function testFetchWithNewSubscription()
    {
        $content_delivery_dao = new models\dao\ContentDelivery();

        $topic_url = 'https://some.site.fr/feed';

        $subscription_id = self::$factories['subscriptions']->create([
            'topic' => $topic_url,
            'status' => 'new',
        ]);
        $content_id = self::$factories['contents']->create([
            'url' => $topic_url,
            'status' => 'new',
        ]);
        $request = new \Minz\Request('CLI', '/contents/fetch');
        \Webubbub\services\Curl::mock('<some>xml</some>');

        $response = self::$application->run($request);

        $this->assertResponse($response, 200);
        $this->assertSame(0, $content_delivery_dao->count());
    }

    public function testFetchWithNoLinks()
    {
        $dao = new models\dao\Content();
        $id = self::$factories['contents']->create([
            'url' => 'https://some.site.fr/feed',
            'status' => 'new',
        ]);
        $request = new \Minz\Request('CLI', '/contents/fetch');

        \Webubbub\services\Curl::mock(
            '<some>xml</some>',
            200,
            ['content-type' => ['application/rss+xml']]
        );

        $response = self::$application->run($request);

        $content = $dao->find($id);
        $expected_links = '<http://localhost/>; rel="hub", '
                        . '<https://some.site.fr/feed>; rel="self"';
        $this->assertResponse($response, 200);
        $this->assertSame($expected_links, $content['links']);
    }

    public function testFetchWithMissingSelfLink()
    {
        $dao = new models\dao\Content();
        $id = self::$factories['contents']->create([
            'url' => 'https://some.site.fr/feed',
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

        $content = $dao->find($id);
        $expected_links = '<https://my-hub.com>; rel="hub", '
                        . '<https://some.site.fr/feed>; rel="self"';
        $this->assertResponse($response, 200);
        $this->assertSame($expected_links, $content['links']);
    }

    public function testFetchWithMissingHubLink()
    {
        $dao = new models\dao\Content();
        $id = self::$factories['contents']->create([
            'url' => 'https://some.site.fr/feed',
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

        $content = $dao->find($id);
        $expected_links = '<https://some.site.fr/feed.xml>; rel="self", '
                        . '<http://localhost/>; rel="hub"';
        $this->assertSame($expected_links, $content['links']);
    }

    public function testFetchWithMissingContentType()
    {
        $dao = new models\dao\Content();
        $id = self::$factories['contents']->create([
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

        $content = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('application/octet-stream', $content['type']);
    }

    public function testFetchWithErrorHttpCode()
    {
        $dao = new models\dao\Content();
        $id = self::$factories['contents']->create([
            'status' => 'new',
        ]);
        $request = new \Minz\Request('CLI', '/contents/fetch');

        \Webubbub\services\Curl::mock(
            'Oops, not found',
            404,
            ['content-type' => ['text/html']]
        );

        $response = self::$application->run($request);

        $content = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('new', $content['status']);
        $this->assertNull($content['content']);
        $this->assertNull($content['type']);
        $this->assertNull($content['links']);
    }

    public function testItems()
    {
        $dao = new models\dao\Content();
        self::$factories['contents']->create([
            'url' => 'https://some.site.fr/feed.xml',
        ]);
        $request = new \Minz\Request('CLI', '/contents');

        $response = self::$application->run($request);

        $output = $response->render();
        $this->assertResponse($response, 200);
        $this->assertStringContainsString('https://some.site.fr/feed.xml', $output);
    }
}
