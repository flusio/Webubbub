<?php

namespace Webubbub\migration_20191226_164849_AddPendingSecretAndPendingLeaseSecondsToSubscription;

/**
 * @return boolean true if the migration was successful, false otherwise
 */
function migrate()
{
    $database = \Minz\Database::get();
    $columns = [
       'pending_lease_seconds integer',
       'pending_secret text',
    ];

    $database->beginTransaction();
    foreach ($columns as $column) {
        $sql = "ALTER TABLE subscriptions ADD COLUMN {$column}";
        $result = $database->exec($sql);

        if ($result === false) {
            $database->rollBack();
            $error_info = $database->errorInfo();
            throw new \Minz\Errors\DatabaseModelError(
                "Error in SQL statement: {$error_info[2]} ({$error_info[0]})."
            );
        }
    }
    $database->commit();

    return true;
}
