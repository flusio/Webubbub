<?php

namespace Webubbub\jobs;

use tests\factories\SubscriptionFactory;
use Webubbub\models;
use Webubbub\services;

class ProcessSubscriptionsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    private static int $expected_code = 200;

    public function setUp(): void
    {
        services\Curl::mockCallback(function (string $url, array $options): services\Curl {
            $url = parse_url($url);
            parse_str($url['query'] ?? '', $params);
            /** @var string */
            $challenge = $params['hub_challenge'] ?? '';

            return new services\Curl($challenge, self::$expected_code, []);
        });
    }

    public function tearDown(): void
    {
        services\Curl::resetMock();
    }

    public function testPerform(): void
    {
        $subscription = SubscriptionFactory::create([
            'status' => 'new',
            'pending_request' => 'subscribe',
        ]);

        $processor = new ProcessSubscriptions();
        $processor->perform();

        $subscription = $subscription->reload();
        $this->assertSame('verified', $subscription->status);
    }

    public function testPerformWithAllowedTopic(): void
    {
        \Webubbub\Configuration::$application['allowed_topic_origins'] = 'https://allowed.1.com,https://allowed.2.com';
        $subscription = SubscriptionFactory::create([
            'status' => 'new',
            'pending_request' => 'subscribe',
            'topic' => 'https://allowed.2.com',
        ]);

        $processor = new ProcessSubscriptions();
        $processor->perform();

        $subscription = $subscription->reload();
        $this->assertSame('verified', $subscription->status);

        \Webubbub\Configuration::$application['allowed_topic_origins'] = '';
    }

    public function testPerformWithNotAllowedTopic(): void
    {
        \Webubbub\Configuration::$application['allowed_topic_origins'] = 'https://allowed.1.com,https://allowed.2.com';
        $subscription = SubscriptionFactory::create([
            'status' => 'new',
            'pending_request' => 'subscribe',
            'topic' => 'https://not.allowed.com',
        ]);

        $processor = new ProcessSubscriptions();
        $processor->perform();

        $this->assertFalse(models\Subscription::exists($subscription->id));

        \Webubbub\Configuration::$application['allowed_topic_origins'] = '';
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('failingHttpCodeProvider')]
    public function testPerformWithNotAllowedTopicAndRecentSubscriptionAndFailingResponse(int $http_code): void
    {
        \Webubbub\Configuration::$application['allowed_topic_origins'] = 'https://allowed.1.com,https://allowed.2.com';
        $subscription = SubscriptionFactory::create([
            'status' => 'new',
            'pending_request' => 'subscribe',
            'topic' => 'https://not.allowed.com',
            'created_at' => \Minz\Time::ago(12, 'hours'),
        ]);
        \Webubbub\services\Curl::mock('Failing response', $http_code);

        $processor = new ProcessSubscriptions();
        $processor->perform();

        $subscription = $subscription->reload();
        $this->assertSame('new', $subscription->status);

        \Webubbub\Configuration::$application['allowed_topic_origins'] = '';
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('failingHttpCodeProvider')]
    public function testPerformWithNotAllowedTopicAndOldSubscriptionAndFailingResponse(int $http_code): void
    {
        \Webubbub\Configuration::$application['allowed_topic_origins'] = 'https://allowed.1.com,https://allowed.2.com';
        $subscription = SubscriptionFactory::create([
            'status' => 'new',
            'pending_request' => 'subscribe',
            'topic' => 'https://not.allowed.com',
            'created_at' => \Minz\Time::ago(26, 'hours'),
        ]);
        \Webubbub\services\Curl::mock('Failing response', $http_code);

        $processor = new ProcessSubscriptions();
        $processor->perform();

        $this->assertFalse(models\Subscription::exists($subscription->id));

        \Webubbub\Configuration::$application['allowed_topic_origins'] = '';
    }

    public function testPerformWithSubscribePendingRequest(): void
    {
        $subscription = SubscriptionFactory::create([
            'status' => 'validated',
            'pending_request' => 'subscribe',
        ]);

        $processor = new ProcessSubscriptions();
        $processor->perform();

        $subscription = $subscription->reload();
        $this->assertSame('verified', $subscription->status);
    }

    public function testPerformWithUnsubscribePendingRequest(): void
    {
        $subscription = SubscriptionFactory::create([
            'status' => 'verified',
            'pending_request' => 'unsubscribe',
        ]);

        $processor = new ProcessSubscriptions();
        $processor->perform();

        $this->assertFalse(models\Subscription::exists($subscription->id));
    }

    public function testPerformWithoutPendingRequest(): void
    {
        $subscription = SubscriptionFactory::create([
            'status' => 'validated',
            'pending_request' => null,
        ]);

        $processor = new ProcessSubscriptions();
        $processor->perform();

        $subscription = $subscription->reload();
        $this->assertSame('validated', $subscription->status);
    }

    public function testPerformWithSubscribeAndPendingSecretAndLeaseSeconds(): void
    {
        $subscription = SubscriptionFactory::create([
            'status' => 'verified',
            'lease_seconds' => models\Subscription::DEFAULT_LEASE_SECONDS,
            'secret' => 'a secret',
            'pending_request' => 'subscribe',
            'pending_lease_seconds' => models\Subscription::MIN_LEASE_SECONDS,
            'pending_secret' => 'another secret',
        ]);

        $processor = new ProcessSubscriptions();
        $processor->perform();

        $subscription = $subscription->reload();
        $this->assertSame(
            models\Subscription::MIN_LEASE_SECONDS,
            intval($subscription->lease_seconds)
        );
        $this->assertSame('another secret', $subscription->secret);
        $this->assertNull($subscription->pending_lease_seconds);
        $this->assertNull($subscription->pending_secret);
    }

    public function testPerformWithSubscribeAndUnmatchingChallenge(): void
    {
        $subscription = SubscriptionFactory::create([
            'status' => 'validated',
            'pending_request' => 'subscribe',
        ]);

        \Webubbub\services\Curl::mock('not the correct challenge');

        $processor = new ProcessSubscriptions();
        $processor->perform();

        $subscription = $subscription->reload();
        $this->assertSame('validated', $subscription->status);
        $this->assertNull($subscription->pending_request);
    }

    public function testPerformWithUnsubscribeAndUnmatchingChallenge(): void
    {
        $subscription = SubscriptionFactory::create([
            'status' => 'validated',
            'pending_request' => 'unsubscribe',
        ]);

        \Webubbub\services\Curl::mock('not the correct challenge');

        $processor = new ProcessSubscriptions();
        $processor->perform();

        $subscription = $subscription->reload();
        $this->assertSame('validated', $subscription->status);
        $this->assertNull($subscription->pending_request);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('failingHttpCodeProvider')]
    public function testPerformWithSubscribeAndNonSuccessHttpCode(int $http_code): void
    {
        $subscription = SubscriptionFactory::create([
            'status' => 'validated',
            'pending_request' => 'subscribe',
        ]);

        self::$expected_code = $http_code;

        $processor = new ProcessSubscriptions();
        $processor->perform();

        self::$expected_code = 200;

        $subscription = $subscription->reload();
        $this->assertSame('validated', $subscription->status);
        $this->assertNull($subscription->pending_request);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('failingHttpCodeProvider')]
    public function testPerformWithUnsubscribeAndNonSuccessHttpCode(int $http_code): void
    {
        $subscription = SubscriptionFactory::create([
            'status' => 'validated',
            'pending_request' => 'unsubscribe',
        ]);

        self::$expected_code = $http_code;

        $processor = new ProcessSubscriptions();
        $processor->perform();

        self::$expected_code = 200;

        $subscription = $subscription->reload();
        $this->assertSame('validated', $subscription->status);
        $this->assertNull($subscription->pending_request);
    }

    public function testPerformWithExpired(): void
    {
        $subscription = SubscriptionFactory::create([
            'expired_at' => \Minz\Time::now(),
            'status' => 'verified',
        ]);

        $processor = new ProcessSubscriptions();
        $processor->perform();

        $subscription = $subscription->reload();
        $this->assertSame('expired', $subscription->status);
    }

    public function testPerformWithExpiredDateInFuture(): void
    {
        $subscription = SubscriptionFactory::create([
            'expired_at' => \Minz\Time::fromNow(1, 'day'),
            'status' => 'verified',
        ]);

        $processor = new ProcessSubscriptions();
        $processor->perform();

        $subscription = $subscription->reload();
        $this->assertSame('verified', $subscription->status);
    }

    /**
     * @return array<array{int}>
     */
    public static function failingHttpCodeProvider(): array
    {
        return [
            [301], [302], [307], [308],
            [400], [401], [404], [410],
            [500], [503], [504],
        ];
    }
}
