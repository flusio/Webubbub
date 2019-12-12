<?php

spl_autoload_register(function ($class_name) {
    if (strpos($class_name, 'Minz') === 0) {
        $class_name = substr($class_name, 5);
        include(__DIR__ . '/src/' . str_replace('\\', '/', $class_name) . '.php');
    } elseif (strpos($class_name, 'AppTest') === 0) {
        $class_name = substr($class_name, 8);
        $class_path = str_replace('\\', '/', $class_name) . '.php';
        include(__DIR__ . '/tests/test_app/src/' . $class_path);
    }
});
