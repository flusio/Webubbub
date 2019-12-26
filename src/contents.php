<?php

namespace Webubbub\controllers\contents;

use Minz\Response;
use Webubbub\models;
use Webubbub\services;

/**
 * @param \Minz\Request $request
 *
 * @return \Minz\Response
 */
function fetch($request)
{
    $subscription_dao = new models\dao\Subscription();
    $content_dao = new models\dao\Content();
    $content_delivery_dao = new models\dao\ContentDelivery();

    $contents_values = $content_dao->listBy(['status' => 'new']);
    foreach ($contents_values as $content_values) {
        // Fetch the content
        $content = new models\Content($content_values);

        $curl_response = services\Curl::get($content->url, [
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        if ($curl_response->http_code < 200 || $curl_response->http_code >= 300) {
            \Minz\Log::notice(
                "[contents#fetch] {$curl_response->http_code} HTTP code is not successful (contents #{$content->id})."
            );
            continue;
        }

        $headers = $curl_response->headers;
        $hub_url = \Minz\Url::absoluteFor('requests#handle');
        $default_self_link = "<{$content->url}>; rel=\"self\"";
        $default_hub_link = "<$hub_url>; rel=\"hub\"";
        if (isset($headers['link'])) {
            $links = implode(', ', $headers['link']);
        } else {
            $links = $default_hub_link . ', ' . $default_self_link;
        }

        if (strpos($links, 'rel="self"') === false) {
            $links .= ', ' . $default_self_link;
        }
        if (strpos($links, 'rel="hub"') === false) {
            $links .= ', ' . $default_hub_link;
        }

        if (isset($headers['content-type'])) {
            $content_type = $headers['content-type'][0];
        } else {
            $content_type = 'application/octet-stream';
        }

        $content->fetch($curl_response->content, $content_type, $links);
        $content_dao->update($content->id, $content->toValues());

        // Then, create content deliveries for subscribers
        $subscriptions_values = $subscription_dao->listBy([
            'topic' => $content->url,
            'status' => 'verified',
        ]);

        if (!$subscriptions_values) {
            // there's no subscriptions to this topic, we don't need to process it
            continue;
        }

        $content_deliveries_values = [];
        foreach ($subscriptions_values as $subscription_values) {
            $content_delivery = models\ContentDelivery::new(
                intval($subscription_values['id']),
                $content->id
            );
            $values = $content_delivery->toValues();
            $values['created_at'] = \Minz\Time::now();
            $content_deliveries_values[] = $values;
        }
        $content_delivery_dao->createList($content_deliveries_values);
    }
    return Response::ok();
}

/**
 * @param \Minz\Request $request
 *
 * @return \Minz\Response
 */
function deliver($request)
{
    $content_dao = new models\dao\Content();
    $subscription_dao = new models\dao\Subscription();
    $content_delivery_dao = new models\dao\ContentDelivery();

    $contents_values = $content_dao->listBy([
        'status' => 'fetched',
    ]);

    foreach ($contents_values as $content_values) {
        $content = new models\Content($content_values);
        $content_deliveries_values = $content_delivery_dao->listBy([
            'content_id' => $content->id,
        ]);
        $count_deleted = 0;

        foreach ($content_deliveries_values as $content_delivery_values) {
            $content_delivery = new models\ContentDelivery($content_delivery_values);
            if ($content_delivery->try_at->getTimestamp() > \Minz\Time::now()) {
                // delivery is marked to be delivered later, just pass to the next.
                continue;
            }

            $subscription_values = $subscription_dao->find(
                $content_delivery->subscription_id
            );
            $subscription = new models\Subscription($subscription_values);

            $headers = [
                'Content-Type: ' . $content->type,
                'Link: ' . $content->links,
            ];

            if ($subscription->secret) {
                $signature = hash_hmac(
                    'sha256',
                    $content->content,
                    $subscription->secret
                );
                $headers[] = 'X-Hub-Signature: sha256=' . $signature;
            }

            $curl_response = services\Curl::post(
                $subscription->callback,
                $content->content,
                [
                    CURLOPT_HTTPHEADER => $headers,
                ]
            );

            // @todo add retry mecanism
            $content_delivery_dao->delete($content_delivery->id);
            $count_deleted += 1;

            if ($curl_response->http_code === 410) {
                // HTTP 410 code means the subscription has been deleted and we
                // can terminate the subscription
                $subscription_dao->delete($subscription->id);
            }
        }

        if ($count_deleted === count($content_deliveries_values)) {
            $content->deliver();
            $content_dao->update($content->id, $content->toValues());
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
    $dao = new models\dao\Content();
    $contents = $dao->listAll();
    return Response::ok('contents/items.txt', [
        'contents' => $contents,
    ]);
}
