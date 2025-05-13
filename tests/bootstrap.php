<?php

$app_path = realpath(__DIR__ . '/..');

assert($app_path !== false);

include $app_path . '/vendor/autoload.php';

\Webubbub\Configuration::load('test', $app_path);
