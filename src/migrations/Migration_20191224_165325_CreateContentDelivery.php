<?php

namespace Webubbub\migrations;

// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
class Migration_20191224_165325_CreateContentDelivery
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
