<?php

namespace Webubbub\cli;

use tests\factories\SubscriptionFactory;

class SubscriptionsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testItems(): void
    {
        $subscription = SubscriptionFactory::create([
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
        ]);

        $response = $this->appRun('CLI', '/subscriptions');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'https://subscriber.com/callback');
        $this->assertResponseContains($response, 'https://some.site.fr/feed.xml');
    }
}
