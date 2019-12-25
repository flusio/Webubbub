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
function items($request)
{
    $dao = new models\dao\Content();
    $contents = $dao->listAll();
    return Response::ok('contents/items.txt', [
        'contents' => $contents,
    ]);
}
