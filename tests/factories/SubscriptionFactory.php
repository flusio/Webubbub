<?php

namespace tests\factories;

use Minz\Database;
use Webubbub\models;

/**
 * @extends Database\Factory<models\Subscription>
 */
class SubscriptionFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Subscription::class;
    }

    public static function values(): array
    {
        return [
            'callback' => 'https://subscriber.com/callback',

            'topic' => 'https://some-site.com/feed.xml',

            'created_at' => function (): \DateTimeImmutable {
                return \Minz\Time::now();
            },

            'status' => 'new',

            'lease_seconds' => 432000,
        ];
    }
}
