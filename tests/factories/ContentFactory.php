<?php

namespace tests\factories;

use Minz\Database;
use Webubbub\models;

/**
 * @extends Database\Factory<models\Content>
 */
class ContentFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Content::class;
    }

    public static function values(): array
    {
        return [
            'url' => 'https://some-site.com/feed.xml',

            'created_at' => function () {
                return \Minz\Time::now();
            },

            'status' => 'new',
        ];
    }
}
