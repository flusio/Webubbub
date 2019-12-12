<?php

namespace AppTest\models\dao;

class Friend extends \Minz\DatabaseModel
{
    public function __construct()
    {
        parent::__construct(
            'friends',
            'id',
            ['id', 'name', 'address']
        );
    }
}
