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
        $count = models\Subscription::deleteOldSubscriptions();
        \Minz\Log::notice("{$count} subscriptions deleted.");

        $count = models\Content::deleteOldContents();
        \Minz\Log::notice("{$count} contents deleted.");
    }
}
