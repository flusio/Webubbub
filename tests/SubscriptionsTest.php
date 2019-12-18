<?php

namespace Webubbub\controllers\subscriptions;

use Minz\Tests\ActionControllerTestCase;
use Webubbub\models;

class SubscriptionsTest extends ActionControllerTestCase
{
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
    }

    public function tearDown(): void
    {
        \Minz\Database::drop();
    }

    public function testExpire()
    {
        $dao = new models\dao\Subscription();
        $id = $dao->create([
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'lease_seconds' => 432000,
            'created_at' => time(),
            'expired_at' => time(),
            'status' => 'verified',
        ]);
        $request = new \Minz\Request('CLI', '/subscriptions/expire');

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 200);
        $this->assertSame('expired', $subscription['status']);
    }

    public function testExpireWithNoExpiredDate()
    {
        $dao = new models\dao\Subscription();
        $id = $dao->create([
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'lease_seconds' => 432000,
            'created_at' => time(),
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
        $dao->create([
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'created_at' => time(),
            'status' => 'new',
            'lease_seconds' => 432000,
        ]);
        $request = new \Minz\Request('CLI', '/subscriptions');

        $response = self::$application->run($request);

        $output = $response->render();
        $this->assertResponse($response, 200);
        $this->assertStringContainsString('https://subscriber.com/callback', $output);
        $this->assertStringContainsString('https://some.site.fr/feed.xml', $output);
    }
}
