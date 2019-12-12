<?php

include __DIR__ . '/../autoload.php';

\Minz\Configuration::load('test', __DIR__ . '/test_app');
\Minz\Environment::initialize();
