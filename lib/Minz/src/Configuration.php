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
 * An `environment` and an `app_path` values are automatically set from the
 * parameters of the `load` method.
 *
 * Required parameters are:
 * - app_name: it must be the same as the base namespace of the application
 * - url_options, with:
 *   - host: the domain name pointing to your server
 *   - port: the listening port of your server (default is 80)
 *   - path: URI path to your application (default is /)
 *   - protocol: the protocol used by your server (default is http)
 *
 * Other automated values are:
 * - configuration_path: the path to the configuration directory
 * - configuration_filepath: the path to the current configuration file
 *
 * Other optional keys are:
 * - database: an array specifying dsn, username, password and options to pass
 *   to the PDO interface, see https://www.php.net/manual/fr/pdo.construct.php
 * - use_session: indicates if you want to use the PHP sessions, default to `true`
 * - no_syslog: `true` to silent calls to \Minz\Log (wrapper aroung syslog function),
 *   default to `false`
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

    /** @var string The path to the configuration directory */
    public static $configuration_path;

    /** @var string The path to the current configuration file */
    public static $configuration_filepath;

    /**
     * @var string The name of the application. It must be identical to the
     *             application's namespace.
     */
    public static $app_name;

    /** @var array The web server information to build URLs */
    public static $url_options;

    /** @var string[] An array containing database configuration */
    public static $database;

    /** @var boolean Indicate if session must be initialized */
    public static $use_session;

    /** @var boolean Indicate if syslog must be called via \Minz\Log calls */
    public static $no_syslog;

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
     * @throws \Minz\Errors\ConfigurationError if a value doesn't match with
     *                                         the required format
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

        $configuration_path = $app_path . '/configuration';
        $configuration_filename = "environment_{$environment}.php";
        $configuration_filepath = $configuration_path . '/' . $configuration_filename;
        if (!file_exists($configuration_filepath)) {
            throw new Errors\ConfigurationError(
                "configuration/{$configuration_filename} file cannot be found."
            );
        }

        $raw_configuration = include($configuration_filepath);

        self::$environment = $environment;
        self::$app_path = $app_path;
        self::$configuration_path = $configuration_path;
        self::$configuration_filepath = $configuration_filepath;

        self::$app_name = self::getRequired($raw_configuration, 'app_name');

        $url_options = self::getRequired($raw_configuration, 'url_options');
        if (!is_array($url_options)) {
            throw new Errors\ConfigurationError(
                'URL options configuration must be an array, containing at least a host key.'
            );
        }

        if (!isset($url_options['host'])) {
            throw new Errors\ConfigurationError(
                'URL options configuration must contain at least a host key.'
            );
        }

        $default_url_options = [
            'port' => 80,
            'path' => '/',
            'protocol' => 'http',
        ];
        self::$url_options = array_merge($default_url_options, $url_options);

        $database = self::getDefault($raw_configuration, 'database', null);
        if ($database !== null) {
            if (!is_array($database)) {
                throw new Errors\ConfigurationError(
                    'Database configuration must be an array, containing at least a dsn key.'
                );
            }

            if (!isset($database['dsn'])) {
                throw new Errors\ConfigurationError(
                    'Database configuration must contain at least a dsn key.'
                );
            }

            $additional_default_values = [
                'username' => null,
                'password' => null,
                'options' => [],
            ];
            $database = array_merge($additional_default_values, $database);
        }
        self::$database = $database;

        self::$use_session = self::getDefault($raw_configuration, 'use_session', true);

        self::$no_syslog = self::getDefault($raw_configuration, 'no_syslog', false);
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
