<?php

namespace Minz;

/**
 * Represent the configuration of the application.
 *
 * `\Minz\Configuration::load($env, $app_path)` must be called at the very
 * beginning of the app initialization.
 *
 * Configurations must be declared under a `configuration/` directory. They are
 * loaded for a given environment (either "development", "test" or
 * "production") and from a `configuration/environment_<environment>.php`
 * file, where `<environment>` is replaced by the value of the current env.
 * These files must return a PHP array.
 *
 * The `app_name` key is required and it must be the same as the base namespace
 * of the application.
 *
 * An `environment` and an `app_path` values are automatically set from the
 * parameters of the `load` method.
 *
 * Other optional keys are:
 * - controllers_path (the path to the controllers directory, useful for the tests)
 * - views_path (the path to the views directory, useful for the tests)
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Configuration
{
    /** @var string[] */
    private const VALID_ENVIRONMENTS = ['development', 'test', 'production'];

    /** @var string The environment in which the application is run */
    public static $environment;

    /** @var string The base path of the application */
    public static $app_path;

    /**
     * @var string The name of the application. It must be identical to the
     *             application's namespace.
     */
    public static $app_name;

    /** @var string The path to the application's views (from app_path). */
    public static $views_path;

    /** @var string The path to the application's controllers (from app_path). */
    public static $controllers_path;

    /**
     * Load the application's configuration, for a given environment.
     *
     * @param string $environment
     * @param string $app_path
     *
     * @throws \Minz\Errors\ConfigurationError if the environment is not part
     *                                         of the valid environments
     * @throws \Minz\Errors\ConfigurationError if the corresponding environment
     *                                         configuration file doesn't exist
     * @throws \Minz\Errors\ConfigurationError if a required value is missing
     *
     * @return void
     */
    public static function load($environment, $app_path)
    {
        if (!in_array($environment, self::VALID_ENVIRONMENTS)) {
            throw new Errors\ConfigurationError(
                "{$environment} is not a valid environment."
            );
        }

        $configuration_filename = "environment_{$environment}.php";
        $configuration_filepath = "{$app_path}/configuration/{$configuration_filename}";
        if (!file_exists($configuration_filepath)) {
            throw new Errors\ConfigurationError(
                "configuration/{$configuration_filename} file cannot be found."
            );
        }

        $raw_configuration = include($configuration_filepath);

        self::$environment = $environment;
        self::$app_path = $app_path;

        self::$app_name = self::getRequired($raw_configuration, 'app_name');

        self::$controllers_path = self::getDefault(
            $raw_configuration,
            'controllers_path',
            'src/controllers'
        );
        self::$views_path = self::getDefault(
            $raw_configuration,
            'views_path',
            'src/views'
        );
    }

    /**
     * Return the value associated to the key of an array, or throw an error if
     * it doesn't exist.
     *
     * @param mixed[] $array
     * @param string $key
     *
     * @throws \Minz\Errors\ConfigurationError if the given key is not in the array
     *
     * @return mixed
     */
    private static function getRequired($array, $key)
    {
        if (isset($array[$key])) {
            return $array[$key];
        } else {
            throw new Errors\ConfigurationError("{$key} configuration key is required");
        }
    }

    /**
     * Return the value associated to the key of an array, or a default one if
     * it doesn't exist.
     *
     * @param mixed[] $array
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    private static function getDefault($array, $key, $default)
    {
        if (isset($array[$key])) {
            return $array[$key];
        } else {
            return $default;
        }
    }
}
