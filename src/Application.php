<?php

namespace Webubbub;

class Application
{
    /** @var \Minz\Engine **/
    private $engine;

    public function __construct()
    {
        $router = new \Minz\Router();
        $router->addRoute('get', '/', 'home#index');

        // This is the main route that subscribers and publishers must use
        $router->addRoute(['post', 'cli'], '/', 'requests#handle');

        // These are the same but don't require the `mode` parameter (only CLI)
        $router->addRoute('cli', '/requests/subscribe', 'requests#subscribe');
        $router->addRoute('cli', '/requests/unsubscribe', 'requests#unsubscribe');
        $router->addRoute('cli', '/requests/publish', 'requests#publish');

        // This route just simulate a subscriber, just for testing
        $router->addRoute(['get', 'post'], '/dummy-subscriber', 'home#dummySubscriber');

        // These ones are intended to be called regularly on the server (e.g.
        // via a cron task and later via a job queue).
        $router->addRoute('cli', '/subscriptions/verify', 'subscriptions#verify');
        $router->addRoute('cli', '/subscriptions/expire', 'subscriptions#expire');
        $router->addRoute('cli', '/contents/fetch', 'contents#fetch');
        $router->addRoute('cli', '/contents/deliver', 'contents#deliver');

        // These routes list what is in database, to help to debug
        $router->addRoute('cli', '/subscriptions', 'subscriptions#items');
        $router->addRoute('cli', '/contents', 'contents#items');

        // These are used to manipulate the system
        $router->addRoute('cli', '/system/init', 'system#init');
        $router->addRoute('cli', '/system/migrate', 'system#migrate');

        $this->engine = new \Minz\Engine($router);
        \Minz\Url::setRouter($router);
    }

    public function run($request)
    {
        return $this->engine->run($request);
    }
}
