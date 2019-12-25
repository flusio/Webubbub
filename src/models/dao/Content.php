<?php

namespace Webubbub\models\dao;

use Webubbub\models;

class Content extends \Minz\DatabaseModel
{
    public function __construct()
    {
        $content_properties = array_keys(models\Content::PROPERTIES);
        parent::__construct('contents', 'id', $content_properties);
    }
}
