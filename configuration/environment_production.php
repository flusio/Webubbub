<?php

$database_path = getenv('APP_DATABASE_PATH');
if (!$database_path) {
    $database_path = "{$app_path}/data/db.sqlite";
}

$url_port = intval(getenv('APP_URL_PORT'));
if (!$url_port) {
    $url_port = 443;
}

$url_path = getenv('APP_URL_PATH');
if (!$url_path) {
    $url_path = '/';
}

$url_protocol = getenv('APP_URL_PROTOCOL');
if (!$url_protocol) {
    $url_protocol = 'https';
}

return [
    'app_name' => 'Webubbub',
    'url_options' => [
        'host' => getenv('APP_URL_HOST'),
        'port' => $url_port,
        'path' => $url_path,
        'protocol' => $url_protocol,
    ],
    'database' => [
        'dsn' => "sqlite:{$database_path}",
    ],
];
