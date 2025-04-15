<?php

namespace Webubbub\models;

use Minz\Database;
use Minz\Validable;

/**
 * Represent the subscription of a subscriber (callback) to a topic.
 *
 * A Subscription has a `status` representing four different steps:
 *
 * - `new` the subscriber just made its first subscription request, it must be
 *   now validated (optional) or verified
 * - `validated` the hub checked additional details and accepted the subscription,
 *   it has to be verified
 * - `verified` the hub checked the intent of the subscriber, who confirmed the
 *   subscription
 * - `expired` the subscription expired because subscriber didn't renew before
 *   the end of lease duration
 *
 * Content distribution requests must be sent only to `verified` subscriptions.
 *
 * In addition of a status, Subscriptions have a `pending_request` attribute.
 * It stores the last request made by the subscriber (`subscribe` or `unsubscribe`)
 * and needing a verification of intent.
 *
 * If intent is verified, there are two cases:
 *
 * - `pending_request` is `subscribe`, the status is set to `verified`
 * - `pending_request` is `unsubscribe`, the Subscription is deleted
 *
 * If intent is not verified, the pending request is set to null and the status
 * is left unchanged.
 *
 * Pending requests should be resolved before sending a content distribution
 * request. In case it cannot (e.g. high rate of unsubscription requests), the
 * request should be delayed for the concerned subscribers.
 *
 * `new`, `validated` and `renew` subscriptions with no pending_request can be
 * deleted but should be after a time of retention (based on `created_at` or
 * `expired_at` attributes).
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'subscriptions')]
class Subscription
{
    use Database\Recordable;
    use Validable;

    public const MIN_LEASE_SECONDS = 86400; // Equivalent to 1 day

    public const DEFAULT_LEASE_SECONDS = 864000; // Equivalent to 10 days

    public const MAX_LEASE_SECONDS = 1296000; // Equivalent to 15 days

    public const MAX_SECRET_LENGTH = 200;

    public const VALID_STATUSES = ['new', 'validated', 'verified', 'expired'];

    public const VALID_REQUESTS = ['subscribe', 'unsubscribe'];

    #[Database\Column]
    public int $id;

    #[Database\Column(format: 'U')]
    public \DateTimeImmutable $created_at;

    #[Database\Column(format: 'U')]
    public ?\DateTimeImmutable $expired_at = null;

    #[Database\Column]
    #[Validable\Inclusion(in: self::VALID_STATUSES, message: 'status "{value}" is invalid')]
    public string $status;

    #[Database\Column]
    #[Validable\Presence(message: 'callback "{value}" is invalid URL')]
    #[Validable\Url(message: 'callback "{value}" is invalid URL')]
    public string $callback;

    #[Database\Column]
    #[Validable\Presence(message: 'topic "{value}" is invalid URL')]
    #[Validable\Url(message: 'topic "{value}" is invalid URL')]
    public string $topic;

    #[Database\Column]
    #[Validable\Comparison(
        greater_or_equal: self::MIN_LEASE_SECONDS,
        less_or_equal: self::MAX_LEASE_SECONDS,
        message: 'lease_seconds must be between {greater_or_equal} and {less_or_equal}'
    )]
    public int $lease_seconds;

    #[Database\Column]
    #[Validable\Length(
        max: self::MAX_SECRET_LENGTH,
        message: 'secret must be less or equal than 200 bytes in length'
    )]
    public ?string $secret = null;

    #[Database\Column]
    #[Validable\Inclusion(in: self::VALID_REQUESTS, message: '{value} is not a valid status.')]
    public ?string $pending_request = null;

    #[Database\Column]
    #[Validable\Comparison(
        greater_or_equal: self::MIN_LEASE_SECONDS,
        less_or_equal: self::MAX_LEASE_SECONDS,
        message: 'lease_seconds must be between {greater_or_equal} and {less_or_equal}'
    )]
    public ?int $pending_lease_seconds = null;

    #[Database\Column]
    #[Validable\Length(
        max: self::MAX_SECRET_LENGTH,
        message: 'secret must be less or equal than 200 bytes in length'
    )]
    public ?string $pending_secret = null;

    public function __construct(
        string $callback,
        string $topic,
        int $lease_seconds = self::DEFAULT_LEASE_SECONDS,
        ?string $secret = null
    ) {
        $this->callback = $callback;
        $this->topic = $topic;
        $this->lease_seconds = self::boundLeaseSeconds($lease_seconds);
        $this->secret = $secret;
        $this->status = 'new';
        $this->pending_request = 'subscribe';
    }

    /**
     * Return wheter a subscription is allowed on the hub or not.
     */
    public function isAllowed(): bool
    {
        $allowed_origins = \Minz\Configuration::$application['allowed_topic_origins'];
        assert(is_string($allowed_origins));

        if ($allowed_origins === '') {
            // Empty value means open hub
            return true;
        }

        $allowed_origins = explode(',', $allowed_origins);

        foreach ($allowed_origins as $allowed_origin) {
            $allowed_origin = trim($allowed_origin);

            $origin_length = strlen($allowed_origin);
            if (substr($this->topic, 0, $origin_length) === $allowed_origin) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return the callback to use to verify subscriber intent.
     *
     * @param string $challenge The challenge string to be verified with the subscriber
     *
     * @throws Errors\SubscriptionError if pending request is null
     * @throws Errors\SubscriptionError if the challenge is empty
     *
     * @return non-empty-string The callback with additional parameters appended
     */
    public function intentCallback(string $challenge): string
    {
        if ($this->pending_request === null) {
            throw new Errors\SubscriptionError(
                'intentCallback cannot be called when pending request is null.'
            );
        }

        if (!$challenge) {
            throw new Errors\SubscriptionError(
                'intentCallback cannot be called with an empty challenge.'
            );
        }

        if (strpos($this->callback, "?")) {
            $query_char = '&';
        } else {
            $query_char = '?';
        }

        $intent_callback = $this->callback . $query_char
                         . "hub.mode={$this->pending_request}"
                         . "&hub.topic={$this->topic}"
                         . "&hub.challenge={$challenge}";

        if ($this->pending_request === 'subscribe') {
            $intent_callback .= "&hub.lease_seconds={$this->lease_seconds}";
        }

        return $intent_callback;
    }

    /**
     * Return the callback to use to deny subscriber.
     *
     * @return non-empty-string
     */
    public function denyCallback(string $reason): string
    {
        if (strpos($this->callback, "?")) {
            $query_char = '&';
        } else {
            $query_char = '?';
        }

        if (!$reason) {
            $reason = 'Topic denied';
        }

        $reason = urlencode($reason);
        $deny_callback = $this->callback . $query_char
                       . "hub.mode=denied"
                       . "&hub.topic={$this->topic}"
                       . "&hub.reason={$reason}";

        return $deny_callback;
    }

    /**
     * Set the subscription as verified
     *
     * @throws Errors\SubscriptionError if pending request is null
     */
    public function verify(): void
    {
        if ($this->pending_request === null) {
            throw new Errors\SubscriptionError(
                'Subscription cannot be verified because it has no pending requests.'
            );
        }

        $this->status = 'verified';
        $this->pending_request = null;

        $expired_at = \Minz\Time::fromNow($this->lease_seconds, 'seconds');
        $this->expired_at = $expired_at;

        if ($this->pending_lease_seconds) {
            $this->lease_seconds = $this->pending_lease_seconds;
            $this->pending_lease_seconds = null;
        }

        if ($this->pending_secret) {
            $this->secret = $this->pending_secret;
            $this->pending_secret = null;
        }
    }

    /**
     * Renew a subscription by setting (pending) lease seconds and secret. It
     * also sets the pending request to "subscribe".
     */
    public function renew(int $lease_seconds = self::DEFAULT_LEASE_SECONDS, ?string $secret = null): void
    {
        $this->pending_lease_seconds = self::boundLeaseSeconds($lease_seconds);
        $this->pending_secret = $secret;
        $this->pending_request = 'subscribe';
    }

    public function shouldExpire(): bool
    {
        if ($this->expired_at) {
            return $this->expired_at <= \Minz\Time::now();
        } else {
            return false;
        }
    }

    /**
     * Set the status to expired
     *
     * @throws Errors\SubscriptionError if status is not verified
     * @throws Errors\SubscriptionError if expired_at is not over
     */
    public function expire(): void
    {
        if ($this->status !== 'verified') {
            throw new Errors\SubscriptionError(
                "Subscription cannot expire with {$this->status} status."
            );
        }

        if (!$this->shouldExpire()) {
            throw new Errors\SubscriptionError(
                'Subscription expiration date is not over yet.'
            );
        }

        $this->status = 'expired';
    }

    /**
     * Set the pending request to "unsubscribe"
     */
    public function requestUnsubscription(): void
    {
        $this->pending_request = 'unsubscribe';
    }

    /**
     * Set the pending request to null
     */
    public function cancelRequest(): void
    {
        $this->pending_request = null;
        $this->pending_lease_seconds = null;
        $this->pending_secret = null;
    }

    private static function boundLeaseSeconds(int $lease_seconds): int
    {
        return max(
            min($lease_seconds, self::MAX_LEASE_SECONDS),
            self::MIN_LEASE_SECONDS
        );
    }

    /**
     * Return Subscriptions where pending_request is not null and status is
     * not new.
     *
     * @return Subscription[]
     */
    public static function listWherePendingRequests(): array
    {
        $sql = <<<SQL
            SELECT * FROM subscriptions
            WHERE pending_request IS NOT NULL
            AND status != 'new'
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->query($sql);
        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Delete the Subscriptions that can be deleted and return the number of
     * deletions.
     */
    public static function deleteOldSubscriptions(): int
    {
        $sql = <<<'SQL'
            DELETE FROM subscriptions
            WHERE (status = 'expired' AND expired_at < :older_than)
            OR (status = 'new' AND created_at < :older_than)
            OR (status = 'validated' AND created_at < :older_than)
        SQL;

        $older_than = \Minz\Time::ago(1, 'week');

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':older_than' => $older_than->format('U'),
        ]);

        return $statement->rowCount();
    }
}
