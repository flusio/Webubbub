<?php

$app_path = realpath(__DIR__ . '/..');

include $app_path . '/src/autoload.php';

\Minz\Configuration::load('test', $app_path);
