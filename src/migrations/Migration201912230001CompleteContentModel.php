<?php

namespace Webubbub\migrations;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Migration201912230001CompleteContentModel
{
    /**
     * @return boolean true if the migration was successful, false otherwise
     */
    public function migrate()
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
            $database->exec($sql);
        }
        $database->commit();

        return true;
    }
}
