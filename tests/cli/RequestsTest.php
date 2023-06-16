<?php

namespace Webubbub\cli;

use Webubbub\models;
use tests\factories\ContentFactory;
use tests\factories\SubscriptionFactory;

class RequestsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @dataProvider invalidModeProvider
     */
    public function testHandleFailsIfModeIsInvalid(string $invalid_mode): void
    {
        $response = $this->appRun('POST', '/', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
            'hub_mode' => $invalid_mode,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseEquals($response, "{$invalid_mode} mode is invalid.\n");
        $this->assertResponseHeaders($response, ['Content-Type' => 'text/plain']);
    }

    public function testSubscribe(): void
    {
        $response = $this->appRun('CLI', '/requests/subscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
            'hub_lease_seconds' => '432000',
            'hub_secret' => 'a cryptographically random unique secret string',
        ]);

        $this->assertResponseCode($response, 202);
        $subscription = models\Subscription::take();
        $this->assertNotNull($subscription);
        $this->assertSame('https://subscriber.com/callback', $subscription->callback);
        $this->assertSame('https://some.site.fr/feed.xml', $subscription->topic);
        $this->assertSame(432000, $subscription->lease_seconds);
        $this->assertSame(
            'a cryptographically random unique secret string',
            $subscription->secret
        );
        $this->assertSame('new', $subscription->status);
    }

    public function testSubscribeWithExistingSubscription(): void
    {
        $callback = 'https://subscriber.com/callback';
        $topic = 'https://some.site.fr/feed.xml';
        $subscription = SubscriptionFactory::create([
            'callback' => $callback,
            'topic' => $topic,
            'secret' => null,
            'lease_seconds' => 432000,
            'pending_request' => null,
        ]);
        $this->assertSame(1, models\Subscription::count());

        $response = $this->appRun('CLI', '/requests/subscribe', [
            'hub_callback' => $callback,
            'hub_topic' => $topic,
            'hub_lease_seconds' => '543000',
            'hub_secret' => 'a secret string',
        ]);

        $this->assertResponseCode($response, 202);
        $this->assertSame(1, models\Subscription::count());
        $subscription = $subscription->reload();
        $this->assertNotNull($subscription);
        $this->assertSame(432000, $subscription->lease_seconds);
        $this->assertNull($subscription->secret);
        $this->assertSame(543000, $subscription->pending_lease_seconds);
        $this->assertSame('a secret string', $subscription->pending_secret);
        $this->assertSame('subscribe', $subscription->pending_request);
    }

    public function testSubscribeWithNoLeaseSeconds(): void
    {
        $response = $this->appRun('CLI', '/requests/subscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
            'hub_secret' => 'a cryptographically random unique secret string',
        ]);

        $this->assertResponseCode($response, 202);
        $subscription = models\Subscription::take();
        $this->assertNotNull($subscription);
        $this->assertSame('https://subscriber.com/callback', $subscription->callback);
        $this->assertSame('https://some.site.fr/feed.xml', $subscription->topic);
        $this->assertSame(
            models\Subscription::DEFAULT_LEASE_SECONDS,
            $subscription->lease_seconds
        );
        $this->assertSame(
            'a cryptographically random unique secret string',
            $subscription->secret
        );
        $this->assertSame('new', $subscription->status);
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testSubscribeFailsIfCallbackIsInvalid(string $invalid_url): void
    {
        $response = $this->appRun('CLI', '/requests/subscribe', [
            'hub_callback' => $invalid_url,
            'hub_topic' => 'https://some.site.fr/feed.xml',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, "callback \"{$invalid_url}\" is invalid URL");
        $this->assertResponseHeaders($response, ['Content-Type' => 'text/plain']);
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testSubscribeFailsIfTopicIsInvalid(string $invalid_url): void
    {
        $response = $this->appRun('CLI', '/requests/subscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => $invalid_url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, "topic \"{$invalid_url}\" is invalid URL");
        $this->assertResponseHeaders($response, ['Content-Type' => 'text/plain']);
    }

    public function testSubscribeWithExistingSubscriptionFailsIfSecretIsInvalid(): void
    {
        $callback = 'https://subscriber.com/callback';
        $topic = 'https://some.site.fr/feed.xml';
        $subscription = SubscriptionFactory::create([
            'callback' => $callback,
            'topic' => $topic,
            'secret' => null,
            'lease_seconds' => 432000,
            'pending_request' => null,
        ]);

        $response = $this->appRun('CLI', '/requests/subscribe', [
            'hub_callback' => $callback,
            'hub_topic' => $topic,
            'hub_lease_seconds' => 543000,
            'hub_secret' => '',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseEquals(
            $response,
            "secret must either be not given or be a cryptographically random unique secret string.\n"
        );
        $this->assertResponseHeaders($response, ['Content-Type' => 'text/plain']);
    }

    public function testUnsubscribe(): void
    {
        $callback = 'https://subscriber.com/callback';
        $topic = 'https://some.site.fr/feed.xml';
        $subscription = SubscriptionFactory::create([
            'callback' => $callback,
            'topic' => $topic,
            'status' => 'new',
            'pending_request' => null,
        ]);

        $response = $this->appRun('CLI', '/requests/unsubscribe', [
            'hub_callback' => $callback,
            'hub_topic' => $topic,
        ]);

        $this->assertResponseCode($response, 202);
        $subscription = $subscription->reload();
        $this->assertNotNull($subscription);
        $this->assertSame('new', $subscription->status);
        $this->assertSame('unsubscribe', $subscription->pending_request);
    }

    public function testUnsubscribeWithUnknownSubscription(): void
    {
        $response = $this->appRun('CLI', '/requests/unsubscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseEquals($response, "Unknown subscription.\n");
        $this->assertSame(0, models\Subscription::count());
    }

    public function testPublish(): void
    {
        $url = 'https://some.site.fr/feed.xml';

        $response = $this->appRun('CLI', '/requests/publish', [
            'hub_url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $content = models\Content::take();
        $this->assertNotNull($content);
        $this->assertSame($url, $content->url);
    }

    public function testPublishAcceptsTopic(): void
    {
        $url = 'https://some.site.fr/feed.xml';

        $response = $this->appRun('CLI', '/requests/publish', [
            'hub_topic' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $content = models\Content::take();
        $this->assertNotNull($content);
        $this->assertSame($url, $content->url);
    }

    public function testPublishWithSameUrlAndNewStatus(): void
    {
        $url = 'https://some.site.fr/feed.xml';
        ContentFactory::create([
            'url' => $url,
            'status' => 'new',
        ]);
        $this->assertSame(1, models\Content::count());

        $response = $this->appRun('CLI', '/requests/publish', [
            'hub_url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertSame(1, models\Content::count());
    }

    public function testPublishWithSameUrlAndFetchedStatus(): void
    {
        $url = 'https://some.site.fr/feed.xml';
        ContentFactory::create([
            'url' => $url,
            'status' => 'fetched',
        ]);
        $this->assertSame(1, models\Content::count());

        $response = $this->appRun('CLI', '/requests/publish', [
            'hub_url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertSame(2, models\Content::count());
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testPublishFailsIfUrlIsInvalid(string $invalid_url): void
    {
        $response = $this->appRun('CLI', '/requests/publish', [
            'hub_url' => $invalid_url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, "url \"{$invalid_url}\" is invalid URL");
        $this->assertResponseHeaders($response, ['Content-Type' => 'text/plain']);
        $this->assertSame(0, models\Content::count());
    }

    /**
     * @return array<array{string}>
     */
    public function invalidUrlProvider(): array
    {
        return [
            [''],
            ['some/string'],
            ['ftp://some.site.fr'],
            ['http://'],
        ];
    }

    /**
     * @return array<array{string}>
     */
    public function invalidModeProvider(): array
    {
        return [
            [''],
            ['not a mode'],
            ['subscribemaybe'],
        ];
    }
}
