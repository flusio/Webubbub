<?php

spl_autoload_register(function ($class_name) {
    if (strpos($class_name, 'Minz') === 0) {
        $minz_autoload_path = __DIR__ . '/lib/Minz/src/autoload.php';
        include($minz_autoload_path);
    }
});
