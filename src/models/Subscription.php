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
class Subscription
{
    public const MIN_LEASE_SECONDS = 86400; // Equivalent to 1 day

    public const DEFAULT_LEASE_SECONDS = 864000; // Equivalent to 10 days

    public const MAX_LEASE_SECONDS = 1296000; // Equivalent to 15 days

    public const MAX_SECRET_LENGTH = 200;

    public const VALID_STATUSES = ['new', 'validated', 'verified', 'expired'];

    public const VALID_REQUESTS = ['subscribe', 'unsubscribe'];

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

        $this->callback = urldecode($callback);
        $this->topic = urldecode($topic);
        $this->setLeaseSeconds($lease_seconds);
        $this->setSecret($secret);

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

        $this->status = 'verified';
        $this->pending_request = null;

        $expired_at_timestamp = time() + $this->lease_seconds;
        $expired_at = new \DateTime();
        $expired_at->setTimestamp($expired_at_timestamp);
        $this->expired_at = $expired_at;
    }

    /**
     * Renew a subscription by setting lease seconds and secret. It also set
     * the pending request to "subscribe"
     *
     * @param integer $lease_seconds
     * @param string|null $secret
     */
    public function renew($lease_seconds = self::DEFAULT_LEASE_SECONDS, $secret = null)
    {
        $this->setLeaseSeconds($lease_seconds);
        $this->setSecret($secret);
        $this->pending_request = 'subscribe';
    }

    /**
     * Set the pending request to "unsubscribe"
     */
    public function requestUnsubscription()
    {
        $this->pending_request = 'unsubscribe';
    }

    /**
     * Set the pending request to null
     */
    public function cancelRequest()
    {
        $this->pending_request = null;
    }

    /**
     * @return integer|null
     */
    public function id()
    {
        return $this->id;
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
    public function createdAt()
    {
        return $this->created_at;
    }

    /**
     * @return \DateTime|null
     */
    public function expiredAt()
    {
        return $this->expired_at;
    }

    /**
     * @param integer $lease_seconds
     */
    private function setLeaseSeconds($lease_seconds)
    {
        $this->lease_seconds = max(
            min($lease_seconds, self::MAX_LEASE_SECONDS),
            self::MIN_LEASE_SECONDS
        );
    }

    /**
     * @param string|null $secret
     *
     * @throws \Webubbub\models\Errors\SubscriptionError if secret is invalid
     */
    private function setSecret($secret)
    {
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

        $this->secret = $secret;
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
            'created_at' => $this->created_at ? $this->created_at->getTimestamp() : null,
            'expired_at' => $this->expired_at ? $this->expired_at->getTimestamp() : null,
            'status' => $this->status,
            'pending_request' => $this->pending_request,

            'callback' => $this->callback,
            'topic' => $this->topic,
            'lease_seconds' => $this->lease_seconds,
            'secret' => $this->secret,
        ];
    }

    /**
     * Create a Subscription object from given values.
     *
     * It should be used with values coming from the database.
     *
     * @param mixed[] $values
     *
     * @throws \Webubbub\models\Errors\SubscriptionError if a required value is missing
     *                                                   or is not valid
     *
     * @return \Webubbub\models\Subscription
     */
    public static function fromValues($values)
    {
        $required_values = [
            'id',
            'callback',
            'topic',
            'lease_seconds',
            'status',
            'created_at'
        ];
        foreach ($required_values as $value_name) {
            if (!isset($values[$value_name])) {
                throw new Errors\SubscriptionError(
                    "{$value_name} value is required."
                );
            }
        }

        $integer_values = ['id', 'lease_seconds', 'created_at', 'expired_at'];
        foreach ($integer_values as $value_name) {
            if (
                isset($values[$value_name]) &&
                !filter_var($values[$value_name], FILTER_VALIDATE_INT)
            ) {
                throw new Errors\SubscriptionError(
                    "{$value_name} value must be an integer."
                );
            }
        }

        if (!in_array($values['status'], self::VALID_STATUSES)) {
            throw new Errors\SubscriptionError(
                "{$values['status']} is not a valid status."
            );
        }

        if (
            isset($values['pending_request']) &&
            !in_array($values['pending_request'], self::VALID_REQUESTS)
        ) {
            throw new Errors\SubscriptionError(
                "{$values['pending_request']} is not a valid pending request."
            );
        }

        if (isset($values['secret'])) {
            $secret = $values['secret'];
        } else {
            $secret = null;
        }

        $subscription = new self(
            $values['callback'],
            $values['topic'],
            intval($values['lease_seconds']),
            $secret
        );

        $subscription->id = intval($values['id']);
        $subscription->status = $values['status'];

        $created_at = new \DateTime();
        $created_at->setTimestamp(intval($values['created_at']));
        $subscription->created_at = $created_at;

        if (isset($values['pending_request'])) {
            $subscription->pending_request = $values['pending_request'];
        }

        if (isset($values['expired_at'])) {
            $expired_at = new \DateTime();
            $expired_at->setTimestamp(intval($values['expired_at']));
            $subscription->expired_at = $expired_at;
        }

        return $subscription;
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
