<?php

namespace Minz;

/**
 * Abstract the calls to the syslog function. It might allow to preformat
 * messages in the future.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Log
{
    public static function notice($message)
    {
        if (!Configuration::$no_syslog) {
            syslog(LOG_NOTICE, $message);
        }
    }

    public static function warning($message)
    {
        if (!Configuration::$no_syslog) {
            syslog(LOG_WARNING, $message);
        }
    }

    public static function error($message)
    {
        if (!Configuration::$no_syslog) {
            syslog(LOG_ERR, $message);
        }
    }
}
