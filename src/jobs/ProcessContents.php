<?php

namespace Webubbub\jobs;

use Minz\Job;
use Webubbub\models;
use Webubbub\services;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ProcessContents extends Job
{
    public static function install(): void
    {
        $job = new self();
        if (!self::existsBy(['name' => $job->name])) {
            $perform_at = \Minz\Time::now();
            $job->performLater($perform_at);
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->frequency = '+5 seconds';
    }

    public function perform(): void
    {
        $this->performFetch();
        $this->performDeliver();
    }

    /**
     * Fetch the new Contents.
     */
    public function performFetch(): void
    {
        $contents = models\Content::listBy(['status' => 'new']);

        foreach ($contents as $content) {
            assert(!empty($content->url));

            \Minz\Log::notice("content #{$content->id}: to be fetched");

            // Fetch the content
            $curl_response = services\Curl::get($content->url, [
                CURLOPT_FOLLOWLOCATION => true,
            ]);

            $http_code = $curl_response->http_code;
            if ($http_code < 200 || $http_code >= 300) {
                \Minz\Log::warning(
                    "content #{$content->id}: {$http_code} HTTP code is not successful"
                );
                continue;
            }

            $headers = $curl_response->headers;
            $hub_url = \Minz\Url::absoluteFor('Requests#handle');

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
            $content->save();

            \Minz\Log::notice("content #{$content->id}: fetched");

            // Then, create content deliveries for subscribers
            $subscriptions = models\Subscription::listBy([
                'topic' => $content->url,
                'status' => 'verified',
            ]);

            $count_subscriptions = count($subscriptions);
            \Minz\Log::notice("content #{$content->id}: {$count_subscriptions} subscribers to notify");

            if ($count_subscriptions === 0) {
                // there's no subscriptions to this topic, we don't need to process it
                continue;
            }

            foreach ($subscriptions as $subscription) {
                $content_delivery = new models\ContentDelivery($subscription->id, $content->id);
                $content_delivery->save();
            }
        }
    }

    /**
     * Deliver the fetched Contents.
     */
    public function performDeliver(): void
    {
        $contents = models\Content::listBy([
            'status' => 'fetched',
        ]);

        foreach ($contents as $content) {
            $content_deliveries = models\ContentDelivery::listBy([
                'content_id' => $content->id,
            ]);

            $count_deleted = 0;

            foreach ($content_deliveries as $content_delivery) {
                if ($content_delivery->try_at > \Minz\Time::now()) {
                    // delivery is marked to be delivered later, just pass to the next.
                    continue;
                }

                $subscription = $content_delivery->subscription();

                assert(!empty($subscription->callback));

                \Minz\Log::notice("content #{$content->id}: delivery to {$subscription->callback}");

                $headers = [
                    'Content-Type: ' . $content->type,
                    'Link: ' . $content->links,
                ];

                if ($subscription->secret) {
                    $signature = hash_hmac(
                        'sha256',
                        $content->content ?? '',
                        $subscription->secret
                    );
                    $headers[] = 'X-Hub-Signature: sha256=' . $signature;
                }

                $curl_response = services\Curl::post(
                    $subscription->callback,
                    $content->content ?? '',
                    [
                        CURLOPT_HTTPHEADER => $headers,
                    ]
                );

                $http_code = $curl_response->http_code;
                if (($http_code >= 200 && $http_code < 300) || $http_code === 410) {
                    \Minz\Log::notice("content #{$content->id}: delivered to {$subscription->callback}");

                    $content_delivery->remove();
                    $count_deleted += 1;

                    if ($http_code === 410) {
                        // HTTP 410 code means the subscription has been deleted
                        // and we can terminate the subscription
                        $subscription->remove();
                    }
                } else {
                    try {
                        // The request failed, we should retry later
                        $content_delivery->retryLater();
                        $content_delivery->save();

                        \Minz\Log::warning(
                            "content #{$content->id}: delivery to {$subscription->callback} "
                            . "marked for later"
                        );
                    } catch (models\Errors\ContentDeliveryError $e) {
                        $content_delivery->remove();
                        $count_deleted += 1;

                        \Minz\Log::warning(
                            "content #{$content->id}: delivery to {$subscription->callback} "
                            . "failed (reached max tries count)"
                        );
                    }
                }
            }

            if ($count_deleted === count($content_deliveries)) {
                $content->deliver();
                $content->save();

                \Minz\Log::warning("content #{$content->id}: delivered");
            }
        }
    }
}
