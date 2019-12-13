<?php

namespace Webubbub\models\dao;

class Content extends \Minz\DatabaseModel
{
    public function __construct()
    {
        parent::__construct(
            'contents',
            'id',
            [
                'id',
                'created_at',
                'url',
            ]
        );
    }
}
