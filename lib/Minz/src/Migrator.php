<?php

namespace Minz;

/**
 * The Migrator helps to migrate data (in a database or not) or the
 * architecture of a Minz application.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Migrator
{
    /** @var string|null */
    private $version;

    /** @var array */
    private $migrations = [];

    /**
     * Create a Migrator instance. If directory is given, it'll load the
     * migrations from it.
     *
     * The migrations in the directory must declare a `migrate` function in the
     * namespace <app_name>\migration_<filename> where:
     *
     * - <app_name> is the application name declared in the configuration file
     * - <filename> is the migration file name, without the `.php` extension
     *
     * @param string|null $directory
     */
    public function __construct($directory = null)
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) as $filename) {
            if ($filename[0] === '.') {
                continue;
            }

            $filepath = $directory . '/' . $filename;
            $migration_name = basename($filename, '.php');
            $app_name = Configuration::$app_name;
            $migration_namespace = "\\{$app_name}\\migration_{$migration_name}";
            $migration_callback = "{$migration_namespace}\\migrate";

            $include_result = @include_once($filepath);
            if (!$include_result) {
                Log::warning("{$filepath} migration file cannot be loaded.");
            }
            $this->addMigration($migration_name, $migration_callback);
        }
    }

    /**
     * Register a migration into the migration system.
     *
     * @param string $name The name of the migration (be careful, migrations
     *                     are sorted with the `strnatcmp` function)
     * @param callback $callback The migration function to execute, it should
     *                           return true on success and must return false
     *                           on error
     *
     * @throws \Minz\Errors\MigrationError if the callback isn't callable.
     */
    public function addMigration($name, $callback)
    {
        if (!is_callable($callback)) {
            throw new Errors\MigrationError("{$name} migration cannot be called.");
        }

        $this->migrations[$name] = $callback;
    }

    /**
     * Return the list of migrations, sorted with `strnatcmp`
     *
     * @see https://www.php.net/manual/en/function.strnatcmp.php
     *
     * @return array
     */
    public function migrations()
    {
        $migrations = $this->migrations;
        uksort($migrations, 'strnatcmp');
        return $migrations;
    }

    /**
     * Set the actual version of the application.
     *
     * @param string $version
     *
     * @throws \Minz\Errors\MigrationError if there is no migrations corresponding
     *                                     to the given version.
     */
    public function setVersion($version)
    {
        $version = trim($version);
        if (!isset($this->migrations[$version])) {
            throw new Errors\MigrationError("{$version} migration does not exist.");
        }

        $this->version = $version;
    }

    /**
     * @return string|null
     */
    public function version()
    {
        return $this->version;
    }

    /**
     * @return string|null
     */
    public function lastVersion()
    {
        $migrations = array_keys($this->migrations());
        if (!$migrations) {
            return null;
        }

        return end($migrations);
    }

    /**
     * @return boolean Return true if the application is up-to-date, false
     *                 otherwise. If no migrations are registered, it always
     *                 returns true.
     */
    public function upToDate()
    {
        return $this->version === $this->lastVersion();
    }

    /**
     * Migrate the system to the latest version.
     *
     * It only executes migrations AFTER the current version. If a migration
     * returns false or fails, it immediatly stops the process.
     *
     * If the migration doesn't return false nor raise an exception, it is
     * considered as successful. It is considered as good practice to return
     * true on success though.
     *
     * @return array Return the results of each executed migration. If an
     *               exception was raised in a migration, its result is set to
     *               the exception message.
     */
    public function migrate()
    {
        $result = [];
        $apply_migration = $this->version === null;
        foreach ($this->migrations() as $version => $migration) {
            if (!$apply_migration) {
                $apply_migration = $this->version === $version;
                continue;
            }

            try {
                $migration_result = $migration();
                $result[$version] = $migration_result;
            } catch (\Exception $e) {
                $migration_result = false;
                $result[$version] = $e->getMessage();
            }

            if ($migration_result === false) {
                break;
            }

            $this->version = $version;
        }

        return $result;
    }
}
