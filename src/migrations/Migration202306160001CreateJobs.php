<?php

namespace Webubbub\migrations;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Migration202306160001CreateJobs
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE jobs (
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                perform_at TEXT NOT NULL,
                name TEXT NOT NULL DEFAULT '',
                args TEXT NOT NULL DEFAULT '{}',
                frequency TEXT NOT NULL DEFAULT '',
                queue TEXT NOT NULL DEFAULT 'default',
                locked_at TEXT,
                number_attempts BIGINT NOT NULL DEFAULT 0,
                last_error TEXT NOT NULL DEFAULT '',
                failed_at TEXT
            );
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP TABLE jobs;
        SQL);

        return true;
    }
}
