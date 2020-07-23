<?php

namespace Webubbub\models;

use PHPUnit\Framework\TestCase;

class SubscriptionTest extends TestCase
{
    use \Minz\Tests\TimeHelper;

    public function testNew()
    {
        $callback = 'https://subscriber.com/callback';
        $topic = 'https://some.site.fr/feed.xml';

        $subscription = Subscription::new($callback, $topic);

        $this->assertSame($callback, $subscription->callback);
        $this->assertSame($topic, $subscription->topic);
        $this->assertSame(
            Subscription::DEFAULT_LEASE_SECONDS,
            $subscription->lease_seconds
        );
        $this->assertSame('new', $subscription->status);
        $this->assertSame('subscribe', $subscription->pending_request);
    }

    public function testNewDecodesUrls()
    {
        $callback = 'https://subscriber.com/callback?foo%2Bbar';
        $topic = 'https://some.site.fr/feed.xml?foo%2Bbar';

        $subscription = Subscription::new($callback, $topic);

        $this->assertSame(
            'https://subscriber.com/callback?foo+bar',
            $subscription->callback
        );
        $this->assertSame(
            'https://some.site.fr/feed.xml?foo+bar',
            $subscription->topic
        );
    }

    public function testNewWithLeaseSeconds()
    {
        $lease_seconds = Subscription::DEFAULT_LEASE_SECONDS / 2;

        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            $lease_seconds
        );

