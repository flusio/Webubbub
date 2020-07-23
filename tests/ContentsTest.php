<?php

namespace Webubbub;

class ContentsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\TimeHelper;
    use \Minz\Tests\ResponseAsserts;

    public function tearDown(): void
    {
        \Webubbub\services\Curl::resetMock();
    }

    public function testFetch()
    {
        $dao = new models\dao\Content();
        $id = $this->create('contents', [
            'url' => 'https://some.site.fr/feed',
            'status' => 'new',
        ]);

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

        $response = $this->appRun('cli', '/contents/fetch');

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
        $subscription_id = $this->create('subscriptions', [
            'topic' => $topic_url,
            'status' => 'verified',
        ]);
        $content_id = $this->create('contents', [
            'url' => $topic_url,
            'status' => 'new',
        ]);
        \Webubbub\services\Curl::mock('<some>xml</some>');

        $this->assertSame(0, $content_delivery_dao->count());

        $response = $this->appRun('cli', '/contents/fetch');

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

        $subscription_id = $this->create('subscriptions', [
            'topic' => $topic_url,
            'status' => 'new',
        ]);
        $content_id = $this->create('contents', [
            'url' => $topic_url,
            'status' => 'new',
        ]);
        \Webubbub\services\Curl::mock('<some>xml</some>');

        $response = $this->appRun('cli', '/contents/fetch');

        $this->assertResponse($response, 200);
        $this->assertSame(0, $content_delivery_dao->count());
    }

    public function testFetchWithNoLinks()
    {
        $dao = new models\dao\Content();
        $id = $this->create('contents', [
            'url' => 'https://some.site.fr/feed',
            'status' => 'new',
        ]);

        \Webubbub\services\Curl::mock(
            '<some>xml</some>',
            200,
            ['content-type' => ['application/rss+xml']]
        );

        $response = $this->appRun('cli', '/contents/fetch');

        $content = $dao->find($id);
        $expected_links = '<http://localhost/>; rel="hub", '
                        . '<https://some.site.fr/feed>; rel="self"';
        $this->assertResponse($response, 200);
        $this->assertSame($expected_links, $content['links']);
    }

    public function testFetchWithMissingSelfLink()
    {
        $dao = new models\dao\Content();
        $id = $this->create('contents', [
            'url' => 'https://some.site.fr/feed',
            'status' => 'new',
        ]);

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

        $response = $this->appRun('cli', '/contents/fetch');

        $content = $dao->find($id);
        $expected_links = '<https://my-hub.com>; rel="hub", '
                        . '<https://some.site.fr/feed>; rel="self"';
        $this->assertResponse($response, 200);
        $this->assertSame($expected_links, $content['links']);
    }

    public function testFetchWithMissingHubLink()
    {
        $dao = new models\dao\Content();
        $id = $this->create('contents', [
            'url' => 'https://some.site.fr/feed',
            'status' => 'new',
        ]);

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

        $response = $this->appRun('cli', '/contents/fetch');

        $content = $dao->find($id);
        $expected_links = '<https://some.site.fr/feed.xml>; rel="self", '
                        . '<http://localhost/>; rel="hub"';
        $this->assertSame($expected_links, $content['links']);
    }

    public function testFetchWithMissingContentType()
    {
        $dao = new models\dao\Content();
        $id = $this->create('contents', [
            'status' => 'new',
        ]);

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

        $response = $this->appRun('cli', '/contents/fetch');

        $content = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('application/octet-stream', $content['type']);
    }

    public function testFetchWithErrorHttpCode()
    {
        $dao = new models\dao\Content();
        $id = $this->create('contents', [
            'status' => 'new',
        ]);

        \Webubbub\services\Curl::mock(
            'Oops, not found',
            404,
            ['content-type' => ['text/html']]
        );

        $response = $this->appRun('cli', '/contents/fetch');

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

        $content_id = $this->create('contents', [
            'status' => 'fetched',
        ]);
        $content_delivery_id = $this->create('content_deliveries', [
            'content_id' => $content_id,
        ]);

        \Webubbub\services\Curl::mock();

        $response = $this->appRun('cli', '/contents/deliver');

        $content = $content_dao->find($content_id);
        $content_delivery = $content_delivery_dao->find($content_delivery_id);
        $this->assertResponse($response, 200);
        $this->assertSame('delivered', $content['status']);
        $this->assertNull($content_delivery);
    }

    public function testDeliverWithNewStatus()
    {
        $content_dao = new models\dao\Content();

        $content_id = $this->create('contents', [
            'status' => 'new',
        ]);

        \Webubbub\services\Curl::mock();

        $response = $this->appRun('cli', '/contents/deliver');

        $content = $content_dao->find($content_id);
        $this->assertResponse($response, 200);
        $this->assertSame('new', $content['status']);
    }

    public function testDeliverWithTryAtInFuture()
    {
        $this->freeze(1000);

        $content_dao = new models\dao\Content();
        $content_delivery_dao = new models\dao\ContentDelivery();

        $content_id = $this->create('contents', [
            'status' => 'fetched',
        ]);
        $content_delivery_id = $this->create('content_deliveries', [
            'content_id' => $content_id,
            'try_at' => 2000,
        ]);

        \Webubbub\services\Curl::mock();

        $response = $this->appRun('cli', '/contents/deliver');

        $content = $content_dao->find($content_id);
        $content_delivery = $content_delivery_dao->find($content_delivery_id);
        $this->assertResponse($response, 200);
        $this->assertSame('fetched', $content['status']);
        $this->assertNotNull($content_delivery);
    }

    public function testDeliverWithNoContentDeliveries()
    {
        $content_dao = new models\dao\Content();

        $content_id = $this->create('contents', [
            'status' => 'fetched',
        ]);

        \Webubbub\services\Curl::mock();

        $response = $this->appRun('cli', '/contents/deliver');

        $content = $content_dao->find($content_id);
        $this->assertResponse($response, 200);
        $this->assertSame('delivered', $content['status']);
    }

    public function testDeliverWithSecret()
    {
        $this->freeze(2000);

        $content_dao = new models\dao\Content();

        $content_content = 'some content';
        $subscription_secret = 'a very secure secret';

        $content_id = $this->create('contents', [
            'status' => 'fetched',
            'content' => $content_content,
        ]);
        $subscription_id = $this->create('subscriptions', [
            'secret' => $subscription_secret,
        ]);
        $content_delivery_id = $this->create('content_deliveries', [
            'content_id' => $content_id,
            'try_at' => 1000,
            'subscription_id' => $subscription_id,
        ]);

        $mock = \Webubbub\services\Curl::mock();

        $response = $this->appRun('cli', '/contents/deliver');

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

    public function testDeliverWithErrorHttpCode()
    {
        $this->freeze(2000);

        $content_dao = new models\dao\Content();
        $content_delivery_dao = new models\dao\ContentDelivery();

        $content_id = $this->create('contents', [
            'status' => 'fetched',
        ]);
        $content_delivery_id = $this->create('content_deliveries', [
            'content_id' => $content_id,
            'try_at' => 1000,
            'tries_count' => 0,
        ]);

        \Webubbub\services\Curl::mock('', 500);

        $response = $this->appRun('cli', '/contents/deliver');

        $content = $content_dao->find($content_id);
        $content_delivery = $content_delivery_dao->find($content_delivery_id);
        $this->assertResponse($response, 200);
        $this->assertSame('fetched', $content['status']);
        $this->assertSame(2005, intval($content_delivery['try_at']));
        $this->assertSame(1, intval($content_delivery['tries_count']));
    }

    public function testDeliverWithErrorHttpCodeAndMaxTriesReached()
    {
        $this->freeze(2000);

        $content_dao = new models\dao\Content();
        $content_delivery_dao = new models\dao\ContentDelivery();

        $content_id = $this->create('contents', [
            'status' => 'fetched',
        ]);
        $content_delivery_id = $this->create('content_deliveries', [
            'content_id' => $content_id,
            'try_at' => 1000,
            'tries_count' => models\ContentDelivery::MAX_TRIES_COUNT,
        ]);

        \Webubbub\services\Curl::mock('', 500);

        $response = $this->appRun('cli', '/contents/deliver');

        $content = $content_dao->find($content_id);
        $content_delivery = $content_delivery_dao->find($content_delivery_id);
        $this->assertResponse($response, 200);
        $this->assertSame('delivered', $content['status']);
        $this->assertNull($content_delivery);
    }

    public function testDeliverWith410HttpCode()
    {
        $this->freeze(2000);

        $content_dao = new models\dao\Content();
        $subscription_dao = new models\dao\Subscription();
        $content_delivery_dao = new models\dao\ContentDelivery();

        $content_id = $this->create('contents', [
            'status' => 'fetched',
        ]);
        $subscription_id = $this->create('subscriptions');
        $content_delivery_id = $this->create('content_deliveries', [
            'content_id' => $content_id,
            'try_at' => 1000,
            'subscription_id' => $subscription_id,
        ]);

        \Webubbub\services\Curl::mock('', 410);

        $response = $this->appRun('cli', '/contents/deliver');

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
        $this->create('contents', [
            'url' => 'https://some.site.fr/feed.xml',
        ]);

        $response = $this->appRun('cli', '/contents');

        $output = $response->render();
        $this->assertResponse($response, 200);
        $this->assertStringContainsString('https://some.site.fr/feed.xml', $output);
    }
}
