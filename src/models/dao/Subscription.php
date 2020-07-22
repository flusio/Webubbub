<?php

namespace Webubbub\models\dao;

use Webubbub\models;

class Subscription extends \Minz\DatabaseModel
{
    public function __construct()
    {
        $subscription_properties = array_keys(models\Subscription::PROPERTIES);
        parent::__construct('subscriptions', 'id', $subscription_properties);
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
        return $statement->fetchAll();
    }
}
