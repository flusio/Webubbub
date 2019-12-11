<?php

namespace Webubbub\models;

use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    public function testConstructor()
    {
        $callback = 'https://subscriber.com/callback';
        $topic = 'https://some.site.fr/feed.xml';

        $subscription = new Subscription($callback, $topic);

        $this->assertSame($callback, $subscription->callback());
        $this->assertSame($topic, $subscription->topic());
        $this->assertSame(
            Subscription::DEFAULT_LEASE_SECONDS,
            $subscription->leaseSeconds()
        );
        $this->assertSame('new', $subscription->status());
        $this->assertSame('subscription', $subscription->pendingRequest());
    }

    public function testConstructorDecodesUrls()
    {
        $callback = 'https://subscriber.com/callback?foo%2Bbar';
        $topic = 'https://some.site.fr/feed.xml?foo%2Bbar';

        $subscription = new Subscription($callback, $topic);

        $this->assertSame(
            'https://subscriber.com/callback?foo+bar',
            $subscription->callback()
        );
        $this->assertSame(
            'https://some.site.fr/feed.xml?foo+bar',
            $subscription->topic()
        );
    }

    public function testConstructorWithLeaseSeconds()
    {
        $lease_seconds = Subscription::DEFAULT_LEASE_SECONDS / 2;

        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            $lease_seconds
        );

        $this->assertSame($lease_seconds, $subscription->leaseSeconds());
    }

    public function testConstructorForcesMinLeaseSeconds()
    {
        $lease_seconds = Subscription::MIN_LEASE_SECONDS - 42;

        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            $lease_seconds
        );

        $this->assertSame(
            Subscription::MIN_LEASE_SECONDS,
            $subscription->leaseSeconds()
        );
    }

    public function testConstructorForcesMaxLeaseSeconds()
    {
        $lease_seconds = Subscription::MAX_LEASE_SECONDS + 42;

        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            $lease_seconds
        );

        $this->assertSame(
            Subscription::MAX_LEASE_SECONDS,
            $subscription->leaseSeconds()
        );
    }

    public function testConstructorWithSecret()
    {
        $secret = 'a cryptographically random unique secret string';

        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            0,
            $secret
        );

        $this->assertSame($secret, $subscription->secret());
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testConstructorFailsIfCallbackIsInvalid($invalid_url)
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage("{$invalid_url} callback is invalid.");

        new Subscription($invalid_url, 'https://some.site.fr/feed.xml');
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testConstructorFailsIfTopicIsInvalid($invalid_url)
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage("{$invalid_url} topic is invalid.");

        new Subscription('https://subscriber.com/callback', $invalid_url);
    }

    public function testConstructorFailsIfSecretIsEmptyString()
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage(
            'Secret must either be not given or be a cryptographically random unique secret string.'
        );

        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            0,
            ''
        );
    }

    public function testConstructorFailsIfSecretIsMoreThan200Bytes()
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage(
            'Secret must be equal or less than 200 bytes in length.'
        );

        $secret = str_repeat('a', 201);

        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            0,
            $secret
        );
    }

    public function invalidUrlProvider()
    {
        return [
            [''],
            ['some/string'],
            ['ftp://some.site.fr'],
            ['http://'],
        ];
    }
}
