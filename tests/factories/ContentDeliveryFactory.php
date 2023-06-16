<?php

namespace tests\factories;

use Minz\Database;
use Webubbub\models;

/**
 * @extends Database\Factory<models\ContentDelivery>
 */
class ContentDeliveryFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\ContentDelivery::class;
    }

    public static function values(): array
    {
        return [
            'subscription_id' => function () {
                return SubscriptionFactory::create()->id;
            },

            'content_id' => function () {
                return ContentFactory::create()->id;
            },

            'created_at' => function () {
                return \Minz\Time::now();
            },

            'try_at' => function () {
                return \Minz\Time::now();
            },

            'tries_count' => 0,
        ];
    }
}
