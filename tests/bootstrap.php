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
