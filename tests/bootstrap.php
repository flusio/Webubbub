<?php

$app_path = realpath(__DIR__ . '/..');

assert($app_path !== false);

include $app_path . '/vendor/autoload.php';

\Webubbub\Configuration::load('test', $app_path);

\Minz\Database::reset();
$schema = @file_get_contents(\Minz\Configuration::$schema_path);

assert($schema !== false);

$database = \Minz\Database::get();
$database->exec($schema);
