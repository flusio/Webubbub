<?php

namespace Webubbub;

class Application
{
    /** @var \Minz\Engine **/
    private $engine;

    public function __construct()
    {
        $router = new \Minz\Router();
        $router->addRoute('get', '/', 'Home#index');

        // This is the main route that subscribers and publishers must use
        $router->addRoute('post', '/', 'Requests#handle');
        $router->addRoute('cli', '/', 'Requests#handle');

        // These are the same but don't require the `mode` parameter (only CLI)
        $router->addRoute('cli', '/requests/subscribe', 'Requests#subscribe');
        $router->addRoute('cli', '/requests/unsubscribe', 'Requests#unsubscribe');
        $router->addRoute('cli', '/requests/publish', 'Requests#publish');

        // This route just simulate a subscriber, just for testing
        $router->addRoute('get', '/dummy-subscriber', 'Home#dummySubscriber');
        $router->addRoute('post', '/dummy-subscriber', 'Home#dummySubscriber');

        // These ones are intended to be called regularly on the server (e.g.
        // via a cron task and later via a job queue).
        $router->addRoute('cli', '/subscriptions/validate', 'Subscriptions#validate');
        $router->addRoute('cli', '/subscriptions/verify', 'Subscriptions#verify');
        $router->addRoute('cli', '/subscriptions/expire', 'Subscriptions#expire');
        $router->addRoute('cli', '/contents/fetch', 'Contents#fetch');
        $router->addRoute('cli', '/contents/deliver', 'Contents#deliver');

        // These routes list what is in database, to help to debug
        $router->addRoute('cli', '/subscriptions', 'Subscriptions#items');
        $router->addRoute('cli', '/contents', 'Contents#items');

        // These are used to manipulate the system
        $router->addRoute('cli', '/system/init', 'System#init');
        $router->addRoute('cli', '/system/migrate', 'System#migrate');
        $router->addRoute('cli', '/system/clean', 'System#clean');

        $this->engine = new \Minz\Engine($router);
        \Minz\Url::setRouter($router);
    }

    public function run($request)
    {
        return $this->engine->run($request, [
            'not_found_view_pointer' => 'not_found.phtml',
            'internal_server_error_view_pointer' => 'internal_server_error.phtml',
        ]);
    }
}
