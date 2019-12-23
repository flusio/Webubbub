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
    $dao = new models\dao\Content();
    $contents_values = $dao->listBy(['status' => 'new']);
    foreach ($contents_values as $content_values) {
        $content = models\Content::fromValues($content_values);

        $curl_response = services\Curl::get($content->url(), [
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        if ($curl_response->http_code < 200 || $curl_response->http_code >= 300) {
            \Minz\Log::notice(
                "[contents#fetch] {$curl_response->http_code} HTTP code is not successful (contents #{$content->id()})."
            );
            continue;
        }

        $headers = $curl_response->headers;
        $hub_url = \Minz\Url::absoluteFor('requests#handle');
        $default_self_link = "<{$content->url()}>; rel=\"self\"";
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
        $dao->update($content->id(), $content->toValues());
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
