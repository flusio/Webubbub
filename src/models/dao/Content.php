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
                'fetched_at',
                'status',
                'url',
                'links',
                'type',
                'content',
            ]
        );
    }
}
