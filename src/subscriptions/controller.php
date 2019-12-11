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
function handleRequest($request)
{
    // We expect to receive hub.* parameters here (with dots). For some
    // reasons, PHP replaces those dots by underscores. Meh!
    // See https://www.php.net/variables.external#language.variables.external.dot-in-names
    $mode = $request->param('hub_mode', '');
    if ($mode !== 'subscribe') {
        return Response::badRequest('subscriptions#error.txt', [
            'error' => "{$mode} mode is invalid.",
        ]);
    }

    try {
        return handleSubscribe($request);
    } catch (models\Errors\SubscriptionError $e) {
        return Response::badRequest('subscriptions#error.txt', [
            'error' => $e->getMessage(),
        ]);
    } catch (\Exception $e) {
        return Response::internalServerError('subscriptions#error.txt', [
            'error' => (
                'An unexpected error occured, it’s not your fault.'
                . ' Please retry later or contact an administrator.'
            )
        ]);
    }
}

/**
 * @param \Minz\Request $request
 *
 * @throws \Webubbub\models\Errors\SubscriptionError if the data aren't valid
 *
 * @return \Minz\Response
 */
function handleSubscribe($request)
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
        $subscription = new models\Subscription(
            $callback,
            $topic,
            $lease_seconds,
            $secret
        );
        $values = $subscription->toValues();
        $values['created_at'] = time();
        $dao->create($values);
    } else {
        // Subscriptions renewal will be implemented later
    }

    return Response::accepted();
}
