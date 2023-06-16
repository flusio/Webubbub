<?php

namespace Webubbub\jobs;

use Minz\Job;
use Webubbub\models;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Cleaner extends Job
{
    public static function install(): void
    {
        $job = new self();
        if (!self::existsBy(['name' => $job->name])) {
            $perform_at = \Minz\Time::relative('tomorrow 2:00');
            $job->performLater($perform_at);
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->frequency = '+1 day';
    }

    /**
     * Clear expired subscriptions and contents.
     */
    public function perform(): void
    {
        $two_weeks_ago = \Minz\Time::ago(2, 'weeks');

        $subscriptions = models\Subscription::listBy([
            'status' => ['new', 'validated', 'expired'],
        ]);

        $subscription_ids_to_delete = [];
        foreach ($subscriptions as $subscription) {
            if (
                $subscription->status === 'expired' &&
                $subscription->expired_at <= $two_weeks_ago
            ) {
                $subscription_ids_to_delete[] = $subscription->id;
            }

            if (
                $subscription->status !== 'expired' &&
                $subscription->created_at <= $two_weeks_ago
            ) {
                $subscription_ids_to_delete[] = $subscription->id;
            }
        }

        models\Subscription::deleteBy([
            'id' => $subscription_ids_to_delete,
        ]);

        $count_subscriptions_deleted = count($subscription_ids_to_delete);
        \Minz\Log::notice("{$count_subscriptions_deleted} subscriptions deleted.");

        $contents = models\Content::listBy([
            'status' => 'delivered',
        ]);

        $content_ids_to_delete = array_column($contents, 'id');

        models\Content::deleteBy([
            'id' => $content_ids_to_delete,
        ]);

        $count_contents_deleted = count($content_ids_to_delete);
        \Minz\Log::notice("{$count_contents_deleted} contents deleted.");
    }
}
