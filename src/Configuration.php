<?php

namespace Webubbub;

/**
 * @phpstan-type ConfigurationApplication array{
 *     'allowed_topic_origins': string,
 * }
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Configuration extends \Minz\Configuration
{
    /**
     * @var ConfigurationApplication
     */
    public static array $application;

    public static function isPublicHub(): bool
    {
        return self::$application['allowed_topic_origins'] === '';
    }

    /**
     * @return string[]
     */
    public static function allowedTopicOrigins(): array
    {
        return array_map('trim', explode(',', self::$application['allowed_topic_origins']));
    }
}
