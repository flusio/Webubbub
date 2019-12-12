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

        $challenge = $intents_service->getChallengeFromCallback($intent_callback);

        if ($challenge === $expected_challenge) {
            if ($pending_request === 'subscribe') {
                $subscription->verify();
                $dao->update($subscription->id(), $subscription->toValues());
            }
        } else {
            \Minz\Log::notice(
                "{$challenge} challenge does not match ({$intent_callback})."
            );

            if ($pending_request === 'subscribe') {
                $dao->delete($subscription->id());
            }
        }
    }

    return Response::ok();
}
