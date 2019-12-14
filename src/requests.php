<?php

namespace Webubbub\controllers\requests;

use Minz\Response;
use Webubbub\models;

/**
 * Handle the subscribers requests (subscribe/unsubscribe) and publishers
 * "publish" request to the hub.
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
    if ($mode !== 'subscribe' && $mode !== 'unsubscribe' && $mode !== 'publish') {
        return Response::badRequest('requests/error.txt', [
            'error' => "{$mode} mode is invalid.",
        ]);
    }

    if ($mode === 'subscribe') {
        return subscribe($request);
    } elseif ($mode === 'unsubscribe') {
        return unsubscribe($request);
    } elseif ($mode === 'publish') {
        return publish($request);
    }
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
        // New subscription, not in database
        try {
            $subscription = new models\Subscription(
                $callback,
                $topic,
                $lease_seconds,
                $secret
            );
        } catch (models\Errors\SubscriptionError $e) {
            return Response::badRequest('requests/error.txt', [
                'error' => $e->getMessage(),
            ]);
        }

        $values = $subscription->toValues();
        $values['created_at'] = \Minz\Time::now();
        $dao->create($values);
    } else {
        // Subscription renewal
        $subscription = models\Subscription::fromValues($subscription_values);
        $subscription->renew($lease_seconds, $secret);
        $dao->update($subscription->id(), $subscription->toValues());
    }

    return Response::accepted();
}

/**
 * Handle the "unsubscribe" requests to the hub.
 *
 * @param \Minz\Request $request
 *
 * @return \Minz\Response
 */
function unsubscribe($request)
{
    $callback = $request->param('hub_callback', '');
    $topic = $request->param('hub_topic', '');

    $dao = new models\dao\Subscription();
    $subscription_values = $dao->findBy([
        'callback' => $callback,
        'topic' => $topic,
    ]);

    if ($subscription_values) {
        try {
            $subscription = models\Subscription::fromValues($subscription_values);
        } catch (models\Errors\SubscriptionError $e) {
            return Response::badRequest('requests/error.txt', [
                'error' => $e->getMessage(),
            ]);
        }

        $subscription->requestUnsubscription();

        $dao->update($subscription->id(), $subscription->toValues());
    } else {
        // We received an unsubscription for an unknown subscription. We return
        // an error message to indicate we'll not process any verification.
        return Response::badRequest('requests/error.txt', [
            'error' => 'Unknown subscription.',
        ]);
    }

    return Response::accepted();
}

/**
 * Handle the "publish" requests to the hub.
 *
 * @param \Minz\Request $request
 *
 * @return \Minz\Response
 */
function publish($request)
{
    $url = $request->param('hub_url', '');

    $dao = new models\dao\Content();
    $content_values = $dao->findBy(['url' => $url]);

    if ($content_values) {
        // we already know about this content, it will be delivered soon
        return Response::ok();
    }

    try {
        $content = new models\Content($url);
    } catch (models\Errors\ContentError $e) {
        return Response::badRequest('requests/error.txt', [
            'error' => $e->getMessage(),
        ]);
    }

    $values = $content->toValues();
    $values['created_at'] = \Minz\Time::now();
    $dao->create($values);

    return Response::ok();
}
