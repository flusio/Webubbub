<?php

namespace Webubbub;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Router
{
    public static function loadApp(): \Minz\Router
    {
        $router = new \Minz\Router();

        $router->addRoute('GET', '/', 'Home#index');

        // This is the main route that subscribers and publishers must use
        $router->addRoute('POST', '/', 'Requests#handle');

        // This route just simulate a subscriber, just for testing
        $router->addRoute('GET', '/dummy-subscriber', 'Home#dummySubscriber');
        $router->addRoute('POST', '/dummy-subscriber', 'Home#dummySubscriber');


        return $router;
    }

    public static function loadCli(): \Minz\Router
    {
        $router = self::loadApp();

        $router->addRoute('CLI', '/help', 'Help#show');

        $router->addRoute('CLI', '/requests/subscribe', 'Requests#subscribe');
        $router->addRoute('CLI', '/requests/unsubscribe', 'Requests#unsubscribe');
        $router->addRoute('CLI', '/requests/publish', 'Requests#publish');

        // These routes list what is in database, to help to debug
        $router->addRoute('CLI', '/subscriptions', 'Subscriptions#items');
        $router->addRoute('CLI', '/contents', 'Contents#items');

        // These are used to manipulate the system
        $router->addRoute('CLI', '/migrations', 'Migrations#index');
        $router->addRoute('CLI', '/migrations/setup', 'Migrations#setup');
        $router->addRoute('CLI', '/migrations/rollback', 'Migrations#rollback');
        $router->addRoute('CLI', '/migrations/create', 'Migrations#create');

        $router->addRoute('CLI', '/jobs', 'Jobs#index');
        $router->addRoute('CLI', '/jobs/watch', 'Jobs#watch');
        $router->addRoute('CLI', '/jobs/run', 'Jobs#run');
        $router->addRoute('CLI', '/jobs/show', 'Jobs#show');
        $router->addRoute('CLI', '/jobs/unfail', 'Jobs#unfail');
        $router->addRoute('CLI', '/jobs/unlock', 'Jobs#unlock');

        return $router;
    }
}
