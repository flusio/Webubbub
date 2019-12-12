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
     * Return a row of the current model/table.
     *
     * @param mixed[] $values The values which must match
     *
     * @throws \Minz\Errors\DatabaseModelError if values is empty
     * @throws \Minz\Errors\DatabaseModelError if at least one property isn't
     *                                         declared by the model
     * @throws \Minz\Errors\DatabaseModelError if an error occured in the SQL syntax
     *
     * @return array|null Return the corresponding row data, null otherwise
     */
    public function findBy($values)
    {
        if (!$values) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "{$class}::create method expect values to be passed."
            );
        }

        $properties = array_keys($values);
        $undeclared_property = $this->findUndeclaredProperty($properties);
        if ($undeclared_property) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "{$undeclared_property} is not declared in the {$class} model."
            );
        }

        $where_statement_as_array = [];
        foreach ($properties as $property) {
            $where_statement_as_array[] = "{$property} = ?";
        }
        $where_statement = implode(' AND ', $where_statement_as_array);

        $sql = "SELECT * FROM {$this->table_name} WHERE {$where_statement}";

        $statement = $this->prepare($sql);
        $parameters = array_values($values);
        $result = $statement->execute($parameters);
        if (!$result) {
            throw self::sqlStatementError($statement);
        }

        $result = $statement->fetch();
        if ($result) {
            return $result;
        } else {
            return null;
        }
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
