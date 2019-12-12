<?php

namespace AppTest\models\dao;

class Rabbit extends \Minz\DatabaseModel
{
    public function __construct()
    {
        parent::__construct(
            'rabbits',
            'rabbit_id',
            ['rabbit_id', 'name', 'friend_id']
        );
    }
}
