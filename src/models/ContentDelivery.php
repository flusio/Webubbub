<?php

namespace Webubbub\models;

/**
 * Represent the content to deliver to a specific subscriber.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ContentDelivery extends \Minz\Model
{
    public const PROPERTIES = [
        'id' => 'integer',

        'subscription_id' => [
            'type' => 'integer',
            'required' => true,
        ],

        'content_id' => [
            'type' => 'integer',
            'required' => true,
        ],

        'created_at' => 'datetime',

        'try_at' => [
            'type' => 'datetime',
            'required' => true,
        ],

        'tries_count' => [
            'type' => 'integer',
            'required' => true,
        ],
    ];

    /**
     * @param integer $subscription_id
     * @param integer $content_id
     *
     * @throws \Minz\Error\ModelPropertyError if one of the value is invalid
     *
     * @return \Webubbub\models\ContentDelivery
     */
    public static function new($subscription_id, $content_id)
    {
        return new self([
            'subscription_id' => $subscription_id,
            'content_id' => $content_id,
            'try_at' => \Minz\Time::now(),
            'tries_count' => 0,
        ]);
    }

    /**
     * Initialize a ContentDelivery from values (usually from database).
     *
     * @param array $values
     *
     * @throws \Minz\Error\ModelPropertyError if one of the value is invalid
     */
    public function __construct($values)
    {
        parent::__construct(self::PROPERTIES);
        $this->fromValues($values);
    }
}
