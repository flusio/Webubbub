<?php

return [
    'app_name' => 'Webubbub',

    'secret_key' => $dotenv->pop('APP_SECRET_KEY'),

    'url_options' => [
        'protocol' => 'https',
        'host' => $dotenv->pop('APP_HOST'),
        'port' => intval($dotenv->pop('APP_PORT', '443')),
    ],

    'application' => [
        'allowed_topic_origins' => $dotenv->pop('ALLOWED_TOPIC_ORIGINS', ''),
    ],

    'database' => [
        'dsn' => 'sqlite:' . $dotenv->pop('DB_PATH', "{$app_path}/data/db.sqlite"),
    ],
];
