<?php

return [
    'app_name' => 'AppTest',
    'url_options' => [
        'host' => 'localhost',
    ],
    'database' => [
        'dsn' => 'sqlite::memory:',
    ],
    'no_syslog' => !getenv('APP_SYSLOG_ENABLED'),
];
