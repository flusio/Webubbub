<?php

namespace Webubbub\utils;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class AllowedOriginHelper
{
    public static function isOriginAllowed(string $origin): bool
    {
        if (\Webubbub\Configuration::isPublicHub()) {
            return true;
        }

        $allowed_topic_origins = explode(',', \Webubbub\Configuration::$application['allowed_topic_origins']);

        foreach ($allowed_topic_origins as $allowed_topic_origin) {
            $allowed_topic_origin = trim($allowed_topic_origin);

            if (str_starts_with($origin, $allowed_topic_origin)) {
                return true;
            }
        }

        return false;
    }
}
