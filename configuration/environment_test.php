<?php

return [
    'app_name' => 'Webubbub',
    'url_options' => [
        'host' => 'localhost',
    ],
    'database' => [
        'dsn' => 'sqlite::memory:',
    ],
    'no_syslog' => !getenv('APP_SYSLOG_ENABLED'),
];
