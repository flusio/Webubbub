<?php

namespace Webubbub\migration_20191223_160843_CompleteContentModel;

/**
 * @return boolean true if the migration was successful, false otherwise
 */
function migrate()
{
    $database = \Minz\Database::get();
    $columns = [
       'fetched_at datetime',
       'status text NOT NULL DEFAULT new',
       'links text',
       'type text',
       'content text',
    ];

    $database->beginTransaction();
    foreach ($columns as $column) {
        $sql = "ALTER TABLE contents ADD COLUMN {$column}";
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
