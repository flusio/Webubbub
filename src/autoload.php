<?php

spl_autoload_register(function ($class_name) {
    if (strpos($class_name, 'Minz') === 0) {
        $class_name = substr($class_name, 5);
        $base_path = 'lib/Minz/';
        include($base_path . str_replace('\\', '/', $class_name) . '.php');
    }
});
