<?php

include __DIR__ . '/../src/autoload.php';

\Minz\Configuration::load('test', __DIR__ . '/test_app');
\Minz\Environment::initialize();
