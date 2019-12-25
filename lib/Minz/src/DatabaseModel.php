<?php

namespace Minz;

class DatabaseModel
{
    private const VALID_TABLE_NAME_REGEX = '/^\w[\w\d]*$/';
    private const VALID_COLUMN_NAME_REGEX = '/^\w[\w\d]*$/';

    /** @var \Minz\Database */
    protected $database;

    /** @var string */
    protected $table_name;

    /** @var string */
    protected $primary_key_name;

    /** @var string[] */
    protected $properties;

    /**
     * @throws \Minz\DatabaseModelError if the table name, or one of the
     *                                  declared properties is invalid
     * @throws \Minz\DatabaseModelError if the primary key name isn't declared
     *                                  in properties
     * @throws \Minz\Errors\DatabaseError if the database initialization fails
     *
     * @see \Minz\Database::_construct()
     */
    public function __construct($table_name, $primary_key_name, $properties)
    {
        $this->database = Database::get();

        $this->setTableName($table_name);
        $this->setProperties($properties);
        $this->setPrimaryKeyName($primary_key_name);
    }

    /**
     * Create an instance of the model in database
     *
     * @param mixed[] $values The list of properties with associated values
     *
     * @throws \Minz\Errors\DatabaseModelError if values is empty
     * @throws \Minz\Errors\DatabaseModelError if at least one property isn't
     *                                         declared by the model
     * @throws \Minz\Errors\DatabaseModelError if an error occured in the SQL syntax
     *
     * @return integer|string|boolean Return the id as an integer if cast is
     *                                possible, as a string otherwise. Return
     *                                true if lastInsertId is not supported by
     *                                the PDO driver.
     */
    public function create($values)
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

        $values_as_question_marks = array_fill(0, count($values), '?');
        $values_placeholder = implode(", ", $values_as_question_marks);
        $columns_placeholder = implode(", ", $properties);

        $sql = "INSERT INTO {$this->table_name} ({$columns_placeholder}) VALUES ({$values_placeholder})";

