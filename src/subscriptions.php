<?php

namespace Webubbub\controllers\subscriptions;

use Minz\Response;
use Webubbub\models;

/**
 * Handle the subscribers requests (subscribe/unsubscribe) to the hub.
 *
 * @param \Minz\Request $request
 *
 * @return \Minz\Response
 */
function handle($request)
{
    // We expect to receive hub.* parameters here (with dots). For some
    // reasons, PHP replaces those dots by underscores. Meh!
    // See https://www.php.net/variables.external#language.variables.external.dot-in-names
    $mode = $request->param('hub_mode', '');
    if ($mode !== 'subscribe') {
        return Response::badRequest('subscriptions/error.txt', [
            'error' => "{$mode} mode is invalid.",
        ]);
    }

    return subscribe($request);
}

/**
 * Handle the "subscribe" requests to the hub.
 *
 * @param \Minz\Request $request
 *
 * @return \Minz\Response
 */
function subscribe($request)
{
    $callback = $request->param('hub_callback', '');
    $topic = $request->param('hub_topic', '');
    $lease_seconds = $request->param('hub_lease_seconds');
    $secret = $request->param('hub_secret');

    $dao = new models\dao\Subscription();
    $subscription_values = $dao->findBy([
        'callback' => $callback,
        'topic' => $topic,
    ]);

    if (!$subscription_values) {
        try {
            $subscription = new models\Subscription(
                $callback,
                $topic,
                $lease_seconds,
                $secret
            );
        } catch (models\Errors\SubscriptionError $e) {
            return Response::badRequest('subscriptions/error.txt', [
                'error' => $e->getMessage(),
            ]);
        }

        $values = $subscription->toValues();
        $values['created_at'] = time();
        $dao->create($values);
    } else {
        // Subscriptions renewal will be implemented later
    }

    return Response::accepted();
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
