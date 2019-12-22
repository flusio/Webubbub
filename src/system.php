<?php

namespace Webubbub\controllers\system;

use Minz\Response;

/**
 * Initialize the database.
 *
 * @param \Minz\Request $request
 *
 * @return \Minz\Response
 */
function init($request)
{
    $configuration_path = \Minz\Configuration::$configuration_path;
    $schema = file_get_contents($configuration_path . '/schema.sql');
    $database = \Minz\Database::get();
    $database->exec($schema);

    return Response::ok();
}

/**
 * Execute the migrations under src/migrations/. The version is stored in
 * data/migrations_version.txt.
 *
 * @param \Minz\Request $request
 *
 * @return \Minz\Response
 */
function migrate($request)
{
    $app_path = \Minz\Configuration::$app_path;
    $migrations_path = $app_path . '/src/migrations';
    $migrator = new \Minz\Migrator($migrations_path);

    $migrations_version_path = $app_path . '/data/migrations_version.txt';
    $migration_version = @file_get_contents($migrations_version_path);
    if ($migration_version) {
        $migrator->setVersion($migration_version);
    }

    if ($migrator->upToDate()) {
        return Response::ok('system/migrate/up_to_date.txt');
    }

    $results = $migrator->migrate();

    $new_version = $migrator->version();
    $saved = file_put_contents($migrations_version_path, $new_version);
    if ($saved === false) {
        return Response::internalServerError('system/error.txt', [
            'error' => "Cannot save data/migrations_version.txt file ({$new_version})",
        ]);
    }

    foreach ($results as $migration => $result) {
        if ($result === true) {
            $results[$migration] = 'OK';
        } elseif ($result === false) {
            $results[$migration] = 'KO';
        }
    }

    return Response::ok('system/migrate/results.txt', ['results' => $results]);
}
