<?php

namespace Webubbub\migrations;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20191226_164849_AddPendingSecretAndPendingLeaseSecondsToSubscription
{
    /**
     * @return boolean true if the migration was successful, false otherwise
     */
    public function migrate()
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
            }
        }
        $database->commit();

        return true;
    }
}
