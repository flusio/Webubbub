<?php

return [
    'app_name' => 'Webubbub',

    'secret_key' => 'change-me',

    'url_options' => [
        'host' => 'localhost',
    ],

    'application' => [
        'allowed_topic_origins' => '',
    ],

    'database' => [
        'dsn' => 'sqlite::memory:',
    ],

    'no_syslog_output' => true,
];
