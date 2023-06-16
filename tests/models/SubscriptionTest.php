<?php

namespace Webubbub\models;

use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    use \Minz\Tests\TimeHelper;

    public function testVerify(): void
    {
        $this->freeze();
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            Subscription::MIN_LEASE_SECONDS
        );

        $this->assertSame('new', $subscription->status);
        $this->assertSame('subscribe', $subscription->pending_request);
        $this->assertNull($subscription->expired_at);

        $subscription->verify();

        $expected_expired_at = \Minz\Time::fromNow(Subscription::MIN_LEASE_SECONDS, 'seconds');
        $this->assertSame('verified', $subscription->status);
        $this->assertNull($subscription->pending_request);
        $this->assertNotNull($subscription->expired_at);
        $this->assertSame(
            $expected_expired_at->getTimestamp(),
            $subscription->expired_at->getTimestamp()
        );
    }

    public function testVerifyTwiceFailsBecausePendingRequestIsNull(): void
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage(
            'Subscription cannot be verified because it has no pending requests.'
        );

        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $subscription->verify();

        $subscription->verify();
    }

    public function testVerifyAfterRenewAndDifferentLeaseSecondsAndSecret(): void
    {
        $this->freeze();
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            Subscription::DEFAULT_LEASE_SECONDS,
            'a secret'
        );

        $subscription->renew(Subscription::MIN_LEASE_SECONDS, 'another secret');

        $this->assertSame(Subscription::DEFAULT_LEASE_SECONDS, $subscription->lease_seconds);
        $this->assertSame(Subscription::MIN_LEASE_SECONDS, $subscription->pending_lease_seconds);
        $this->assertSame('a secret', $subscription->secret);
        $this->assertSame('another secret', $subscription->pending_secret);

        $subscription->verify();

        $this->assertSame(Subscription::MIN_LEASE_SECONDS, $subscription->lease_seconds);
        $this->assertNull($subscription->pending_lease_seconds);
        $this->assertSame('another secret', $subscription->secret);
        $this->assertNull($subscription->pending_secret);
    }

    public function testRenew(): void
    {
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            Subscription::MIN_LEASE_SECONDS,
            'a secret'
        );

        $subscription->verify();

        $this->assertNull($subscription->pending_request);
        $this->assertSame(Subscription::MIN_LEASE_SECONDS, $subscription->lease_seconds);
        $this->assertSame('a secret', $subscription->secret);

        $subscription->renew(
            Subscription::DEFAULT_LEASE_SECONDS,
            'another secret'
        );

        $this->assertSame('subscribe', $subscription->pending_request);
        $this->assertSame(Subscription::MIN_LEASE_SECONDS, $subscription->lease_seconds);
        $this->assertSame('a secret', $subscription->secret);
        $this->assertSame(
            Subscription::DEFAULT_LEASE_SECONDS,
            $subscription->pending_lease_seconds
        );
        $this->assertSame('another secret', $subscription->pending_secret);
    }

    public function testRenewForcesLeaseSeconds(): void
    {
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $lease_seconds = Subscription::MIN_LEASE_SECONDS - 42;

        $subscription->renew($lease_seconds);

        $this->assertSame(
            Subscription::MIN_LEASE_SECONDS,
            $subscription->pending_lease_seconds
        );
    }

    public function testRenewFailsIfInvalidSecret(): void
    {
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $secret = str_repeat('a', 201);

        $subscription->renew(Subscription::DEFAULT_LEASE_SECONDS, $secret);
        $errors = $subscription->validate();

        $this->assertArrayHasKey('pending_secret', $errors);
    }

    public function testExpire(): void
    {
        $this->freeze();
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $subscription->verify();

        $expected_expired_at = \Minz\Time::fromNow(Subscription::DEFAULT_LEASE_SECONDS, 'seconds');
        $this->assertSame('verified', $subscription->status);
        $this->assertEquals($expected_expired_at, $subscription->expired_at);

        $this->freeze($expected_expired_at);

        $subscription->expire();

        $this->assertSame('expired', $subscription->status);
    }

    public function testExpireIfStatusIsNotVerified(): void
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage('Subscription cannot expire with new status.');

        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );

        $this->assertSame('new', $subscription->status);
        $this->assertNull($subscription->expired_at);

        $subscription->expire();
    }

    public function testExpireIfExpiredAtIsNotOver(): void
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage('Subscription expiration date is not over yet.');

        $this->freeze();
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $subscription->verify();

        $expected_expired_at = \Minz\Time::fromNow(Subscription::DEFAULT_LEASE_SECONDS, 'seconds');
        $this->assertEquals($expected_expired_at, $subscription->expired_at);

        $this->freeze($expected_expired_at->modify('-1 second'));

        $subscription->expire();
    }

    public function testShouldExpire(): void
    {
        $this->freeze();
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $subscription->verify();

        $expected_expired_at = \Minz\Time::fromNow(Subscription::DEFAULT_LEASE_SECONDS, 'seconds');
        $this->assertEquals($expected_expired_at, $subscription->expired_at);

        $this->freeze($expected_expired_at);

        $should_expire = $subscription->shouldExpire();

        $this->assertTrue($should_expire);
    }

    public function testShouldExpireIfExpiredAtIsNotOver(): void
    {
        $this->freeze();
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $subscription->verify();

        $expected_expired_at = \Minz\Time::fromNow(Subscription::DEFAULT_LEASE_SECONDS, 'seconds');
        $this->assertEquals($expected_expired_at, $subscription->expired_at);

        $this->freeze($expected_expired_at->modify('-1 second'));

        $should_expire = $subscription->shouldExpire();

        $this->assertFalse($should_expire);
    }

    public function testShouldExpireWithNoExpiredAt(): void
    {
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );

        $this->assertNull($subscription->expired_at);

        $should_expire = $subscription->shouldExpire();

        $this->assertFalse($should_expire);
    }

    public function testRequestUnsubscription(): void
    {
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );

        $this->assertSame('subscribe', $subscription->pending_request);

        $subscription->requestUnsubscription();

        $this->assertSame('unsubscribe', $subscription->pending_request);
    }

    public function testCancelRequestWithSubscribe(): void
    {
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );

        $this->assertSame('subscribe', $subscription->pending_request);

        $subscription->cancelRequest();

        $this->assertNull($subscription->pending_request);
    }

    public function testCancelRequestWithUnsubscribe(): void
    {
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $subscription->requestUnsubscription();

        $this->assertSame('unsubscribe', $subscription->pending_request);

        $subscription->cancelRequest();

        $this->assertNull($subscription->pending_request);
    }

    public function testCancelRequestWithPendingSecretAndLease(): void
    {
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            Subscription::DEFAULT_LEASE_SECONDS,
            'a secret'
        );
        $subscription->renew(Subscription::MIN_LEASE_SECONDS, 'another secret');

        $this->assertSame(
            Subscription::DEFAULT_LEASE_SECONDS,
            $subscription->lease_seconds
        );
        $this->assertSame(
            Subscription::MIN_LEASE_SECONDS,
            $subscription->pending_lease_seconds
        );
        $this->assertSame('a secret', $subscription->secret);
        $this->assertSame('another secret', $subscription->pending_secret);

        $subscription->cancelRequest();

        $this->assertSame(
            Subscription::DEFAULT_LEASE_SECONDS,
            $subscription->lease_seconds
        );
        $this->assertNull($subscription->pending_lease_seconds);
        $this->assertSame('a secret', $subscription->secret);
        $this->assertNull($subscription->pending_secret);
    }

    public function testIntentCallback(): void
    {
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            Subscription::DEFAULT_LEASE_SECONDS
        );
        $expected_callback = 'https://subscriber.com/callback?'
                           . 'hub.mode=subscribe&'
                           . 'hub.topic=https://some.site.fr/feed.xml&'
                           . 'hub.challenge=foobar&'
                           . 'hub.lease_seconds=' . Subscription::DEFAULT_LEASE_SECONDS;

        $intent_callback = $subscription->intentCallback('foobar');

        $this->assertSame($expected_callback, $intent_callback);
    }

    public function testIntentCallbackWithExistingParams(): void
    {
        $subscription = new Subscription(
            'https://subscriber.com/callback?baz=qux',
            'https://some.site.fr/feed.xml',
            Subscription::DEFAULT_LEASE_SECONDS
        );
        $expected_callback = 'https://subscriber.com/callback?'
                           . 'baz=qux&'
                           . 'hub.mode=subscribe&'
                           . 'hub.topic=https://some.site.fr/feed.xml&'
                           . 'hub.challenge=foobar&'
                           . 'hub.lease_seconds=' . Subscription::DEFAULT_LEASE_SECONDS;

        $intent_callback = $subscription->intentCallback('foobar');

        $this->assertSame($expected_callback, $intent_callback);
    }

    public function testIntentCallbackWithUnsubscribe(): void
    {
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            Subscription::DEFAULT_LEASE_SECONDS
        );
        $subscription->requestUnsubscription();
        $expected_callback = 'https://subscriber.com/callback?'
                           . 'hub.mode=unsubscribe&'
                           . 'hub.topic=https://some.site.fr/feed.xml&'
                           . 'hub.challenge=foobar';

        $intent_callback = $subscription->intentCallback('foobar');

        $this->assertSame($expected_callback, $intent_callback);
    }

    public function testIntentCallbackFailsIfPendingRequestIsNull(): void
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage(
            'intentCallback cannot be called when pending request is null.'
        );

        $subscription = new Subscription(
            'https://subscriber.com/callback?baz=qux',
            'https://some.site.fr/feed.xml',
            Subscription::DEFAULT_LEASE_SECONDS
        );
        $subscription->verify();

        $subscription->intentCallback('foobar');
    }

    public function testIntentCallbackFailsIfChallengeIsEmpty(): void
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage(
            'intentCallback cannot be called with an empty challenge.'
        );

        $subscription = new Subscription(
            'https://subscriber.com/callback?baz=qux',
            'https://some.site.fr/feed.xml',
            Subscription::DEFAULT_LEASE_SECONDS
        );

        $subscription->intentCallback('');
    }
}
