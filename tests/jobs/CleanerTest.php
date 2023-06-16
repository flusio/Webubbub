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
        $old_content = ContentFactory::create([
            'status' => 'new',
            'created_at' => $old_date,
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
        $this->assertFalse(models\Content::exists($old_content->id));
        $this->assertFalse(models\Subscription::exists($old_new_subscription->id));
        $this->assertFalse(models\Subscription::exists($old_validated_subscription->id));
        $this->assertFalse(models\Subscription::exists($old_expired_subscription->id));
    }

    public function testCleanKeepsRecentExpiredData(): void
    {
        $recent_date = \Minz\Time::ago(1, 'week');
        $recent_content = ContentFactory::create([
            'status' => 'new',
            'created_at' => $recent_date,
        ]);
        $recent_new_subscription = SubscriptionFactory::create([
            'status' => 'new',
            'created_at' => $recent_date,
        ]);
        $recent_validated_subscription = SubscriptionFactory::create([
            'status' => 'validated',
            'created_at' => $recent_date,
        ]);
        $recent_expired_subscription = SubscriptionFactory::create([
            'status' => 'expired',
            'expired_at' => $recent_date,
        ]);

        $cleaner = new Cleaner();
        $cleaner->perform();

        $this->assertTrue(models\Content::exists($recent_content->id));
        $this->assertTrue(models\Subscription::exists($recent_new_subscription->id));
        $this->assertTrue(models\Subscription::exists($recent_validated_subscription->id));
        $this->assertTrue(models\Subscription::exists($recent_expired_subscription->id));
    }

    public function testCleanKeepsNonExpiredData(): void
    {
        $old_date = \Minz\Time::ago(2, 'weeks');
        $verified_subscription = SubscriptionFactory::create([
            'status' => 'verified',
            'created_at' => $old_date,
        ]);

        $cleaner = new Cleaner();
        $cleaner->perform();

        $this->assertTrue(models\Subscription::exists($verified_subscription->id));
    }
}
