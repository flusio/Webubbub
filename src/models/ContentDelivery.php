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

        'created_at' => [
            'type' => 'datetime',
            'format' => 'U',
        ],

        'try_at' => [
            'type' => 'datetime',
            'format' => 'U',
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
        $interval_seconds = pow(5, $tries_count);
        $try_at = \Minz\Time::now();
        $try_at->modify("+{$interval_seconds} seconds");

        $this->try_at = $try_at;
        $this->tries_count = $tries_count;
    }
}
