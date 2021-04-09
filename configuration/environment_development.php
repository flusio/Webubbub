<?php

return [
    'app_name' => 'Webubbub',

    'secret_key' => $dotenv->pop('APP_SECRET_KEY'),

    'url_options' => [
        'host' => $dotenv->pop('APP_HOST'),
        'port' => intval($dotenv->pop('APP_PORT')),
    ],

    'application' => [
        'allowed_topic_origins' => $dotenv->pop('ALLOWED_TOPIC_ORIGINS', ''),
    ],

    'database' => [
        'dsn' => 'sqlite:' . $dotenv->pop('DB_PATH', "{$app_path}/data/db.sqlite"),
    ],
];
