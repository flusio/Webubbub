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

        // This is the main route that subscribers and publishers must use
        $router->addRoute('/', 'requests#handle', ['post', 'cli']);

        // These are the same but don't require the `mode` parameter (only CLI)
        $router->addRoute('/requests/subscribe', 'requests#subscribe', 'cli');
        $router->addRoute('/requests/unsubscribe', 'requests#unsubscribe', 'cli');
        $router->addRoute('/requests/publish', 'requests#publish', 'cli');

        // These ones are intended to be called regularly on the server (e.g.
        // via a cron task and later via a job queue).
        $router->addRoute('/intents/verify', 'intents#verify', 'cli');
        $router->addRoute('/subscriptions/expire', 'subscriptions#expire', 'cli');

        // These routes list what is in database, to help to debug
        $router->addRoute('/subscriptions', 'subscriptions#items', 'cli');
        $router->addRoute('/contents', 'contents#items', 'cli');

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
