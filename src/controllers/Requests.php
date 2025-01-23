<?php

namespace Webubbub\controllers;

use Minz\Request;
use Minz\Response;
use Webubbub\models;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Requests
{
    /**
     * Handle the subscribers requests (subscribe/unsubscribe) and publishers
     * "publish" request to the hub.
     *
     * @see self::subscribe()
     * @see self::unsubscribe()
     * @see self::publish()
     *
     * @request_param string hub.mode
     *     Either `subscribe`, `unsubscribe` or `publish`
     *
     * @response 400
     *     If the mode is invalid.
     */
    public function handle(Request $request): Response
    {
        // We expect to receive hub.* parameters here (with dots). For some
        // reasons, PHP replaces those dots by underscores. Meh!
        // See https://www.php.net/variables.external#language.variables.external.dot-in-names

        $mode = $request->param('hub_mode', '');

        if ($mode === 'subscribe') {
            return $this->subscribe($request);
        } elseif ($mode === 'unsubscribe') {
            return $this->unsubscribe($request);
        } elseif ($mode === 'publish') {
            return $this->publish($request);
        } else {
            return Response::badRequest('requests/error.txt', [
                'errors' => ["{$mode} mode is invalid."],
            ]);
        }
    }

    /**
     * Handle the "subscribe" requests to the hub.
     *
     * @request_param string hub.callback
     * @request_param string hub.topic
     * @request_param ?int hub.lease_seconds
     * @request_param ?string hub.secret
     *
     * @response 400
     *     If the callback or the topic are not valid URLs.
     *     If the secret is given but not secure enough, or too long.
     * @response 202
     *     On success
     */
    public function subscribe(Request $request): Response
    {
        $callback = $request->param('hub_callback', '');
        $topic = $request->param('hub_topic', '');
        $lease_seconds = $request->paramInteger(
            'hub_lease_seconds',
            models\Subscription::DEFAULT_LEASE_SECONDS
        );
        $secret = $request->param('hub_secret');

        if ($secret === '') {
            return Response::badRequest('requests/error.txt', [
                'errors' => ['secret must either be not given or be a cryptographically random unique secret string.'],
            ]);
        }

        $subscription = models\Subscription::findBy([
            'callback' => $callback,
            'topic' => $topic,
        ]);

        if (!$subscription) {
            // New subscription, not in database
            $subscription = new models\Subscription(
                $callback,
                $topic,
                $lease_seconds,
                $secret
            );

            $errors = $subscription->validate();
            if ($errors) {
                return Response::badRequest('requests/error.txt', [
                    'errors' => $errors,
                ]);
            }

            $subscription->save();
        } else {
            // Subscription renewal
            $subscription->renew($lease_seconds, $secret);

            $errors = $subscription->validate();
            if ($errors) {
                return Response::badRequest('requests/error.txt', [
                    'errors' => $errors,
                ]);
            }

            $subscription->save();
        }

        return Response::accepted();
    }

    /**
     * Handle the "unsubscribe" requests to the hub.
     *
     * @request_param string hub.callback
     * @request_param string hub.topic
     *
     * @response 400
     *     If the request doesn't correspond to an existing subscription.
     * @response 202
     *     On success
     */
    public function unsubscribe(Request $request): Response
    {
        $callback = $request->param('hub_callback', '');
        $topic = $request->param('hub_topic', '');

        $subscription = models\Subscription::findBy([
            'callback' => $callback,
            'topic' => $topic,
        ]);

        if ($subscription) {
            $subscription->requestUnsubscription();

            $subscription->save();
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
     * Both hub.url and hub.topic parameters have the same meaning. This action
     * looks for the hub.url parameter first. If it's not set, it looks then
     * for hub.topic.
     *
     * @request_param ?string hub.url
     * @request_param ?string hub.topic
     *
     * @response 400
     *     If the URL or topic URL is invalid.
     * @response 200
     *     On success
     */
    public function publish(Request $request): Response
    {
        $url = $request->param('hub_url', '');

        if ($url === '') {
            $url = $request->param('hub_topic', '');
        }

        $content = models\Content::findBy([
            'url' => $url,
            'status' => 'new',
        ]);

        if ($content) {
            // we already know about this content, it will be delivered soon
            return Response::ok();
        }

        $content = new models\Content($url);

        $errors = $content->validate();
        if ($errors) {
            return Response::badRequest('requests/error.txt', [
                'errors' => $errors,
            ]);
        }

        $content->save();

        return Response::ok();
    }
}
