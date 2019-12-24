<?php

namespace Webubbub\controllers\subscriptions;

use Minz\Response;
use Webubbub\models;

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
