<?php

return [
    'app_name' => 'Webubbub',

    'secret_key' => $dotenv->pop('APP_SECRET_KEY'),

    'url_options' => [
        'protocol' => 'https',
        'host' => $dotenv->pop('APP_HOST'),
        'port' => intval($dotenv->pop('APP_PORT', '443')),
    ],

    'database' => [
        'dsn' => 'sqlite:' . $dotenv->pop('DB_PATH', "{$app_path}/data/db.sqlite"),
    ],
];
