<?php

namespace Webubbub\controllers\subscriptions;

use Minz\Tests\IntegrationTestCase;
use Webubbub\models;

class SubscriptionsTest extends IntegrationTestCase
{
    public static $challenge;

    public function setUp(): void
    {
        self::$challenge = 'foobar';
        \Webubbub\services\Curl::mock(self::$challenge);
    }

    public function tearDown(): void
    {
        \Webubbub\services\Curl::resetMock();
    }

    public function testVerifyWithSubscribePendingRequest()
    {
        $dao = new models\dao\Subscription();
        $id = self::$factories['subscriptions']->create([
            'status' => 'new',
            'pending_request' => 'subscribe',
        ]);

        $request = new \Minz\Request('CLI', '/subscriptions/verify');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('verified', $subscription['status']);
    }

    public function testVerifyWithUnsubscribePendingRequest()
    {
        $dao = new models\dao\Subscription();
        $id = self::$factories['subscriptions']->create([
            'pending_request' => 'unsubscribe',
        ]);

        $request = new \Minz\Request('CLI', '/subscriptions/verify');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertNull($subscription);
    }

    public function testVerifyWithoutPendingRequest()
    {
        $dao = new models\dao\Subscription();
        $id = self::$factories['subscriptions']->create([
            'status' => 'new',
            'pending_request' => null,
        ]);

        $request = new \Minz\Request('CLI', '/subscriptions/verify');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('new', $subscription['status']);
    }

    public function testVerifyWithSubscribeAndPendingSecretAndLeaseSeconds()
    {
        $dao = new models\dao\Subscription();
        $id = self::$factories['subscriptions']->create([
            'status' => 'verified',
            'lease_seconds' => models\Subscription::DEFAULT_LEASE_SECONDS,
            'secret' => 'a secret',
            'pending_request' => 'subscribe',
            'pending_lease_seconds' => models\Subscription::MIN_LEASE_SECONDS,
            'pending_secret' => 'another secret',
        ]);

        $request = new \Minz\Request('CLI', '/subscriptions/verify');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame(
            models\Subscription::MIN_LEASE_SECONDS,
            intval($subscription['lease_seconds'])
        );
        $this->assertSame('another secret', $subscription['secret']);
        $this->assertNull($subscription['pending_lease_seconds']);
        $this->assertNull($subscription['pending_secret']);
    }

    public function testVerifyWithSubscribeAndUnmatchingChallenge()
    {
        $dao = new models\dao\Subscription();
        $id = self::$factories['subscriptions']->create([
            'status' => 'new',
            'pending_request' => 'subscribe',
        ]);

        \Webubbub\services\Curl::mock('not the correct challenge');

        $request = new \Minz\Request('CLI', '/subscriptions/verify');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('new', $subscription['status']);
        $this->assertNull($subscription['pending_request']);
    }

    public function testVerifyWithUnsubscribeAndUnmatchingChallenge()
    {
        $dao = new models\dao\Subscription();
        $id = self::$factories['subscriptions']->create([
            'status' => 'new',
            'pending_request' => 'unsubscribe',
        ]);

        \Webubbub\services\Curl::mock('not the correct challenge');

        $request = new \Minz\Request('CLI', '/subscriptions/verify');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('new', $subscription['status']);
        $this->assertNull($subscription['pending_request']);
    }

    /**
     * @dataProvider failingHttpCodeProvider
     */
    public function testVerifyWithSubscribeAndNonSuccessHttpCode($http_code)
    {
        $dao = new models\dao\Subscription();
        $id = self::$factories['subscriptions']->create([
            'status' => 'new',
            'pending_request' => 'subscribe',
        ]);

        \Webubbub\services\Curl::mock(self::$challenge, $http_code);

        $request = new \Minz\Request('CLI', '/subscriptions/verify');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('new', $subscription['status']);
        $this->assertNull($subscription['pending_request']);
    }

    /**
     * @dataProvider failingHttpCodeProvider
     */
    public function testVerifyWithUnsubscribeAndNonSuccessHttpCode($http_code)
    {
        $dao = new models\dao\Subscription();
        $id = self::$factories['subscriptions']->create([
            'status' => 'new',
            'pending_request' => 'unsubscribe',
        ]);

        \Webubbub\services\Curl::mock(self::$challenge, $http_code);

        $request = new \Minz\Request('CLI', '/subscriptions/verify');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('new', $subscription['status']);
        $this->assertNull($subscription['pending_request']);
    }

    public function testExpire()
    {
        $dao = new models\dao\Subscription();
        $id = self::$factories['subscriptions']->create([
            'expired_at' => time(),
            'status' => 'verified',
        ]);
        $request = new \Minz\Request('CLI', '/subscriptions/expire');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('expired', $subscription['status']);
    }

    public function testExpireWithExpiredDateInFuture()
    {
        $dao = new models\dao\Subscription();
        $id = self::$factories['subscriptions']->create([
            'expired_at' => time() + 1000,
            'status' => 'verified',
        ]);
        $request = new \Minz\Request('CLI', '/subscriptions/expire');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('verified', $subscription['status']);
    }

    public function testItems()
    {
        $dao = new models\dao\Subscription();
        self::$factories['subscriptions']->create([
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
        ]);
        $request = new \Minz\Request('CLI', '/subscriptions');

        $response = self::$application->run($request);

        $output = $response->render();
        $this->assertResponse($response, 200);
        $this->assertStringContainsString('https://subscriber.com/callback', $output);
        $this->assertStringContainsString('https://some.site.fr/feed.xml', $output);
    }

    public function failingHttpCodeProvider()
    {
        return [
            [301], [302], [307], [308],
            [400], [401], [404], [410],
            [500], [503], [504],
        ];
    }
}

/**
 * Override sha1() in current namespace for testing.
 *
 * @see https://www.schmengler-se.de/en/2011/03/php-mocking-built-in-functions-like-time-in-unit-tests/
 * @see https://www.php.net/manual/fr/function.sha1.php
 *
 * @return string
 */
function sha1()
{
    return SubscriptionsTest::$challenge;
}
