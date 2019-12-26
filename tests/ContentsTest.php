<?php

namespace Webubbub\controllers\contents;

use Minz\Tests\IntegrationTestCase;
use Webubbub\models;

class ContentsTest extends IntegrationTestCase
{
    public function tearDown(): void
    {
        \Minz\Time::unfreeze();
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

    public function testDeliver()
    {
        $content_dao = new models\dao\Content();
        $content_delivery_dao = new models\dao\ContentDelivery();

        $content_id = self::$factories['contents']->create([
            'status' => 'fetched',
        ]);
        $content_delivery_id = self::$factories['content_deliveries']->create([
            'content_id' => $content_id,
        ]);
        $request = new \Minz\Request('CLI', '/contents/deliver');

        \Webubbub\services\Curl::mock();

        $response = self::$application->run($request);

        $content = $content_dao->find($content_id);
        $content_delivery = $content_delivery_dao->find($content_delivery_id);
        $this->assertResponse($response, 200);
        $this->assertSame('delivered', $content['status']);
        $this->assertNull($content_delivery);
    }

    public function testDeliverWithNewStatus()
    {
        $content_dao = new models\dao\Content();

        $content_id = self::$factories['contents']->create([
            'status' => 'new',
        ]);
        $request = new \Minz\Request('CLI', '/contents/deliver');

        \Webubbub\services\Curl::mock();

        $response = self::$application->run($request);

        $content = $content_dao->find($content_id);
        $this->assertResponse($response, 200);
        $this->assertSame('new', $content['status']);
    }

    public function testDeliverWithTryAtInFuture()
    {
        \Minz\Time::freeze(1000);

        $content_dao = new models\dao\Content();
        $content_delivery_dao = new models\dao\ContentDelivery();

        $content_id = self::$factories['contents']->create([
            'status' => 'fetched',
        ]);
        $content_delivery_id = self::$factories['content_deliveries']->create([
            'content_id' => $content_id,
            'try_at' => 2000,
        ]);
        $request = new \Minz\Request('CLI', '/contents/deliver');

        \Webubbub\services\Curl::mock();

        $response = self::$application->run($request);

        $content = $content_dao->find($content_id);
        $content_delivery = $content_delivery_dao->find($content_delivery_id);
        $this->assertResponse($response, 200);
        $this->assertSame('fetched', $content['status']);
        $this->assertNotNull($content_delivery);
    }

    public function testDeliverWithNoContentDeliveries()
    {
        $content_dao = new models\dao\Content();

        $content_id = self::$factories['contents']->create([
            'status' => 'fetched',
        ]);
        $request = new \Minz\Request('CLI', '/contents/deliver');

        \Webubbub\services\Curl::mock();

        $response = self::$application->run($request);

        $content = $content_dao->find($content_id);
        $this->assertResponse($response, 200);
        $this->assertSame('delivered', $content['status']);
    }

    public function testDeliverWithSecret()
    {
        $content_dao = new models\dao\Content();

        $content_content = 'some content';
        $subscription_secret = 'a very secure secret';

        $content_id = self::$factories['contents']->create([
            'status' => 'fetched',
            'content' => $content_content,
        ]);
        $subscription_id = self::$factories['subscriptions']->create([
            'secret' => $subscription_secret,
        ]);
        $content_delivery_id = self::$factories['content_deliveries']->create([
            'content_id' => $content_id,
            'subscription_id' => $subscription_id,
        ]);
        $request = new \Minz\Request('CLI', '/contents/deliver');

        $mock = \Webubbub\services\Curl::mock();

        $response = self::$application->run($request);

        $content = $content_dao->find($content_id);
        $expected_signature = 'sha256=' . hash_hmac(
            'sha256',
            $content_content,
            $subscription_secret
        );
        $this->assertResponse($response, 200);
        $this->assertSame('delivered', $content['status']);
        $this->assertSame($expected_signature, $mock->received_headers['X-Hub-Signature']);
    }

    public function testDeliverWith410HttpCode()
    {
        $content_dao = new models\dao\Content();
        $subscription_dao = new models\dao\Subscription();
        $content_delivery_dao = new models\dao\ContentDelivery();

        $content_id = self::$factories['contents']->create([
            'status' => 'fetched',
        ]);
        $subscription_id = self::$factories['subscriptions']->create();
        $content_delivery_id = self::$factories['content_deliveries']->create([
            'content_id' => $content_id,
            'subscription_id' => $subscription_id,
        ]);
        $request = new \Minz\Request('CLI', '/contents/deliver');

        \Webubbub\services\Curl::mock('', 410);

        $response = self::$application->run($request);

        $content = $content_dao->find($content_id);
        $subscription = $subscription_dao->find($subscription_id);
        $content_delivery = $content_delivery_dao->find($content_delivery_id);
        $this->assertResponse($response, 200);
        $this->assertSame('delivered', $content['status']);
        $this->assertNull($subscription);
        $this->assertNull($content_delivery);
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
