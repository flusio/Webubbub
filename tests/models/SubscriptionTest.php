<?php

namespace Webubbub\models;

use PHPUnit\Framework\TestCase;

/**
 * Override time() in current namespace for testing. It's a bit hacky, but
 * it'll be good enough for now.
 *
 * @see https://www.schmengler-se.de/en/2011/03/php-mocking-built-in-functions-like-time-in-unit-tests/
 *
 * @return int
 */
function time()
{
    if (SubscriptionTest::$now) {
        return SubscriptionTest::$now;
    } else {
        return \time();
    }
}

class SubscriptionTest extends TestCase
{
    public static $now;

    public function tearDown(): void
    {
        self::$now = null;
    }

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
        $this->assertSame('subscribe', $subscription->pendingRequest());
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

    public function testVerify()
    {
        self::$now = 1000;
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
            Subscription::MIN_LEASE_SECONDS,
        );

        $this->assertSame('new', $subscription->status());
        $this->assertSame('subscribe', $subscription->pendingRequest());
        $this->assertNull($subscription->expiredAt());

        $subscription->verify();

        $expected_expired_at = self::$now + Subscription::MIN_LEASE_SECONDS;
        $this->assertSame('verified', $subscription->status());
        $this->assertNull($subscription->pendingRequest());
        $this->assertSame(
            $expected_expired_at,
            $subscription->expiredAt()->getTimestamp()
        );
    }

    public function testVerifyTwiceFailsBecausePendingRequestIsNull()
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage(
            'Subscription cannot be verified because it has no pending requests.'
        );

        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
        );
        $subscription->verify();

        $subscription->verify();
    }

    public function testRequestUnsubscription()
    {
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
        );

        $this->assertSame('subscribe', $subscription->pendingRequest());

        $subscription->requestUnsubscription();

        $this->assertSame('unsubscribe', $subscription->pendingRequest());
    }

    public function testCancelUnsubscriptionRequest()
    {
        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
        );
        $subscription->requestUnsubscription();

        $this->assertSame('unsubscribe', $subscription->pendingRequest());

        $subscription->cancelUnsubscription();

        $this->assertNull($subscription->pendingRequest());
    }

    public function testCancelUnsubscriptionRequestFailsIfPendingIsSubscribe()
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage(
            'Cannot cancel unsubscription because pending request is subscribe.'
        );

        $subscription = new Subscription(
            'https://subscriber.com/callback',
            'https://some.site.fr/feed.xml',
        );

        $this->assertSame('subscribe', $subscription->pendingRequest());

        $subscription->cancelUnsubscription();
    }

    public function testIntentCallback()
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

    public function testIntentCallbackWithExistingParams()
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

    public function testIntentCallbackWithUnsubscribe()
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

    public function testIntentCallbackFailsIfPendingRequestIsNull()
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

    public function testIntentCallbackFailsIfChallengeIsEmpty()
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

    public function testFromValues()
    {
        $subscription = Subscription::fromValues([
            'id' => '1',
            'created_at' => '10000',
            'status' => 'new',
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'lease_seconds' => strval(Subscription::DEFAULT_LEASE_SECONDS),
        ]);

        $this->assertSame(1, $subscription->id());
        $this->assertSame(10000, $subscription->createdAt()->getTimestamp());
        $this->assertSame('new', $subscription->status());
        $this->assertSame('https://subscriber.com/callback', $subscription->callback());
        $this->assertSame('https://some.site.fr/feed.xml', $subscription->topic());
        $this->assertSame(Subscription::DEFAULT_LEASE_SECONDS, $subscription->leaseSeconds());
    }

    /**
     * @dataProvider missingValuesProvider
     */
    public function testFromValuesFailsIfRequiredValueIsMissing($values, $missing_value_name)
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage("{$missing_value_name} value is required.");

        $subscription = Subscription::fromValues($values);
    }

    /**
     * @dataProvider integerValuesNamesProvider
     */
    public function testFromValuesFailsIfIntegerValueCannotBeParsed($value_name)
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage("{$value_name} value must be an integer.");

        $values = [
            'id' => '1',
            'created_at' => '10000',
            'status' => 'new',
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'lease_seconds' => strval(Subscription::DEFAULT_LEASE_SECONDS),
        ];
        $values[$value_name] = 'not an integer';

        $subscription = Subscription::fromValues($values);
    }

    public function testFromValuesFailsIfStatusIsInvalid()
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage('invalid is not a valid status.');

        $subscription = Subscription::fromValues([
            'id' => '1',
            'created_at' => '10000',
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'lease_seconds' => strval(Subscription::DEFAULT_LEASE_SECONDS),
            'status' => 'invalid',
        ]);
    }

    public function testFromValuesFailsIfPendingRequestIsInvalid()
    {
        $this->expectException(Errors\SubscriptionError::class);
        $this->expectExceptionMessage('invalid is not a valid pending request.');

        $subscription = Subscription::fromValues([
            'id' => '1',
            'created_at' => '10000',
            'status' => 'new',
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'lease_seconds' => strval(Subscription::DEFAULT_LEASE_SECONDS),
            'pending_request' => 'invalid',
        ]);
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

    public function integerValuesNamesProvider()
    {
        return [
            ['id'],
            ['lease_seconds'],
            ['created_at'],
            ['expired_at'],
        ];
    }

    public function missingValuesProvider()
    {
        $default_values = [
            'id' => '1',
            'created_at' => '10000',
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
