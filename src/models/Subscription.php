<?php

namespace Webubbub\models;

/**
 * Represent the subscription of a subscriber (callback) to a topic.
 *
 * A Subscription has a `status` representing four different steps:
 *
 * - `new` the subscriber just made its first subscription request, it must be
 *   now validated (optional) or verified
 * - `validated` the hub checked additional details and accepted the subscription,
 *   it have to be verified
 * - `verified` the hub checked the intent of the subscriber, who confirmed the
 *   subscription
 * - `expired` the subscription expired because subscriber didn't renew before
 *   the end of lease duration
 *
 * An `expired` subscription can be deleted at any time. It might be kept for
 * a bit of time to ease a potential late renewal, allowing to skip the
 * additional validation step.
 *
 * Content distribution requests must be sent only to `verified` subscriptions.
 *
 * In addition of a status, Subscriptions have a `pending_request` attribute.
 * It stores the last request made by the subscriber (`subscribe` or `unsubscribe`)
 * and needing a verification of intent.
 *
 * If `pending_request` is `subscribe`:
 *
 * - intent is verified and the status is set to `verified`
 * - intent is declined and the Subscription is deleted, unless status is
 *   `verified` and the lease duration is not over yet.
 *
 * If `pending_request` is `unsubscribe`:
 *
 * - intent is verified and the Subscription is deleted
 * - intent is declined and nothing changes
 *
 * Pending requests should be resolved before sending a content distribution
 * request. In case it cannot (e.g. high rate of unsubscription requests), the
 * request should be delayed for the concerned subscribers.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Subscription
{
    public const MIN_LEASE_SECONDS = 86400; // Equivalent to 1 day

    public const DEFAULT_LEASE_SECONDS = 864000; // Equivalent to 10 days

    public const MAX_LEASE_SECONDS = 1296000; // Equivalent to 15 days

    public const MAX_SECRET_LENGTH = 200;

    /** @var integer|null */
    private $id;

    /** @var \DateTime|null */
    private $created_at;

    /** @var \DateTime|null */
    private $expired_at;

    /** @var string */
    private $status;

    /** @var string|null */
    private $pending_request;

    /** @var string */
    private $callback;

    /** @var string */
    private $topic;

    /** @var integer */
    private $lease_seconds;

    /** @var string|null */
    private $secret;

    /**
     * @param string $callback
     * @param string $topic
     *
     * @throws \Webubbub\models\Errors\SubscriptionError if callback, topic or
     *                                                   secret is invalid
     */
    public function __construct($callback, $topic, $lease_seconds = self::DEFAULT_LEASE_SECONDS, $secret = null)
    {
        if (!self::validateUrl($callback)) {
            throw new Errors\SubscriptionError("{$callback} callback is invalid.");
        }

        if (!self::validateUrl($topic)) {
            throw new Errors\SubscriptionError("{$topic} topic is invalid.");
        }

        if ($secret === '') {
            throw new Errors\SubscriptionError(
                'Secret must either be not given or be a cryptographically random unique secret string.'
            );
        }

        $max_secret_length = self::MAX_SECRET_LENGTH;
        if (strlen($secret) > $max_secret_length) {
            throw new Errors\SubscriptionError(
                "Secret must be equal or less than {$max_secret_length} bytes in length."
            );
        }

        $this->callback = urldecode($callback);
        $this->topic = urldecode($topic);
        $this->lease_seconds = max(
            min($lease_seconds, self::MAX_LEASE_SECONDS),
            self::MIN_LEASE_SECONDS
        );
        $this->secret = $secret;

        $this->status = 'new';
        $this->pending_request = 'subscribe';
    }

    /**
     * Return the callback to use to verify subscriber intent.
     *
     * @param string $challenge The challenge string to be verified with the subscriber
     *
     * @throws \Webubbub\models\Errors\SubscriptionError if pending request is null
     * @throws \Webubbub\models\Errors\SubscriptionError if the challenge is empty
     *
     * @return string The callback with additional parameters appended
     */
    public function intentCallback($challenge)
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

        // Note: no need to append lease_seconds in case of unsubscription.
        // Since I'm not supporting unsubscription yet, it is not implemented.
        return $this->callback . $query_char
            . "hub.mode={$this->pending_request}"
            . "&hub.topic={$this->topic}"
            . "&hub.challenge={$challenge}"
            . "&hub.lease_seconds={$this->lease_seconds}";
    }

    /**
     * Set the subscription as verified
     *
     * @throws \Webubbub\models\Errors\SubscriptionError if pending request is null
     */
    public function verify()
    {
        if ($this->pending_request === null) {
            throw new Errors\SubscriptionError(
                'Subscription cannot be verified because it has no pending requests.'
            );
        }

        $this->status = 'verified';
        $this->pending_request = null;

        $expired_at_timestamp = time() + $this->lease_seconds;
        $expired_at = new \DateTime();
        $expired_at->setTimestamp($expired_at_timestamp);
        $this->expired_at = $expired_at;
    }

    /**
     * @return string
     */
    public function callback()
    {
        return $this->callback;
    }

    /**
     * @return string
     */
    public function topic()
    {
        return $this->topic;
    }

    /**
     * @return integer
     */
    public function leaseSeconds()
    {
        return $this->lease_seconds;
    }

    /**
     * @return string|null
     */
    public function secret()
    {
        return $this->secret;
    }

    /**
     * @return string
     */
    public function status()
    {
        return $this->status;
    }

    /**
     * @return string|null
     */
    public function pendingRequest()
    {
        return $this->pending_request;
    }

    /**
     * @return \DateTime|null
     */
    public function expiredAt()
    {
        return $this->expired_at;
    }

    /**
     * Return the model values, in order to be passed to the DAO model. Note
     * that additional process might be needed (e.g. setting the required
     * `created_at` for a creation).
     *
     * @return mixed[]
     */
    public function toValues()
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'expired_at' => $this->expired_at,
            'status' => $this->status,
            'pending_request' => $this->pending_request,

            'callback' => $this->callback,
            'topic' => $this->topic,
            'lease_seconds' => $this->lease_seconds,
            'secret' => $this->secret,
        ];
    }

    /**
     * Check that an URL is valid.
     *
     * @param string $url
     *
     * @return boolean Return true if the URL is valid, false otherwise
     */
    private static function validateUrl($url)
    {
        $url_components = parse_url($url);
        if (!$url_components || !isset($url_components['scheme'])) {
            return false;
        }

        $url_scheme = $url_components['scheme'];
        return $url_scheme === 'http' || $url_scheme === 'https';
    }
}
