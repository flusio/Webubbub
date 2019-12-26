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
    public const MAX_TRIES_COUNT = 7;

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

    /**
     * Set the try_at property to a later time and increase the tries_count.
     *
     * @throws \Webubbub\models\Errors\ContentDeliveryError if the maximum allowed
     *                                                      tries count is reached
     */
    public function retryLater()
    {
        if ($this->tries_count >= self::MAX_TRIES_COUNT) {
            throw new Errors\ContentDeliveryError(
                'Content delivery has reached the maximum of allowed number '
                . 'of tries (' . self::MAX_TRIES_COUNT . ').'
            );
        }

        $tries_count = $this->tries_count + 1;
        $try_at = new \DateTime();
        $try_at->setTimestamp(\Minz\Time::now() + pow(5, $tries_count));

        $this->setProperty('try_at', $try_at);
        $this->setProperty('tries_count', $tries_count);
    }
}
