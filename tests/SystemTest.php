<?php

namespace Webubbub;

class SystemTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\TimeHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testCleanDeletesOldExpiredData()
    {
        $content_dao = new models\dao\Content();
        $subscription_dao = new models\dao\Subscription();
        $old_date = \Minz\Time::ago(2, 'weeks');
        $content_id = $this->create('contents', [
            'status' => 'delivered',
        ]);
        $old_new_subscription_id = $this->create('subscriptions', [
            'status' => 'new',
            'created_at' => $old_date->getTimestamp(),
        ]);
        $old_validated_subscription_id = $this->create('subscriptions', [
            'status' => 'validated',
            'created_at' => $old_date->getTimestamp(),
        ]);
        $old_expired_subscription_id = $this->create('subscriptions', [
            'status' => 'expired',
            'expired_at' => $old_date->getTimestamp(),
        ]);

        $response = $this->appRun('cli', '/system/clean');

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '3 subscriptions deleted, 1 contents deleted');
        $this->assertFalse($content_dao->exists($content_id));
        $this->assertFalse($subscription_dao->exists($old_new_subscription_id));
        $this->assertFalse($subscription_dao->exists($old_validated_subscription_id));
        $this->assertFalse($subscription_dao->exists($old_expired_subscription_id));
    }

    public function testCleanKeepsRecentExpiredData()
    {
        $subscription_dao = new models\dao\Subscription();
        $recent_date = \Minz\Time::ago(1, 'weeks');
        $old_new_subscription_id = $this->create('subscriptions', [
            'status' => 'new',
            'created_at' => $recent_date->getTimestamp(),
        ]);
        $old_validated_subscription_id = $this->create('subscriptions', [
            'status' => 'validated',
            'created_at' => $recent_date->getTimestamp(),
        ]);
        $old_expired_subscription_id = $this->create('subscriptions', [
            'status' => 'expired',
            'expired_at' => $recent_date->getTimestamp(),
        ]);

        $response = $this->appRun('cli', '/system/clean');

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '0 subscriptions deleted, 0 contents deleted');
        $this->assertTrue($subscription_dao->exists($old_new_subscription_id));
        $this->assertTrue($subscription_dao->exists($old_validated_subscription_id));
        $this->assertTrue($subscription_dao->exists($old_expired_subscription_id));
    }

    public function testCleanKeepsNonExpiredData()
    {
        $content_dao = new models\dao\Content();
        $subscription_dao = new models\dao\Subscription();
        $new_content_id = $this->create('contents', [
            'status' => 'new',
        ]);
        $fetched_content_id = $this->create('contents', [
            'status' => 'fetched',
        ]);
        $verified_subscription_id = $this->create('subscriptions', [
            'status' => 'verified',
        ]);

        $response = $this->appRun('cli', '/system/clean');

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '0 subscriptions deleted, 0 contents deleted');
        $this->assertTrue($content_dao->exists($new_content_id));
        $this->assertTrue($content_dao->exists($fetched_content_id));
        $this->assertTrue($subscription_dao->exists($verified_subscription_id));
    }
}