        $this->assertSame($lease_seconds, $subscription->lease_seconds);
    }

    public function testNewForcesMinLeaseSeconds()
    {
        $lease_seconds = Subscription::MIN_LEASE_SECONDS - 42;

        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            $lease_seconds
        );

        $this->assertSame(
            Subscription::MIN_LEASE_SECONDS,
            $subscription->lease_seconds
        );
    }

    public function testNewForcesMaxLeaseSeconds()
    {
        $lease_seconds = Subscription::MAX_LEASE_SECONDS + 42;

        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            $lease_seconds
        );

        $this->assertSame(
            Subscription::MAX_LEASE_SECONDS,
            $subscription->lease_seconds
        );
    }

    public function testNewWithSecret()
    {
        $secret = 'a cryptographically random unique secret string';

        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            0,
            $secret
        );

        $this->assertSame($secret, $subscription->secret);
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testNewFailsIfCallbackIsInvalid($invalid_url)
    {
        $subscription = Subscription::new($invalid_url, 'https://some.site.fr/feed.xml');

        $errors = $subscription->validate();

        $this->assertArrayHasKey('callback', $errors);
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testNewFailsIfTopicIsInvalid($invalid_url)
    {
        $subscription = Subscription::new('https://subscriber.com/callback', $invalid_url);

        $errors = $subscription->validate();

        $this->assertArrayHasKey('topic', $errors);
    }

    public function testNewFailsIfSecretIsMoreThan200Bytes()
    {
        $secret = str_repeat('a', 201);

        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            0,
            $secret
        );

        $errors = $subscription->validate();

        $this->assertArrayHasKey('secret', $errors);
    }

    public function testVerify()
    {
        $this->freeze(1000);
        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            Subscription::MIN_LEASE_SECONDS
        );

        $this->assertSame('new', $subscription->status);
        $this->assertSame('subscribe', $subscription->pending_request);
        $this->assertNull($subscription->expired_at);

        $subscription->verify();

        $expected_expired_at = 1000 + Subscription::MIN_LEASE_SECONDS;
        $this->assertSame('verified', $subscription->status);
        $this->assertNull($subscription->pending_request);
        $this->assertSame(
            $expected_expired_at,
            $subscription->expired_at->getTimestamp()
        );
    }

    public function testVerifyTwiceFailsBecausePendingRequestIsNull()
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage(
            'Subscription cannot be verified because it has no pending requests.'
        );

        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $subscription->verify();

        $subscription->verify();
    }

    public function testVerifyAfterRenewAndDifferentLeaseSecondsAndSecret()
    {
        $this->freeze(1000);
        $subscription = Subscription::new(
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

    public function testRenew()
    {
        $subscription = Subscription::new(
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

    public function testRenewForcesLeaseSeconds()
    {
        $subscription = Subscription::new(
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

    public function testRenewFailsIfInvalidSecret()
    {
        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $secret = str_repeat('a', 201);

        $subscription->renew(Subscription::DEFAULT_LEASE_SECONDS, $secret);
        $errors = $subscription->validate();

        $this->assertArrayHasKey('pending_secret', $errors);
    }

    public function testExpire()
    {
        $this->freeze(1000);
        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $subscription->verify();

        $expected_expired_at = 1000 + Subscription::DEFAULT_LEASE_SECONDS;
        $this->assertSame('verified', $subscription->status);
        $this->assertSame($expected_expired_at, $subscription->expired_at->getTimestamp());

        $this->freeze($expected_expired_at);

        $subscription->expire();

        $this->assertSame('expired', $subscription->status);
    }

    public function testExpireIfStatusIsNotVerified()
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage('Subscription cannot expire with new status.');

        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );

        $this->assertSame('new', $subscription->status);
        $this->assertNull($subscription->expired_at);

        $subscription->expire();
    }

    public function testExpireIfExpiredAtIsNotOver()
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage('Subscription expiration date is not over yet.');

        $this->freeze(1000);
        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $subscription->verify();

        $expected_expired_at = 1000 + Subscription::DEFAULT_LEASE_SECONDS;
        $this->assertSame($expected_expired_at, $subscription->expired_at->getTimestamp());

        $this->freeze($expected_expired_at - 1);

        $subscription->expire();
    }

    public function testShouldExpire()
    {
        $this->freeze(1000);
        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $subscription->verify();

        $expected_expired_at = 1000 + Subscription::DEFAULT_LEASE_SECONDS;
        $this->assertSame($expected_expired_at, $subscription->expired_at->getTimestamp());

        $this->freeze($expected_expired_at);

        $should_expire = $subscription->shouldExpire();

        $this->assertTrue($should_expire);
    }

    public function testShouldExpireIfExpiredAtIsNotOver()
    {
        $this->freeze(1000);
        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $subscription->verify();

        $expected_expired_at = 1000 + Subscription::DEFAULT_LEASE_SECONDS;
        $this->assertSame($expected_expired_at, $subscription->expired_at->getTimestamp());

        $this->freeze($expected_expired_at - 1);

        $should_expire = $subscription->shouldExpire();

        $this->assertFalse($should_expire);
    }

    public function testShouldExpireWithNoExpiredAt()
    {
        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );

        $this->assertNull($subscription->expired_at);

        $should_expire = $subscription->shouldExpire();

        $this->assertFalse($should_expire);
    }

    public function testRequestUnsubscription()
    {
        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );

        $this->assertSame('subscribe', $subscription->pending_request);

        $subscription->requestUnsubscription();

        $this->assertSame('unsubscribe', $subscription->pending_request);
    }

    public function testCancelRequestWithSubscribe()
    {
        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );

        $this->assertSame('subscribe', $subscription->pending_request);

        $subscription->cancelRequest();

        $this->assertNull($subscription->pending_request);
    }

    public function testCancelRequestWithUnsubscribe()
    {
        $subscription = Subscription::new(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml'
        );
        $subscription->requestUnsubscription();

        $this->assertSame('unsubscribe', $subscription->pending_request);

        $subscription->cancelRequest();

        $this->assertNull($subscription->pending_request);
    }

    public function testCancelRequestWithPendingSecretAndLease()
    {
        $subscription = Subscription::new(
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

    public function testIntentCallback()
    {
        $subscription = Subscription::new(
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

    public function testIntentCallbackWithExistingParams()
    {
        $subscription = Subscription::new(
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

    public function testIntentCallbackWithUnsubscribe()
    {
        $subscription = Subscription::new(
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

    public function testIntentCallbackFailsIfPendingRequestIsNull()
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage(
            'intentCallback cannot be called when pending request is null.'
        );

        $subscription = Subscription::new(
            'https://subscriber.com/callback?baz=qux',
            'https://some.site.fr/feed.xml',
            Subscription::DEFAULT_LEASE_SECONDS
        );
        $subscription->verify();

        $subscription->intentCallback('foobar');
    }

    public function testIntentCallbackFailsIfChallengeIsEmpty()
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage(
            'intentCallback cannot be called with an empty challenge.'
        );

        $subscription = Subscription::new(
            'https://subscriber.com/callback?baz=qux',
            'https://some.site.fr/feed.xml',
            Subscription::DEFAULT_LEASE_SECONDS
        );

        $subscription->intentCallback('');
    }

    public function testConstructor()
    {
        $subscription = new Subscription([
            'id' => '1',
            'created_at' => '10000',
            'status' => 'new',
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'lease_seconds' => strval(Subscription::DEFAULT_LEASE_SECONDS),
        ]);

        $this->assertSame(1, $subscription->id);
        $this->assertSame(10000, $subscription->created_at->getTimestamp());
        $this->assertSame('new', $subscription->status);
        $this->assertSame('https://subscriber.com/callback', $subscription->callback);
        $this->assertSame('https://some.site.fr/feed.xml', $subscription->topic);
        $this->assertSame(Subscription::DEFAULT_LEASE_SECONDS, $subscription->lease_seconds);
    }

    /**
     * @dataProvider missingValuesProvider
     */
    public function testConstuctorFailsIfRequiredValueIsMissing($values, $missing_value_name)
    {
        $subscription = new Subscription($values);

        $errors = $subscription->validate();

        $this->assertArrayHasKey($missing_value_name, $errors);
    }

    public function testConstuctorFailsIfStatusIsInvalid()
    {
        $subscription = new Subscription([
            'id' => '1',
            'created_at' => '10000',
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'lease_seconds' => strval(Subscription::DEFAULT_LEASE_SECONDS),
            'status' => 'invalid',
        ]);

        $errors = $subscription->validate();

        $this->assertArrayHasKey('status', $errors);
    }

    public function testConstuctorFailsIfPendingRequestIsInvalid()
    {
        $subscription = new Subscription([
            'id' => '1',
            'created_at' => '10000',
            'status' => 'new',
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'lease_seconds' => strval(Subscription::DEFAULT_LEASE_SECONDS),
            'pending_request' => 'invalid',
        ]);

        $errors = $subscription->validate();

        $this->assertArrayHasKey('pending_request', $errors);
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

    public function missingValuesProvider()
    {
        $default_values = [
            'status' => 'new',
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'lease_seconds' => strval(Subscription::DEFAULT_LEASE_SECONDS),
        ];

        $dataset = [];
        foreach (array_keys($default_values) as $missing_value_name) {
            $values = $default_values;
            unset($values[$missing_value_name]);
            $dataset[] = [$values, $missing_value_name];
        }

        return $dataset;
    }
}
