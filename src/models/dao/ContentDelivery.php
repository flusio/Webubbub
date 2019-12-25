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

    /**
     * Create instances of ContentDelivery in database.
     *
     * Database errors are catched and logged to syslog.
     *
     * @param array $list_values
     *
     * @return integer[] List of created ContentDelivery ids
     */
    public function createList($list_values)
    {
        $ids = [];
        foreach ($list_values as $values) {
            try {
                $ids[] = $this->create($values);
            } catch (\Minz\Errors\DatabaseModelError $e) {
                \Minz\Log::error(
                    "[ContentDelivery#createList] Failed while create a ContentDelivery: {$e->getMessage()}"
                );
            }
        }
        return $ids;
    }
}
