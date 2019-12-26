<?php

namespace Webubbub\controllers\subscriptions;

use Minz\Response;
use Webubbub\models;
use Webubbub\services;

/**
 * Verify intents of subscriptions with pending request;
 *
 * @param \Minz\Request $request
 *
 * @return \Minz\Response
 */
function verify($request)
{
    $dao = new models\dao\Subscription();

    $subscriptions_values = $dao->listWherePendingRequests();

    foreach ($subscriptions_values as $subscription_values) {
        $subscription = new models\Subscription($subscription_values);

        $expected_challenge = sha1(rand());
        $intent_callback = $subscription->intentCallback($expected_challenge);

        $curl_response = services\Curl::get($intent_callback);

        $http_code_successful = (
            $curl_response->http_code >= 200 &&
            $curl_response->http_code < 300
        );
        $challenges_match = $curl_response->content === $expected_challenge;
        if ($http_code_successful && $challenges_match) {
            if ($subscription->pending_request === 'subscribe') {
                $subscription->verify();
                $dao->update($subscription->id, $subscription->toValues());
            } elseif ($subscription->pending_request === 'unsubscribe') {
                $dao->delete($subscription->id);
            }
        } else {
            if ($http_code_successful) {
                \Minz\Log::notice(
                    "[subscriptions#verify] {$curl_response->content} challenge does "
                    . "not match ({$intent_callback})."
                );
            } else {
                \Minz\Log::notice(
                    "[subscriptions#verify] {$curl_response->http_code} HTTP code is "
                    . "not successful ({$intent_callback})."
                );
            }

            $subscription->cancelRequest();
            $dao->update($subscription->id, $subscription->toValues());
        }
    }

    return Response::ok();
}

/**
 * @param \Minz\Request $request
 *
 * @return \Minz\Response
 */
function expire($request)
{
    $dao = new models\dao\Subscription();
    $verified_subscriptions_values = $dao->listBy(['status' => 'verified']);
    foreach ($verified_subscriptions_values as $subscription_values) {
        $subscription = new models\Subscription($subscription_values);
        if ($subscription->shouldExpire()) {
            $subscription->expire();
            $dao->update($subscription->id, $subscription->toValues());
        }
    }

    return Response::ok();
}

/**
 * @param \Minz\Request $request
 *
 * @return \Minz\Response
 */
function items($request)
{
    $dao = new models\dao\Subscription();
    $subscriptions = $dao->listAll();
    return Response::ok('subscriptions/items.txt', [
        'subscriptions' => $subscriptions,
    ]);
}
