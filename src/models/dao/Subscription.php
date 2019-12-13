<?php

namespace Webubbub\models\dao;

class Subscription extends \Minz\DatabaseModel
{
    public function __construct()
    {
        parent::__construct(
            'subscriptions',
            'id',
            [
                'id',
                'created_at',
                'expired_at',
                'status',
                'pending_request',

                'callback',
                'topic',
                'lease_seconds',
                'secret',
            ]
        );
    }

    /**
     * Return rows where pending_request is not null.
     *
     * @throws \Minz\Errors\DatabaseModelError if an error occured in the SQL syntax
     *
     * @return array
     */
    public function listWherePendingRequests()
    {
        $sql = "SELECT * FROM {$this->table_name} WHERE pending_request IS NOT NULL";

        $statement = $this->query($sql);
        $result = $statement->fetchAll();
        if ($result !== false) {
            return $result;
        } else {
            throw self::sqlStatementError($statement);
        }
    }
}
