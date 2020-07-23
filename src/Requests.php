<?php

namespace Webubbub;

use Minz\Response;

class Requests
{
    /**
     * Handle the subscribers requests (subscribe/unsubscribe) and publishers
     * "publish" request to the hub.
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function handle($request)
    {
        // We expect to receive hub.* parameters here (with dots). For some
        // reasons, PHP replaces those dots by underscores. Meh!
        // See https://www.php.net/variables.external#language.variables.external.dot-in-names
        $mode = $request->param('hub_mode', '');
        if ($mode !== 'subscribe' && $mode !== 'unsubscribe' && $mode !== 'publish') {
            return Response::badRequest('requests/error.txt', [
                'errors' => ["{$mode} mode is invalid."],
            ]);
        }

        if ($mode === 'subscribe') {
            return $this->subscribe($request);
        } elseif ($mode === 'unsubscribe') {
            return $this->unsubscribe($request);
        } elseif ($mode === 'publish') {
            return $this->publish($request);
        }
    }

    /**
     * Handle the "subscribe" requests to the hub.
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function subscribe($request)
    {
        $callback = $request->param('hub_callback', '');
        $topic = $request->param('hub_topic', '');
        $lease_seconds = $request->param('hub_lease_seconds');
        $secret = $request->param('hub_secret');

        if ($secret === '') {
            return Response::badRequest('requests/error.txt', [
                'errors' => ['secret must either be not given or be a cryptographically random unique secret string.'],
            ]);
        }

        $dao = new models\dao\Subscription();
        $subscription_values = $dao->findBy([
            'callback' => $callback,
            'topic' => $topic,
        ]);

        if (!$subscription_values) {
            // New subscription, not in database
            $subscription = models\Subscription::new(
                $callback,
                $topic,
                $lease_seconds,
                $secret
            );
            $errors = $subscription->validate();
            if ($errors) {
                return Response::badRequest('requests/error.txt', [
                    'errors' => array_column($errors, 'description'),
                ]);
            }

            $values = $subscription->toValues();
            $values['created_at'] = \Minz\Time::now()->getTimestamp();
            $dao->create($values);
        } else {
            // Subscription renewal
            $subscription = new models\Subscription($subscription_values);
            $subscription->renew($lease_seconds, $secret);
            $errors = $subscription->validate();
            if ($errors) {
                return Response::badRequest('requests/error.txt', [
                    'errors' => array_column($errors, 'description'),
                ]);
            }
            $dao->update($subscription->id, $subscription->toValues());
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
    public function unsubscribe($request)
    {
        $callback = $request->param('hub_callback', '');
        $topic = $request->param('hub_topic', '');

        $dao = new models\dao\Subscription();
        $subscription_values = $dao->findBy([
            'callback' => $callback,
            'topic' => $topic,
        ]);

        if ($subscription_values) {
            $subscription = new models\Subscription($subscription_values);
            $errors = $subscription->validate();
            if ($errors) {
                return Response::badRequest('requests/error.txt', [
                    'errors' => array_column($errors, 'description'),
                ]);
            }

            $subscription->requestUnsubscription();

            $dao->update($subscription->id, $subscription->toValues());
        } else {
            // We received an unsubscription for an unknown subscription. We return
            // an error message to indicate we'll not process any verification.
            return Response::badRequest('requests/error.txt', [
                'errors' => ['Unknown subscription.'],
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
    public function publish($request)
    {
        $url = $request->param('hub_url', '');
        if ($url === '') {
            $url = $request->param('hub_topic', '');
        }

        $dao = new models\dao\Content();
        $content_values = $dao->findBy([
            'url' => $url,
            'status' => 'new',
        ]);

        if ($content_values) {
            // we already know about this content, it will be delivered soon
            return Response::ok();
        }

        $content = models\Content::new($url);
        $errors = $content->validate();
        if ($errors) {
            return Response::badRequest('requests/error.txt', [
                'errors' => array_column($errors, 'description'),
            ]);
        }

        $values = $content->toValues();
        $values['created_at'] = \Minz\Time::now()->getTimestamp();
        $dao->create($values);

        return Response::ok();
    }
}
