<?php

namespace Webubbub;

class RequestsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @dataProvider invalidModeProvider
     */
    public function testHandleFailsIfModeIsInvalid($invalidMode)
    {
        $response = $this->appRun('post', '/', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
            'hub_mode' => $invalidMode,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseEquals($response, "{$invalidMode} mode is invalid.\n");
        $this->assertResponseHeaders($response, ['Content-Type' => 'text/plain']);
    }

    public function testSubscribe()
    {
        $response = $this->appRun('cli', '/requests/subscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
            'hub_lease_seconds' => '432000',
            'hub_secret' => 'a cryptographically random unique secret string',
        ]);

        $dao = new models\dao\Subscription();
        $this->assertSame(1, $dao->count());
        $this->assertResponseCode($response, 202);

        $subscription = $dao->listAll()[0];
        $this->assertSame('https://subscriber.com/callback', $subscription['callback']);
        $this->assertSame('https://some.site.fr/feed.xml', $subscription['topic']);
        $this->assertSame(432000, intval($subscription['lease_seconds']));
        $this->assertSame(
            'a cryptographically random unique secret string',
            $subscription['secret']
        );
        $this->assertSame('new', $subscription['status']);
    }

    public function testSubscribeWithExistingSubscription()
    {
        $callback = 'https://subscriber.com/callback';
        $topic = 'https://some.site.fr/feed.xml';
        $dao = new models\dao\Subscription();
        $id = $this->create('subscriptions', [
            'callback' => $callback,
            'topic' => $topic,
            'secret' => null,
            'lease_seconds' => 432000,
            'pending_request' => null,
        ]);
        $this->assertSame(1, $dao->count());

        $response = $this->appRun('cli', '/requests/subscribe', [
            'hub_callback' => $callback,
            'hub_topic' => $topic,
            'hub_lease_seconds' => '543000',
            'hub_secret' => 'a secret string',
        ]);

        $subscription = $dao->find($id);
        $this->assertSame(1, $dao->count());
        $this->assertResponseCode($response, 202);
        $this->assertSame('432000', $subscription['lease_seconds']);
        $this->assertNull($subscription['secret']);
        $this->assertSame('543000', $subscription['pending_lease_seconds']);
        $this->assertSame('a secret string', $subscription['pending_secret']);
        $this->assertSame('subscribe', $subscription['pending_request']);
    }

    public function testSubscribeWithNoLeaseSeconds()
    {
        $response = $this->appRun('cli', '/requests/subscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
            'hub_secret' => 'a cryptographically random unique secret string',
        ]);

        $dao = new models\dao\Subscription();
        $this->assertSame(1, $dao->count());
        $this->assertResponseCode($response, 202);

        $subscription = $dao->listAll()[0];
        $this->assertSame('https://subscriber.com/callback', $subscription['callback']);
        $this->assertSame('https://some.site.fr/feed.xml', $subscription['topic']);
        $this->assertSame(
            models\Subscription::DEFAULT_LEASE_SECONDS,
            intval($subscription['lease_seconds'])
        );
        $this->assertSame(
            'a cryptographically random unique secret string',
            $subscription['secret']
        );
        $this->assertSame('new', $subscription['status']);
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testSubscribeFailsIfCallbackIsInvalid($invalid_url)
    {
        $response = $this->appRun('cli', '/requests/subscribe', [
            'hub_callback' => $invalid_url,
            'hub_topic' => 'https://some.site.fr/feed.xml',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, "`callback` property is");
        $this->assertResponseHeaders($response, ['Content-Type' => 'text/plain']);
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testSubscribeFailsIfTopicIsInvalid($invalid_url)
    {
        $response = $this->appRun('cli', '/requests/subscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => $invalid_url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, "`topic` property is");
        $this->assertResponseHeaders($response, ['Content-Type' => 'text/plain']);
    }

    public function testSubscribeWithExistingSubscriptionFailsIfSecretIsInvalid()
    {
        $callback = 'https://subscriber.com/callback';
        $topic = 'https://some.site.fr/feed.xml';
        $id = $this->create('subscriptions', [
            'callback' => $callback,
            'topic' => $topic,
            'secret' => null,
            'lease_seconds' => 432000,
            'pending_request' => null,
        ]);

        $response = $this->appRun('cli', '/requests/subscribe', [
            'hub_callback' => $callback,
            'hub_topic' => $topic,
            'hub_lease_seconds' => 543000,
            'hub_secret' => '',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseEquals(
            $response,
            "secret must either be not given or be a cryptographically random unique secret string.\n"
        );
        $this->assertResponseHeaders($response, ['Content-Type' => 'text/plain']);
    }

    public function testUnsubscribe()
    {
        $callback = 'https://subscriber.com/callback';
        $topic = 'https://some.site.fr/feed.xml';
        $dao = new models\dao\Subscription();
        $id = $this->create('subscriptions', [
            'callback' => $callback,
            'topic' => $topic,
            'status' => 'new',
            'pending_request' => null,
        ]);

        $response = $this->appRun('cli', '/requests/unsubscribe', [
            'hub_callback' => $callback,
            'hub_topic' => $topic,
        ]);

        $subscription = $dao->find($id);
        $this->assertResponseCode($response, 202);
        $this->assertSame('new', $subscription['status']);
        $this->assertSame('unsubscribe', $subscription['pending_request']);
    }

    public function testUnsubscribeWithUnknownSubscription()
    {
        $response = $this->appRun('cli', '/requests/unsubscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
        ]);

        $dao = new models\dao\Subscription();
        $this->assertResponseCode($response, 400);
        $this->assertResponseEquals($response, "Unknown subscription.\n");
        $this->assertSame(0, $dao->count());
    }

    public function testPublish()
    {
        $dao = new models\dao\Content();
        $url = 'https://some.site.fr/feed.xml';
        $this->assertSame(0, $dao->count());

        $response = $this->appRun('cli', '/requests/publish', [
            'hub_url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertSame(1, $dao->count());
        $content = $dao->listAll()[0];
        $this->assertSame($url, $content['url']);
    }

    public function testPublishAcceptsTopic()
    {
        $dao = new models\dao\Content();
        $url = 'https://some.site.fr/feed.xml';
        $this->assertSame(0, $dao->count());

        $response = $this->appRun('cli', '/requests/publish', [
            'hub_topic' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertSame(1, $dao->count());
        $content = $dao->listAll()[0];
        $this->assertSame($url, $content['url']);
    }

    public function testPublishWithSameUrlAndNewStatus()
    {
        $dao = new models\dao\Content();
        $url = 'https://some.site.fr/feed.xml';
        $this->create('contents', [
            'url' => $url,
            'status' => 'new',
        ]);
        $this->assertSame(1, $dao->count());

        $response = $this->appRun('cli', '/requests/publish', [
            'hub_url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertSame(1, $dao->count());
    }

    public function testPublishWithSameUrlAndFetchedStatus()
    {
        $dao = new models\dao\Content();
        $url = 'https://some.site.fr/feed.xml';
        $this->create('contents', [
            'url' => $url,
            'status' => 'fetched',
        ]);
        $this->assertSame(1, $dao->count());

        $response = $this->appRun('cli', '/requests/publish', [
            'hub_url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertSame(2, $dao->count());
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testPublishFailsIfUrlIsInvalid($invalid_url)
    {
        $response = $this->appRun('cli', '/requests/publish', [
            'hub_url' => $invalid_url,
        ]);

        $dao = new models\dao\Content();
        $this->assertSame(0, $dao->count());
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, '`url` property is');
        $this->assertResponseHeaders($response, ['Content-Type' => 'text/plain']);
    }

    public function invalidUrlProvider()
    {
        return [
            [''],
            ['some/string'],
            ['ftp://some.site.fr'],
            ['http://'],
        ];
    }

    public function invalidModeProvider()
    {
        return [
            [''],
            ['not a mode'],
            ['subscribemaybe'],
        ];
    }
}
