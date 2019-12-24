<?php

namespace Webubbub\models\dao;

use Webubbub\models;

class ContentDelivery extends \Minz\DatabaseModel
{
    public function __construct()
    {
        $content_delivery_properties = array_keys(models\ContentDelivery::PROPERTIES);
        parent::__construct('content_deliveries', 'id', $content_delivery_properties);
    }
}
