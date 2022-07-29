<?php

namespace Webubbub;

use Minz\Response;

class System
{
    /**
     * Initialize the database and set the migration version.
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function init($request)
    {
        $app_path = \Minz\Configuration::$app_path;
        $migrations_path = $app_path . '/src/migrations';
        $migrations_version_path = $app_path . '/data/migrations_version.txt';

        if (file_exists($migrations_version_path)) {
            return Response::internalServerError('system/error.txt', [
                'error' => 'data/migrations_version.txt file exists, the system is already initialized.',
            ]);
        }

        $schema = file_get_contents($app_path . '/src/schema.sql');
        $database = \Minz\Database::get();
        $database->exec($schema);

        $migrator = new \Minz\Migrator($migrations_path);
        $version = $migrator->lastVersion();
        $saved = @file_put_contents($migrations_version_path, $version);
        if ($saved === false) {
            return Response::internalServerError('system/error.txt', [
                'error' => "Cannot save data/migrations_version.txt file ({$version}).",
            ]);
        }

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
    public function migrate($request)
    {
        $app_path = \Minz\Configuration::$app_path;
        $migrations_path = $app_path . '/src/migrations';
        $migrations_version_path = $app_path . '/data/migrations_version.txt';

        if (!file_exists($migrations_version_path)) {
            return Response::internalServerError('system/error.txt', [
                'error' => 'data/migrations_version.txt file does not exist, you must initialize the system first.',
            ]);
        }

        $migration_version = @file_get_contents($migrations_version_path);
        if ($migration_version === false) {
            return Response::internalServerError('system/error.txt', [
                'error' => 'Cannot read data/migrations_version.txt file.',
            ]);
        }

        $migrator = new \Minz\Migrator($migrations_path);
        if ($migration_version) {
            $migrator->setVersion($migration_version);
        }

        if ($migrator->upToDate()) {
            return Response::ok('system/migrate/up_to_date.txt');
        }

        $results = $migrator->migrate();

        $new_version = $migrator->version();
        $saved = @file_put_contents($migrations_version_path, $new_version);
        if ($saved === false) {
            return Response::internalServerError('system/error.txt', [
                'error' => "Cannot save data/migrations_version.txt file ({$new_version}).",
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

    /**
     * Clean expired data from the database.
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function clean($request)
    {
        $subscription_dao = new models\dao\Subscription();
        $content_dao = new models\dao\Content();

        $two_weeks_ago = \Minz\Time::ago(2, 'weeks');
        $db_subscriptions = $subscription_dao->listBy([
            'status' => ['new', 'validated', 'expired'],
        ]);
        $subscription_ids_to_delete = [];
        foreach ($db_subscriptions as $db_subscription) {
            $subscription = new models\Subscription($db_subscription);
            if (
                $subscription->status === 'expired' &&
                $subscription->expired_at <= $two_weeks_ago
            ) {
                $subscription_ids_to_delete[] = $subscription->id;
            }

            if (
                $subscription->status !== 'expired' &&
                $subscription->created_at <= $two_weeks_ago
            ) {
                $subscription_ids_to_delete[] = $subscription->id;
            }
        }
        $subscription_dao->delete($subscription_ids_to_delete);

        $db_contents = $content_dao->listBy([
            'status' => 'delivered',
        ]);
        $content_ids_to_delete = array_column($db_contents, 'id');
        $content_dao->delete($content_ids_to_delete);
    }
}
