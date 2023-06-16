<?php

namespace Webubbub\models;

use Minz\Database;

/**
 * Represent the content to deliver to a specific subscriber.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'content_deliveries')]
class ContentDelivery
{
    use Database\Recordable;

    public const MAX_TRIES_COUNT = 7;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public int $subscription_id;

    #[Database\Column]
    public int $content_id;

    #[Database\Column(format: 'U')]
    public \DateTimeImmutable $created_at;

    #[Database\Column(format: 'U')]
    public \DateTimeImmutable $try_at;

    #[Database\Column]
    public int $tries_count;

    public function __construct(int $subscription_id, int $content_id)
    {
        $this->subscription_id = $subscription_id;
        $this->content_id = $content_id;
        $this->try_at = \Minz\Time::now();
        $this->tries_count = 0;
    }

    public function subscription(): Subscription
    {
        $subscription = Subscription::find($this->subscription_id);

        if (!$subscription) {
            throw new \Exception("Content delivery #{$this->id} subscription does not exist");
        }

        return $subscription;
    }

    public function content(): Content
    {
        $content = Content::find($this->content_id);

        if (!$content) {
            throw new \Exception("Content delivery #{$this->id} content does not exist");
        }

        return $content;
    }

    /**
     * Set the try_at property to a later time and increase the tries_count.
     *
     * @throws Errors\ContentDeliveryError
     *     if the maximum allowed tries count is reached
     */
    public function retryLater(): void
    {
        if ($this->tries_count >= self::MAX_TRIES_COUNT) {
            throw new Errors\ContentDeliveryError(
                'Content delivery has reached the maximum of allowed number '
                . 'of tries (' . self::MAX_TRIES_COUNT . ').'
            );
        }

        $tries_count = $this->tries_count + 1;
        $interval_seconds = pow(5, $tries_count);

        $this->try_at = \Minz\Time::fromNow($interval_seconds, 'seconds');
        $this->tries_count = $tries_count;
    }
}
