<?php

namespace Webubbub\migrations;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Migration201912240001CreateContentDelivery
{
    /**
     * @return boolean true if the migration was successful, false otherwise
     */
    public function migrate()
    {
        $database = \Minz\Database::get();

        $sql = <<<SQL
            CREATE TABLE content_deliveries (
              id integer NOT NULL PRIMARY KEY AUTOINCREMENT,
              subscription_id integer NOT NULL,
              content_id integer NOT NULL,
              created_at datetime NOT NULL,
              try_at datetime NOT NULL,
              tries_count integer NOT NULL DEFAULT 0,
              FOREIGN KEY (subscription_id) REFERENCES subscriptions(id),
              FOREIGN KEY (content_id) REFERENCES contents(id)
            )
        SQL;

        $database->exec($sql);

        return true;
    }
}
