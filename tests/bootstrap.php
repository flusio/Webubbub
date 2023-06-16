<?php

$app_path = realpath(__DIR__ . '/..');

assert($app_path !== false);

include $app_path . '/autoload.php';

\Minz\Configuration::load('test', $app_path);
