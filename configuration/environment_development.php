<?php

return [
    'app_name' => 'Webubbub',
    'url_options' => [
        'host' => 'localhost',
        'port' => 8000,
    ],
    'database' => [
        'dsn' => "sqlite:{$app_path}/data/db.sqlite",
    ],
];
