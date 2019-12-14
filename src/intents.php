<?php

namespace Webubbub\controllers\intents;

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
    $intents_service = new services\IntentsService();
    $dao = new models\dao\Subscription();

    $subscriptions_values = $dao->listWherePendingRequests();

    foreach ($subscriptions_values as $subscription_values) {
        $subscription = models\Subscription::fromValues($subscription_values);
        $pending_request = $subscription->pendingRequest();

        $expected_challenge = $intents_service->generateChallenge();
        $intent_callback = $subscription->intentCallback($expected_challenge);

        $curl_response = services\Curl::get($intent_callback);

        $http_code_successful = (
            $curl_response->http_code >= 200 &&
            $curl_response->http_code < 300
        );
        $challenges_match = $curl_response->content === $expected_challenge;
        if ($http_code_successful && $challenges_match) {
            if ($pending_request === 'subscribe') {
                $subscription->verify();
                $dao->update($subscription->id(), $subscription->toValues());
            } elseif ($pending_request === 'unsubscribe') {
                $dao->delete($subscription->id());
            }
        } else {
            if ($http_code_successful) {
                \Minz\Log::notice(
                    "[intents#verify] {$curl_response->content} challenge does not match ({$intent_callback})."
                );
            } else {
                \Minz\Log::notice(
                    "[intents#verify] {$curl_response->http_code} HTTP code is not successful ({$intent_callback})."
                );
            }

            $subscription->cancelRequest();
            $dao->update($subscription->id(), $subscription->toValues());
        }
    }

    return Response::ok();
}
