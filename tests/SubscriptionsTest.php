<?php

namespace Webubbub\controllers\subscriptions;

use Minz\Tests\IntegrationTestCase;
use Webubbub\models;

class SubscriptionsTest extends IntegrationTestCase
{
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
}
