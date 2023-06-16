<?php

namespace Webubbub\jobs;

use Webubbub\models;
use tests\factories\ContentFactory;
use tests\factories\SubscriptionFactory;

class CleanerTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    public function testCleanDeletesOldExpiredData(): void
    {
        $old_date = \Minz\Time::ago(2, 'weeks');
        $content = ContentFactory::create([
            'status' => 'delivered',
        ]);
        $old_new_subscription = SubscriptionFactory::create([
            'status' => 'new',
            'created_at' => $old_date,
        ]);
        $old_validated_subscription = SubscriptionFactory::create([
            'status' => 'validated',
            'created_at' => $old_date,
        ]);
        $old_expired_subscription = SubscriptionFactory::create([
            'status' => 'expired',
            'expired_at' => $old_date,
        ]);

        $cleaner = new Cleaner();
        $cleaner->perform();

        $this->assertFalse(models\Content::exists($content->id));
        $this->assertFalse(models\Subscription::exists($old_new_subscription->id));
        $this->assertFalse(models\Subscription::exists($old_validated_subscription->id));
        $this->assertFalse(models\Subscription::exists($old_expired_subscription->id));
    }

    public function testCleanKeepsRecentExpiredData(): void
    {
        $recent_date = \Minz\Time::ago(1, 'weeks');
        $old_new_subscription = SubscriptionFactory::create([
            'status' => 'new',
            'created_at' => $recent_date,
        ]);
        $old_validated_subscription = SubscriptionFactory::create([
            'status' => 'validated',
            'created_at' => $recent_date,
        ]);
        $old_expired_subscription = SubscriptionFactory::create([
            'status' => 'expired',
            'expired_at' => $recent_date,
        ]);

        $cleaner = new Cleaner();
        $cleaner->perform();

        $this->assertTrue(models\Subscription::exists($old_new_subscription->id));
        $this->assertTrue(models\Subscription::exists($old_validated_subscription->id));
        $this->assertTrue(models\Subscription::exists($old_expired_subscription->id));
    }

    public function testCleanKeepsNonExpiredData(): void
    {
        $new_content = ContentFactory::create([
            'status' => 'new',
        ]);
        $fetched_content = ContentFactory::create([
            'status' => 'fetched',
        ]);
        $verified_subscription = SubscriptionFactory::create([
            'status' => 'verified',
        ]);

        $cleaner = new Cleaner();
        $cleaner->perform();

        $this->assertTrue(models\Content::exists($new_content->id));
        $this->assertTrue(models\Content::exists($fetched_content->id));
        $this->assertTrue(models\Subscription::exists($verified_subscription->id));
    }
}
