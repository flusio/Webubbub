<?php

return [
    'app_name' => 'Webubbub',
    'database' => [
        'dsn' => 'sqlite::memory:',
    ],
    'no_syslog' => !getenv('APP_SYSLOG_ENABLED'),
];
