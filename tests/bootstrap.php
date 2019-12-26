<?php

$app_path = realpath(__DIR__ . '/..');

include $app_path . '/autoload.php';

\Minz\Configuration::load('test', $app_path);
\Minz\Environment::initialize();

// Initialize factories
\Minz\Tests\DatabaseFactory::addFactory(
    'subscriptions',
    '\Webubbub\models\dao\Subscription',
    [
        'callback' => 'https://subscriber.com/callback',
        'topic' => 'https://some-site.com/feed.xml',
        'created_at' => time(),
        'status' => 'new',
        'lease_seconds' => 432000,
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'contents',
    '\Webubbub\models\dao\Content',
    [
        'url' => 'https://some-site.com/feed.xml',
        'created_at' => time(),
        'status' => 'new',
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'content_deliveries',
    '\Webubbub\models\dao\ContentDelivery',
    [
        'subscription_id' => function () {
            $subscriptions_factory = new \Minz\Tests\DatabaseFactory('subscriptions');
            return $subscriptions_factory->create();
        },
        'content_id' => function () {
            $contents_factory = new \Minz\Tests\DatabaseFactory('contents');
            return $contents_factory->create();
        },
        'created_at' => time(),
        'try_at' => time(),
        'tries_count' => 0,
    ]
);
