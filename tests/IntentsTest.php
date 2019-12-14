<?php

namespace Webubbub\controllers\intents;

use Minz\Tests\IntegrationTestCase;
use Webubbub\models;

class IntentsTest extends IntegrationTestCase
{
    public static $challenge;

    private static $application;
    private static $schema;

    public static function setUpBeforeClass(): void
    {
        $configuration_path = \Minz\Configuration::$configuration_path;
        self::$schema = file_get_contents($configuration_path . '/schema.sql');

        self::$application = new \Webubbub\Application();
    }

    public function setUp(): void
    {
        $database = \Minz\Database::get();
        $database->exec(self::$schema);

        self::$challenge = 'foobar';
        \Webubbub\services\Curl::mock(self::$challenge);
    }

    public function tearDown(): void
    {
        \Minz\Database::drop();
        \Webubbub\services\Curl::resetMock();
    }

    public function testVerifyWithSubscribePendingRequest()
    {
        $dao = new models\dao\Subscription();
        $id = $dao->create([
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'created_at' => time(),
            'status' => 'new',
            'lease_seconds' => 432000,
            'pending_request' => 'subscribe',
        ]);

        $request = new \Minz\Request('CLI', '/intents/verify');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('verified', $subscription['status']);
    }

    public function testVerifyWithUnsubscribePendingRequest()
    {
        $dao = new models\dao\Subscription();
        $id = $dao->create([
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'created_at' => time(),
            'status' => 'new',
            'lease_seconds' => 432000,
            'pending_request' => 'unsubscribe',
        ]);

        $request = new \Minz\Request('CLI', '/intents/verify');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertNull($subscription);
    }

    public function testVerifyWithoutPendingRequest()
    {
        $dao = new models\dao\Subscription();
        $id = $dao->create([
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'created_at' => time(),
            'status' => 'new',
            'lease_seconds' => 432000,
            'pending_request' => null,
        ]);

        $request = new \Minz\Request('CLI', '/intents/verify');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('new', $subscription['status']);
    }

    public function testVerifyWithSubscribeAndUnmatchingChallenge()
    {
        $dao = new models\dao\Subscription();
        $id = $dao->create([
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'created_at' => time(),
            'status' => 'new',
            'lease_seconds' => 432000,
            'pending_request' => 'subscribe',
        ]);

        \Webubbub\services\Curl::mock('not the correct challenge');

        $request = new \Minz\Request('CLI', '/intents/verify');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('new', $subscription['status']);
        $this->assertNull($subscription['pending_request']);
    }

    public function testVerifyWithUnsubscribeAndUnmatchingChallenge()
    {
        $dao = new models\dao\Subscription();
        $id = $dao->create([
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'created_at' => time(),
            'status' => 'new',
            'lease_seconds' => 432000,
            'pending_request' => 'unsubscribe',
        ]);

        \Webubbub\services\Curl::mock('not the correct challenge');

        $request = new \Minz\Request('CLI', '/intents/verify');

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
        $id = $dao->create([
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'created_at' => time(),
            'status' => 'new',
            'lease_seconds' => 432000,
            'pending_request' => 'subscribe',
        ]);

        \Webubbub\services\Curl::mock(self::$challenge, $http_code);

        $request = new \Minz\Request('CLI', '/intents/verify');

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
        $id = $dao->create([
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'created_at' => time(),
            'status' => 'new',
            'lease_seconds' => 432000,
            'pending_request' => 'unsubscribe',
        ]);

        \Webubbub\services\Curl::mock(self::$challenge, $http_code);

        $request = new \Minz\Request('CLI', '/intents/verify');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('new', $subscription['status']);
        $this->assertNull($subscription['pending_request']);
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

namespace Webubbub\services;

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
    return \Webubbub\controllers\intents\IntentsTest::$challenge;
}
