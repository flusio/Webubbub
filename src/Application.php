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

        // This route just simulate a subscriber, just for testing
        $router->addRoute('/dummy-subscriber', 'home#dummySubscriber', ['get', 'post']);

        // These ones are intended to be called regularly on the server (e.g.
        // via a cron task and later via a job queue).
        $router->addRoute('/intents/verify', 'intents#verify', 'cli');
        $router->addRoute('/subscriptions/expire', 'subscriptions#expire', 'cli');

        // These routes list what is in database, to help to debug
        $router->addRoute('/subscriptions', 'subscriptions#items', 'cli');
        $router->addRoute('/contents', 'contents#items', 'cli');

        // These are used to manipulate the system
        $router->addRoute('/system/init', 'system#init', 'cli');
        $router->addRoute('/system/migrate', 'system#migrate', 'cli');

        $this->engine = new \Minz\Engine($router);
        \Minz\Url::setRouter($router);
    }

    public function run($request)
    {
        return $this->engine->run($request);
    }
}
