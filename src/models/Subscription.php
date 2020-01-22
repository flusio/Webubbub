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
class Subscription extends \Minz\Model
{
    public const MIN_LEASE_SECONDS = 86400; // Equivalent to 1 day

    public const DEFAULT_LEASE_SECONDS = 864000; // Equivalent to 10 days

    public const MAX_LEASE_SECONDS = 1296000; // Equivalent to 15 days

    public const MAX_SECRET_LENGTH = 200;

    public const VALID_STATUSES = ['new', 'validated', 'verified', 'expired'];

    public const VALID_REQUESTS = ['subscribe', 'unsubscribe'];

    public const PROPERTIES = [
        'id' => 'integer',

        'created_at' => 'datetime',

        'expired_at' => 'datetime',

        'status' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\Webubbub\models\Subscription::validateStatus',
        ],

        'callback' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\Webubbub\models\Subscription::validateUrl',
        ],

        'topic' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\Webubbub\models\Subscription::validateUrl',
        ],

        'lease_seconds' => [
            'type' => 'integer',
            'required' => true,
            'validator' => '\Webubbub\models\Subscription::validateLeaseSeconds',
        ],

        'secret' => [
            'type' => 'string',
            'validator' => '\Webubbub\models\Subscription::validateSecret',
        ],

        'pending_request' => [
            'type' => 'string',
            'validator' => '\Webubbub\models\Subscription::validateRequest',
        ],

        'pending_lease_seconds' => [
            'type' => 'integer',
            'validator' => '\Webubbub\models\Subscription::validateLeaseSeconds',
        ],

        'pending_secret' => [
            'type' => 'string',
            'validator' => '\Webubbub\models\Subscription::validateSecret',
        ],
    ];

    /**
     * @param string $callback
     * @param string $topic
     * @param integer $lease_seconds
     * @param string|null $secret
     *
     * @throws \Minz\Error\ModelPropertyError if one of the value is invalid
     *
     * @return \Webubbub\models\Subscription
     */
    public static function new($callback, $topic, $lease_seconds = self::DEFAULT_LEASE_SECONDS, $secret = null)
    {
        return new Subscription([
            'callback' => urldecode($callback),
            'topic' => urldecode($topic),
            'lease_seconds' => self::boundLeaseSeconds($lease_seconds),
            'secret' => $secret,
            'status' => 'new',
            'pending_request' => 'subscribe',
        ]);
    }

    /**
     * Initialize a Subscription from values (usually from database).
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

        $this->setProperty('status', 'verified');
        $this->setProperty('pending_request', null);

        $expired_at = \Minz\Time::fromNow($this->lease_seconds, 'seconds');
        $this->setProperty('expired_at', $expired_at);

        if ($this->pending_lease_seconds) {
            $this->setProperty('lease_seconds', $this->pending_lease_seconds);
            $this->setProperty('pending_lease_seconds', null);
        }
        if ($this->pending_secret) {
            $this->setProperty('secret', $this->pending_secret);
            $this->setProperty('pending_secret', null);
        }
    }

    /**
     * Renew a subscription by setting (pending) lease seconds and secret. It
     * also sets the pending request to "subscribe".
     *
     * @param integer $lease_seconds
     * @param string|null $secret
     *
     * @throws \Minz\Error\ModelPropertyError if one of the value is invalid
     */
    public function renew($lease_seconds = self::DEFAULT_LEASE_SECONDS, $secret = null)
    {
        $this->setProperty('pending_lease_seconds', self::boundLeaseSeconds($lease_seconds));
        $this->setProperty('pending_secret', $secret);
        $this->setProperty('pending_request', 'subscribe');
    }

    /**
     * @return boolean True if the subscription should expire, false otherwise
     */
    public function shouldExpire()
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
     * @throws \Webubbub\models\Errors\SubscriptionError if status is not verified
     * @throws \Webubbub\models\Errors\SubscriptionError if expired_at is not over
     */
    public function expire()
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

        $this->setProperty('status', 'expired');
    }

    /**
     * Set the pending request to "unsubscribe"
     */
    public function requestUnsubscription()
    {
        $this->setProperty('pending_request', 'unsubscribe');
    }

    /**
     * Set the pending request to null
     */
    public function cancelRequest()
    {
        $this->setProperty('pending_request', null);
        $this->setProperty('pending_lease_seconds', null);
        $this->setProperty('pending_secret', null);
    }

    /**
     * @param integer $lease_seconds
     *
     * @return integer
     */
    private static function boundLeaseSeconds($lease_seconds)
    {
        return max(
            min($lease_seconds, self::MAX_LEASE_SECONDS),
            self::MIN_LEASE_SECONDS
        );
    }

    /**
     * Check that an URL is valid.
     *
     * @param string $url
     *
     * @return boolean Return true if the URL is valid, false otherwise
     */
    public static function validateUrl($url)
    {
        $url_components = parse_url($url);
        if (!$url_components || !isset($url_components['scheme'])) {
            return false;
        }

        $url_scheme = $url_components['scheme'];
        return $url_scheme === 'http' || $url_scheme === 'https';
    }

    /**
     * Check the given status is valid.
     *
     * @param string $status
     *
     * @return boolean|string It returns true if the status is valid, or a string
     *                        explaining the error otherwise.
     */
    public static function validateStatus($status)
    {
        if (!in_array($status, self::VALID_STATUSES)) {
            $statuses_as_string = implode(', ', self::VALID_STATUSES);
            return "valid values are {$statuses_as_string}";
        }

        return true;
    }

    /**
     * Check the given request is valid.
     *
     * @param string $request
     *
     * @return boolean|string It returns true if the request is valid, or a string
     *                        explaining the error otherwise
     */
    public static function validateRequest($request)
    {
        if (!in_array($request, self::VALID_REQUESTS)) {
            $requests_as_string = implode(', ', self::VALID_REQUESTS);
            return "valid values are {$requests_as_string}";
        }

        return true;
    }

    /**
     * Check the given lease is between MIN_LEASE_SECONDS and MAX_LEASE_SECONDS.
     *
     * The lease seconds should always be bound with the static `boundLeaseSeconds`
     * method.
     *
     * @param integer $lease_seconds
     *
     * @return boolean It returns true if the lease value is valid, false otherwise
     */
    public static function validateLeaseSeconds($lease_seconds)
    {
        return (
            $lease_seconds >= self::MIN_LEASE_SECONDS &&
            $lease_seconds <= self::MAX_LEASE_SECONDS
        );
    }

    /**
     * Check the given secret is valid.
     *
     * @param string $secret
     *
     * @return boolean|string It returns true if the secret is valid, or a string
     *                        explaining the error otherwise
     */
    public static function validateSecret($secret)
    {
        if ($secret === '') {
            return 'must either be not given or be a cryptographically random unique secret string';
        }

        if (strlen($secret) > self::MAX_SECRET_LENGTH) {
            return 'must be equal or less than 200 bytes in length';
        }

        return true;
    }
}
