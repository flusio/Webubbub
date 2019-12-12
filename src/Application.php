<?php

namespace Webubbub;

class Application
{
    /** @var \Minz\Engine **/
    private $engine;

    public function __construct()
    {
        $router = new \Minz\Router();
        $router->addRoute('/', 'home#index', 'get');
        $router->addRoute('/', 'subscriptions#create', 'post');

        $router->addRoute('/subscriptions/create', 'subscriptions#create', 'cli');
        $router->addRoute('/subscriptions/items', 'subscriptions#items', 'cli');

        $this->engine = new \Minz\Engine($router);

        // Initialize the database. If the DB exists, the request will fail since
        // tables already exist. We don't care.
        // I'll design a better system later but for now it's good enough.
        $configuration_path = \Minz\Configuration::$configuration_path;
        $schema = file_get_contents($configuration_path . '/schema.sql');
        $database = \Minz\Database::get();
        $database->exec($schema);
    }

    public function run($request)
    {
        return $this->engine->run($request);
    }
}
