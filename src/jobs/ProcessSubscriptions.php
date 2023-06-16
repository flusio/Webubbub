<?php

namespace Webubbub\jobs;

use Minz\Job;
use Webubbub\models;
use Webubbub\services;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ProcessSubscriptions extends Job
{
    public static function install(): void
    {
        $job = new self();
        if (!self::existsBy(['name' => $job->name])) {
            $perform_at = \Minz\Time::now();
            $job->performLater($perform_at);
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->frequency = '+5 seconds';
    }

    public function perform(): void
    {
        $this->performValidate();
        $this->performVerify();
        $this->performExpire();
    }

    /**
     * Validate subscriptions based on allowed topics.
     */
    public function performValidate(): void
    {
        $subscriptions = models\Subscription::listBy([
            'status' => 'new',
        ]);

        foreach ($subscriptions as $subscription) {
            if ($subscription->isAllowed()) {
                $subscription->status = 'validated';
                $subscription->save();

                \Minz\Log::notice("subscription #{$subscription->id}: validated");
            } else {
                \Minz\Log::notice("subscription #{$subscription->id}: not validated (not allowed)");

                $deny_callback = $subscription->denyCallback('The topic is not allowed on this hub (private hub)');
                $curl_response = services\Curl::get($deny_callback);

                $http_code_successful = (
                    $curl_response->http_code >= 200 &&
                    $curl_response->http_code < 300
                );

                if ($http_code_successful || $subscription->created_at <= \Minz\Time::ago(1, 'day')) {
                    $subscription->remove();

                    \Minz\Log::notice("subscription #{$subscription->id}: removed");
                }
            }
        }
    }

    /**
     * Verify intents of subscriptions with pending request.
     */
    public function performVerify(): void
    {
        $subscriptions = models\Subscription::listWherePendingRequests();

        foreach ($subscriptions as $subscription) {
            $expected_challenge = \Minz\Random::hex(64);
            $intent_callback = $subscription->intentCallback($expected_challenge);

            $curl_response = services\Curl::get($intent_callback);

            $http_code_successful = (
                $curl_response->http_code >= 200 &&
                $curl_response->http_code < 300
            );
            $challenges_match = $curl_response->content === $expected_challenge;
            if ($http_code_successful && $challenges_match) {
                \Minz\Log::notice("subscription #{$subscription->id}: "
                    . "{$subscription->pending_request} succeeded");

                if ($subscription->pending_request === 'subscribe') {
                    $subscription->verify();
                    $subscription->save();
                } elseif ($subscription->pending_request === 'unsubscribe') {
                    $subscription->remove();
                }
            } else {
                if ($http_code_successful) {
                    \Minz\Log::warning("subscription #{$subscription->id}: "
                        . "{$subscription->pending_request} failed");
                } else {
                    \Minz\Log::warning("subscription #{$subscription->id}: "
                        . "{$subscription->pending_request} failed, "
                        . "erroneous HTTP code {$curl_response->http_code}");
                }

                $subscription->cancelRequest();
                $subscription->save();
            }
        }
    }

    /**
     * Mark as expired relevant verified subscriptions.
     */
    public function performExpire(): void
    {
        $verified_subscriptions = models\Subscription::listBy([
            'status' => 'verified',
        ]);

        foreach ($verified_subscriptions as $subscription) {
            if ($subscription->shouldExpire()) {
                $subscription->expire();
                $subscription->save();

                \Minz\Log::notice("subscription #{$subscription->id}: expired");
            }
        }
    }
}
