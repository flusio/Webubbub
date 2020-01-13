<?php

namespace Minz;

/**
 * The Environment class initialize the application environment, by setting
 * correct global runtime configuration to correct values according to the
 * defined Configuration::$environment.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Environment
{
    /**
     * Initialize the application environment.
     */
    public static function initialize()
    {
        // Configure system logger
        $app_name = Configuration::$app_name;
        openlog($app_name, LOG_PERROR | LOG_PID, LOG_USER);

        // Initialize the session
        if (Configuration::$use_session) {
            session_name($app_name);
            session_start();
        }

        // Configure error reporting
        $environment = Configuration::$environment;
        switch ($environment) {
            case 'development':
            case 'test':
                error_reporting(E_ALL);
                ini_set('display_errors', 'On');
                ini_set('log_errors', 'On');
                break;
            case 'production':
            default:
                error_reporting(E_ALL);
                ini_set('display_errors', 'Off');
                ini_set('log_errors', 'On');
                break;
        }
    }
}
