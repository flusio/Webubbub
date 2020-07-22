<?php

namespace Webubbub\migrations;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20191223_160843_CompleteContentModel
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
            $result = $database->exec($sql);

            if ($result === false) {
                $database->rollBack();
            }
        }
        $database->commit();

        return true;
    }
}
