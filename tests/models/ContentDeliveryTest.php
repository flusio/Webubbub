<?php

namespace Webubbub\models;

use PHPUnit\Framework\TestCase;

class ContentDeliveryTest extends TestCase
{
    public function tearDown(): void
    {
        \Minz\Time::unfreeze();
    }

    public function testNew()
    {
        \Minz\Time::freeze(1000);

        $subscription_id = 2;
        $content_id = 3;

        $content_delivery = ContentDelivery::new($subscription_id, $content_id);

        $this->assertSame($subscription_id, $content_delivery->subscription_id);
        $this->assertSame($content_id, $content_delivery->content_id);
        $this->assertSame(1000, $content_delivery->try_at->getTimestamp());
        $this->assertSame(0, $content_delivery->tries_count);
    }

    public function testConstructor()
    {
        $content = new ContentDelivery([
            'id' => '1',
            'subscription_id' => '2',
            'content_id' => '3',
            'created_at' => '10000',
            'try_at' => '1000',
            'tries_count' => '0',
        ]);

        $this->assertSame(1, $content->id);
        $this->assertSame(2, $content->subscription_id);
        $this->assertSame(3, $content->content_id);
        $this->assertSame(10000, $content->created_at->getTimestamp());
        $this->assertSame(1000, $content->try_at->getTimestamp());
        $this->assertSame(0, $content->tries_count);
    }

    /**
     * @dataProvider missingValuesProvider
     */
    public function testConstructorFailsIfRequiredValueIsMissing($values, $missing_value_name)
    {
        $this->expectException(\Minz\Errors\ModelPropertyError::class);
        $this->expectExceptionMessage(
            "Required `{$missing_value_name}` property is missing."
        );

        new ContentDelivery($values);
    }

    public function missingValuesProvider()
    {
        $default_values = [
            'subscription_id' => '2',
            'content_id' => '3',
            'try_at' => '1000',
            'tries_count' => '0',
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
