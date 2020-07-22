<?php

namespace Webubbub\models;

use PHPUnit\Framework\TestCase;

class ContentDeliveryTest extends TestCase
{
    use \Minz\Tests\TimeHelper;

    public function testNew()
    {
        $this->freeze(1000);

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
        $content_delivery = new ContentDelivery($values);

        $errors = $content_delivery->validate();

        $this->assertArrayHasKey($missing_value_name, $errors);
    }

    /**
     * @dataProvider triesToDelayProvider
     */
    public function testRetryLater($initial_tries, $delay)
    {
        $this->freeze(2000);

        $content = new ContentDelivery([
            'subscription_id' => 1,
            'content_id' => 1,
            'try_at' => 1000,
            'tries_count' => $initial_tries,
        ]);

        $content->retryLater();

        $this->assertSame(2000 + $delay, $content->try_at->getTimestamp());
        $this->assertSame($initial_tries + 1, $content->tries_count);
    }

    public function testRetryLaterFailsIfMaxTriesReached()
    {
        $this->expectException(Errors\ContentDeliveryError::class);
        $this->expectExceptionMessage(
            'Content delivery has reached the maximum of allowed number of tries (7).'
        );

        $content = new ContentDelivery([
            'subscription_id' => 1,
            'content_id' => 1,
            'try_at' => 1000,
            'tries_count' => 7,
        ]);

        $content->retryLater();
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

    public function triesToDelayProvider()
    {
        return [
            [0, 5], // when initial number of tries is 0, try_at will be incremented
                    // by 5 seconds from now.
            [1, 25],
            [2, 125], // ~2 minutes
            [3, 625], // ~10 minutes
            [4, 3125], // ~52 minutes
            [5, 15625], // ~4 hours 20 minutes
            [6, 78125], // ~21 hours 42 minutes
        ];
    }
}
