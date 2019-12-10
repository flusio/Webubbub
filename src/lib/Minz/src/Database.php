<?php

namespace Minz;

class Database extends \PDO
{
    /** @var \Minz\Database */
    private static $instance;

    /**
     * Return an instance of Database.
     *
     * @throws \Minz\Errors\DatabaseError if database is not configured
     * @throws \Minz\Errors\DatabaseError if an error occured during initialization
     *
     * @return \Minz\Database
     */
    public static function get()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Drop the entire database. Hell yeah!
     *
     * It's useful for tests. Only SQLite database is supported for now. Take
     * care of getting a new database object after calling this method.
     *
     * @throws \Minz\Errors\DatabaseError if database is not configured
     * @throws \Minz\Errors\DatabaseError if database is not SQLite
     *
     * @return boolean Return true if the database file was deleted, false otherwise
     */
    public static function drop()
    {
        $database_configuration = Configuration::$database;
        if (!$database_configuration) {
            throw new Errors\DatabaseError(
                'The database is not set in the configuration file.'
            );
        }

        $dsn = $database_configuration['dsn'];
        list($database_type, $dsn_rest) = explode(':', $dsn, 2);

        if ($database_type !== 'sqlite') {
            throw new Errors\DatabaseError(
                "The database type {$database_type} is not supported for dropping."
            );
        }

        self::$instance = null;

        if ($dsn_rest === ':memory:') {
            return true;
        } else {
            return @unlink($dsn_rest);
        }
    }

    /**
     * Initialize a PDO database.
     *
     * @throws \Minz\Errors\DatabaseError if database is not configured
     * @throws \Minz\Errors\DatabaseError if an error occured during initialization
     */
    private function __construct()
    {
        $database_configuration = Configuration::$database;
        if (!$database_configuration) {
            throw new Errors\DatabaseError(
                'The database is not set in the configuration file.'
            );
        }

        $dsn = $database_configuration['dsn'];
        $username = $database_configuration['username'];
        $password = $database_configuration['password'];
        $options = $database_configuration['options'];
        $database_type = strstr($dsn, ':', true);

        // Force some options values
        $options[\PDO::ATTR_DEFAULT_FETCH_MODE] = \PDO::FETCH_ASSOC;
        $options[\PDO::ATTR_EMULATE_PREPARES] = false;

        try {
            parent::__construct($dsn, $username, $password, $options);

            if ($database_type === 'sqlite') {
                $this->exec('PRAGMA foreign_keys = ON;');
            }
        } catch (\PDOException $e) {
            throw new Errors\DatabaseError(
                "An error occured during database initialization: {$e->getMessage()}."
            );
        }
    }
}
