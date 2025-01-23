<?php

namespace Webubbub\jobs;

use Webubbub\models;
use tests\factories\ContentFactory;
use tests\factories\ContentDeliveryFactory;
use tests\factories\SubscriptionFactory;

class ProcessContentsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;

    public function setUp(): void
    {
        $router = \Webubbub\Router::loadCli();
        \Minz\Engine::init($router);
    }

    public function tearDown(): void
    {
        \Webubbub\services\Curl::resetMock();
    }

    public function testPerform(): void
    {
        $content = ContentFactory::create([
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

        $processor = new ProcessContents();
        $processor->perform();

        $expected_links = '<https://my-hub.com>; rel="hub", '
                        . '<https://some.site.fr/feed.xml>; rel="self"';
        $content = $content->reload();
        $this->assertSame('delivered', $content->status);
        $this->assertSame('<some>xml</some>', $content->content);
        $this->assertSame('application/rss+xml', $content->type);
        $this->assertSame($expected_links, $content->links);
    }

    public function testPerformWithVerifiedSubscription(): void
    {
        $topic_url = 'https://some.site.fr/feed';

        $subscription = SubscriptionFactory::create([
            'topic' => $topic_url,
            'status' => 'verified',
        ]);
        $content = ContentFactory::create([
            'url' => $topic_url,
            'status' => 'new',
        ]);
        \Webubbub\services\Curl::mock('<some>xml</some>');

        $processor = new ProcessContents();
        $processor->perform();

        $content = $content->reload();
        $this->assertSame('delivered', $content->status);
        $this->assertSame(0, models\ContentDelivery::count());
        $this->assertTrue(models\Subscription::exists($subscription->id));
    }

    public function testPerformWithNewSubscription(): void
    {
        $topic_url = 'https://some.site.fr/feed';
        $subscription = SubscriptionFactory::create([
            'topic' => $topic_url,
            'status' => 'new',
        ]);
        $content = ContentFactory::create([
            'url' => $topic_url,
            'status' => 'new',
        ]);
        \Webubbub\services\Curl::mock('<some>xml</some>');

        $processor = new ProcessContents();
        $processor->perform();

        $content = $content->reload();
        $this->assertSame('delivered', $content->status);
        $this->assertSame(0, models\ContentDelivery::count());
        $this->assertTrue(models\Subscription::exists($subscription->id));
    }

    public function testPerformWithNoLinks(): void
    {
        $content = ContentFactory::create([
            'url' => 'https://some.site.fr/feed',
            'status' => 'new',
        ]);

        \Webubbub\services\Curl::mock(
            '<some>xml</some>',
            200,
            ['content-type' => ['application/rss+xml']]
        );

        $processor = new ProcessContents();
        $processor->perform();

        $expected_links = '<http://localhost/>; rel="hub", '
                        . '<https://some.site.fr/feed>; rel="self"';
        $content = $content->reload();
        $this->assertSame($expected_links, $content->links);
    }

    public function testPerformWithMissingSelfLink(): void
    {
        $content = ContentFactory::create([
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

        $processor = new ProcessContents();
        $processor->perform();

        $expected_links = '<https://my-hub.com>; rel="hub", '
                        . '<https://some.site.fr/feed>; rel="self"';
        $content = $content->reload();
        $this->assertSame($expected_links, $content->links);
    }

    public function testPerformWithMissingHubLink(): void
    {
        $content = ContentFactory::create([
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

        $processor = new ProcessContents();
        $processor->perform();

        $expected_links = '<https://some.site.fr/feed.xml>; rel="self", '
                        . '<http://localhost/>; rel="hub"';
        $content = $content->reload();
        $this->assertSame($expected_links, $content->links);
    }

    public function testPerformWithMissingContentType(): void
    {
        $content = ContentFactory::create([
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

        $processor = new ProcessContents();
        $processor->perform();

        $content = $content->reload();
        $this->assertSame('application/octet-stream', $content->type);
    }

    public function testPerformWithErrorHttpCode(): void
    {
        $content = ContentFactory::create([
            'status' => 'new',
        ]);

        \Webubbub\services\Curl::mock(
            'Oops, not found',
            404,
            ['content-type' => ['text/html']]
        );

        $processor = new ProcessContents();
        $processor->perform();

        $content = $content->reload();
        $this->assertSame('new', $content->status);
        $this->assertNull($content->content);
        $this->assertNull($content->type);
        $this->assertNull($content->links);
    }

    public function testPerformWithDeliveryTryAtInFuture(): void
    {
        $this->freeze();
        $content = ContentFactory::create([
            'status' => 'fetched',
        ]);
        $content_delivery = ContentDeliveryFactory::create([
            'content_id' => $content->id,
            'try_at' => \Minz\Time::fromNow(1000, 'seconds'),
        ]);

        \Webubbub\services\Curl::mock();

        $processor = new ProcessContents();
        $processor->perform();

        $content = $content->reload();
        $this->assertSame('fetched', $content->status);
        $this->assertTrue(models\ContentDelivery::exists($content_delivery->id));
    }

    public function testPerformDeliverWithSecret(): void
    {
        $this->freeze();
        $content_content = 'some content';
        $subscription_secret = 'a very secure secret';
        $content = ContentFactory::create([
            'status' => 'fetched',
            'content' => $content_content,
        ]);
        $subscription = SubscriptionFactory::create([
            'secret' => $subscription_secret,
        ]);
        $content_delivery = ContentDeliveryFactory::create([
            'content_id' => $content->id,
            'subscription_id' => $subscription->id,
            'try_at' => \Minz\Time::ago(1000, 'seconds'),
            'tries_count' => 0,
        ]);

        $mock = \Webubbub\services\Curl::mock();

        $processor = new ProcessContents();
        $processor->performDeliver();

        $expected_signature = 'sha256=' . hash_hmac(
            'sha256',
            $content_content,
            $subscription_secret
        );
        $content = $content->reload();
        $this->assertSame('delivered', $content->status);
        $this->assertSame($expected_signature, $mock->received_headers['X-Hub-Signature']);
    }

    public function testPerformDeliveryWithErrorHttpCode(): void
    {
        $this->freeze();
        $content = ContentFactory::create([
            'status' => 'fetched',
        ]);
        $content_delivery = ContentDeliveryFactory::create([
            'content_id' => $content->id,
            'try_at' => \Minz\Time::ago(1000, 'seconds'),
            'tries_count' => 0,
        ]);

        \Webubbub\services\Curl::mock('', 500);

        $processor = new ProcessContents();
        $processor->performDeliver();

        $content = $content->reload();
        $this->assertSame('fetched', $content->status);
        $content_delivery = $content_delivery->reload();
        $expected_try_at = \Minz\Time::fromNow(5, 'seconds');
        $this->assertSame(
            $expected_try_at->getTimestamp(),
            $content_delivery->try_at->getTimestamp(),
        );
        $this->assertSame(1, $content_delivery->tries_count);
    }

    public function testPerformDeliverWithErrorHttpCodeAndMaxTriesReached(): void
    {
        $this->freeze();
        $content = ContentFactory::create([
            'status' => 'fetched',
        ]);
        $content_delivery = ContentDeliveryFactory::create([
            'content_id' => $content->id,
            'try_at' => \Minz\Time::ago(1000, 'seconds'),
            'tries_count' => models\ContentDelivery::MAX_TRIES_COUNT,
        ]);

        \Webubbub\services\Curl::mock('', 500);

        $processor = new ProcessContents();
        $processor->performDeliver();

        $content = $content->reload();
        $this->assertSame('delivered', $content->status);
        $this->assertFalse(models\ContentDelivery::exists($content_delivery->id));
    }

    public function testPerformDeliverWith410HttpCode(): void
    {
        $this->freeze();
        $content = ContentFactory::create([
            'status' => 'fetched',
        ]);
        $subscription = SubscriptionFactory::create();
        $content_delivery = ContentDeliveryFactory::create([
            'content_id' => $content->id,
            'try_at' => \Minz\Time::ago(1000, 'seconds'),
            'subscription_id' => $subscription->id,
        ]);

        \Webubbub\services\Curl::mock('', 410);

        $processor = new ProcessContents();
        $processor->performDeliver();

        $content = $content->reload();
        $this->assertSame('delivered', $content->status);
        $this->assertFalse(models\Subscription::exists($subscription->id));
        $this->assertFalse(models\ContentDelivery::exists($content_delivery->id));
    }
}