        $statement = $this->prepare($sql);
        $result = $statement->execute(array_values($values));
        if ($result) {
            return $this->lastInsertId();
        } else {
            throw self::sqlStatementError($statement);
        }
    }

    /**
     * Return a list of all items in database for the current model/table.
     *
     * @param string[] $selected_properties Allow to limit what properties to
     *                                      get (optional)
     *
     * @throws \Minz\Errors\DatabaseModelError if at least one property isn't
     *                                         declared by the model
     * @throws \Minz\Errors\DatabaseModelError if an error occured in the SQL syntax
     *
     * @return array
     */
    public function listAll($selected_properties = [])
    {
        $undeclared_property = $this->findUndeclaredProperty($selected_properties);
        if ($undeclared_property) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "{$undeclared_property} is not declared in the {$class} model."
            );
        }

        if ($selected_properties) {
            $select_sql = implode(', ', $selected_properties);
        } else {
            $select_sql = '*';
        }

        $sql = "SELECT {$select_sql} FROM {$this->table_name}";

        $statement = $this->query($sql);
        $result = $statement->fetchAll();
        if ($result !== false) {
            return $result;
        } else {
            throw self::sqlStatementError($statement);
        }
    }

    /**
     * Return a row of the current model/table.
     *
     * @param integer|string $primary_key The value of the row's primary key to find
     *
     * @throws \Minz\Errors\DatabaseModelError if an error occured in the SQL syntax
     *
     * @return array|null Return the corresponding row data, null otherwise
     */
    public function find($primary_key)
    {
        $sql = "SELECT * FROM {$this->table_name} WHERE {$this->primary_key_name} = ?";

        $statement = $this->prepare($sql);
        $result = $statement->execute([$primary_key]);
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
     * Return a row matching the given values.
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
        $result = $this->listBy($values);
        if ($result) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * Return a list of rows matching the given values.
     *
     * @param mixed[] $values The values which must match
     *
     * @throws \Minz\Errors\DatabaseModelError if values is empty
     * @throws \Minz\Errors\DatabaseModelError if at least one property isn't
     *                                         declared by the model
     * @throws \Minz\Errors\DatabaseModelError if an error occured in the SQL syntax
     *
     * @return array Return the corresponding row data, null otherwise
     */
    public function listBy($values)
    {
        if (!$values) {
            throw new Errors\DatabaseModelError(
                'It is expected values not to be empty.'
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

        $result = $statement->fetchAll();
        if ($result !== false) {
            return $result;
        } else {
            throw self::sqlStatementError($statement);
        }
    }

    /**
     * Update a row
     *
     * @param mixed[] $values The list of properties with associated values
     * @param integer|string $primary_key The value of the row's primary key to change
     *
     * @throws \Minz\Errors\DatabaseModelError if values is empty
     * @throws \Minz\Errors\DatabaseModelError if at least one property isn't
     *                                         declared by the model
     * @throws \Minz\Errors\DatabaseModelError if an error occured in the SQL syntax
     */
    public function update($primary_key, $values)
    {
        if (!$values) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "{$class}::update method expect values to be passed."
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

        $set_statement_as_array = [];
        foreach ($properties as $property) {
            $set_statement_as_array[] = "{$property} = ?";
        }
        $set_statement = implode(', ', $set_statement_as_array);
        $where_statement = "{$this->primary_key_name} = ?";

        $sql = "UPDATE {$this->table_name} SET {$set_statement} WHERE {$where_statement}";

        $statement = $this->prepare($sql);
        $parameters = array_values($values);
        $parameters[] = $primary_key;
        $result = $statement->execute($parameters);
        if (!$result) {
            throw self::sqlStatementError($statement);
        }
    }

    /**
     * Delete a row.
     *
     * @param integer|string $primary_key The value of the row's primary key to delete
     *
     * @throws \Minz\Errors\DatabaseModelError if an error occured in the SQL syntax
     */
    public function delete($primary_key)
    {
        $where_statement = "{$this->primary_key_name} = ?";

        $sql = "DELETE FROM {$this->table_name} WHERE {$where_statement}";

        $statement = $this->prepare($sql);
        $result = $statement->execute([$primary_key]);
        if (!$result) {
            throw self::sqlStatementError($statement);
        }
    }

    /**
     * Return the number of model instances saved in database
     *
     * @throws \Minz\DatabaseModelError if the query fails
     *
     * @return integer
     */
    public function count()
    {
        $sql = "SELECT COUNT(*) FROM {$this->table_name};";

        $statement = $this->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Call query method on a database object.
     *
     * @param string $sql_statement
     *
     * @throws \Minz\DatabaseModelError if the query fails
     * @throws \Minz\Errors\DatabaseError if the database initialization fails
     *
     * @see \Minz\Database::_construct()
     *
     * @return \PDOStatement
     */
    protected function query($sql_statement)
    {
        $statement = $this->database->query($sql_statement);
        if (!$statement) {
            throw self::sqlStatementError($this->database);
        }

        return $statement;
    }

    /**
     * Call prepare method on a database object.
     *
     * @param string $sql_statement
     *
     * @throws \Minz\DatabaseModelError if the query fails
     *
     * @return \PDOStatement
     */
    protected function prepare($sql_statement)
    {
        $statement = $this->database->prepare($sql_statement);
        if (!$statement) {
            throw self::sqlStatementError($this->database);
        }

        return $statement;
    }

    /**
     * Return the id of the last inserted row
     *
     * @see \PDO::lastInsertId()
     *
     * @return integer|string|boolean Return the id as an integer if cast is
     *                                possible, as a string otherwise. Return
     *                                true if lastInsertId is not supported by
     *                                the PDO driver.
     */
    protected function lastInsertId()
    {
        try {
            $id = $this->database->lastInsertId();
            if (filter_var($id, FILTER_VALIDATE_INT)) {
                return intval($id);
            } else {
                return $id;
            }
        } catch (\PDOException $e) {
            return true;
        }
    }

    /**
     * Return one (and only one) undeclared property.
     *
     * The properties must be declared in the `$properties` attribute. If
     * someone tries to set or use an undeclared property, an error must be
     * throwed.
     *
     * @param string[] $properties The properties to check
     *
     * @return string|null Return an undeclared property if any, null otherwise
     */
    protected function findUndeclaredProperty($properties)
    {
        $valid_properties = $this->properties;
        $undeclared_properties = array_diff($properties, $valid_properties);
        if ($undeclared_properties) {
            return current($undeclared_properties);
        } else {
            return null;
        }
    }

    /**
     * @throws \Minz\DatabaseModelError if the table name is invalid
     */
    private function setTableName($table_name)
    {
        if (!preg_match(self::VALID_TABLE_NAME_REGEX, $table_name)) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "{$table_name} is not a valid table name in the {$class} model."
            );
        }
        $this->table_name = $table_name;
    }

    /**
     * @throws \Minz\DatabaseModelError if at least one of the properties is invalid
     */
    private function setProperties($properties)
    {
        foreach ($properties as $property) {
            if (!preg_match(self::VALID_COLUMN_NAME_REGEX, $property)) {
                $class = get_called_class();
                throw new Errors\DatabaseModelError(
                    "{$property} is not a valid column name in the {$class} model."
                );
            }
        }
        $this->properties = $properties;
    }

    /**
     * @throws \Minz\DatabaseModelError if the primary key name isn't declared
     *                                  in properties
     */
    private function setPrimaryKeyName($primary_key_name)
    {
        if (!in_array($primary_key_name, $this->properties)) {
            $class = get_called_class();
            throw new Errors\DatabaseModelError(
                "Primary key {$primary_key_name} must be in properties in the {$class} model."
            );
        }
        $this->primary_key_name = $primary_key_name;
    }

    /**
     * Return an error representing a SQL error.
     *
     * It is just a helper method because I found it was annoying to always do
     * the same. I don't understand why they choose this pattern for PDO
     * (probably some good reasons), but I don't find it's a great programing
     * API.
     *
     * @param \PDO|\PDOStatement $error_info_like_object The object from which we should
     *                                                   get the `errorInfo()`
     *
     * @return \Minz\Errors\DatabaseModelError
     */
    protected static function sqlStatementError($error_info_like_object)
    {
        $error_info = $error_info_like_object->errorInfo();
        return new Errors\DatabaseModelError(
            "Error in SQL statement: {$error_info[2]} ({$error_info[0]})."
        );
    }
}
