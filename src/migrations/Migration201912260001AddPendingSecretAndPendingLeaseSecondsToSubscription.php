<?php

namespace Webubbub\migrations;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Migration201912260001AddPendingSecretAndPendingLeaseSecondsToSubscription
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
            $database->exec($sql);
        }
        $database->commit();

        return true;
    }
}
