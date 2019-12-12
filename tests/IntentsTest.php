<?php

namespace Webubbub\controllers\intents;

use Minz\Tests\ActionControllerTestCase;
use Webubbub\models;

class IntentsTest extends ActionControllerTestCase
{
    public static $challenge;
    public static $subscriber_challenge;
    public static $subscriber_http_code;

    private static $schema;

    public static function setUpBeforeClass(): void
    {
        self::includeController();

        $configuration_path = \Minz\Configuration::$configuration_path;
        self::$schema = file_get_contents($configuration_path . '/schema.sql');
    }

    public function setUp(): void
    {
        $database = \Minz\Database::get();
        $database->exec(self::$schema);

        self::$challenge = 'foobar';
        self::$subscriber_challenge = 'foobar';
        self::$subscriber_http_code = 200;
    }

    public function tearDown(): void
    {
        \Minz\Database::drop();
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

        $response = verify($request);

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

        $response = verify($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        // TODO here, the subscription should be null... but we don't support
        // unsubscription yet!
        $this->assertSame('new', $subscription['status']);
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

        $response = verify($request);

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
        self::$subscriber_challenge = 'not the correct challenge';

        $request = new \Minz\Request('CLI', '/intents/verify');

        $response = verify($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertNull($subscription);
    }

    /**
     * @dataProvider failingHttpCodeProvider
     */
    public function testVerifyWithNonSuccessHttpCode($http_code)
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
        self::$subscriber_http_code = $http_code;

        $request = new \Minz\Request('CLI', '/intents/verify');

        $response = verify($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertNull($subscription);
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

// Berk :x

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

/**
 * Override curl_exec() in current namespace for testing.
 *
 * @see https://www.schmengler-se.de/en/2011/03/php-mocking-built-in-functions-like-time-in-unit-tests/
 * @see https://www.php.net/manual/fr/function.curl-exec.php
 *
 * @return mixed
 */
function curl_exec()
{
    return \Webubbub\controllers\intents\IntentsTest::$subscriber_challenge;
}

/**
 * Override curl_getinfo() in current namespace for testing.
 *
 * @see https://www.schmengler-se.de/en/2011/03/php-mocking-built-in-functions-like-time-in-unit-tests/
 * @see https://www.php.net/manual/fr/function.curl-getinfo.php
 *
 * @param resource $curl_resource
 * @param int $opt
 *
 * @return mixed
 */
function curl_getinfo($curl_resource, $opt)
{
    if ($opt === CURLINFO_RESPONSE_CODE) {
        return \Webubbub\controllers\intents\IntentsTest::$subscriber_http_code;
    } else {
        \curl_getinfo($curl_resource, $opt);
    }
}
