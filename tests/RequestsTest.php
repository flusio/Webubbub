<?php

namespace Webubbub\controllers\requests;

use Minz\Tests\IntegrationTestCase;
use Webubbub\models;

class RequestsTest extends IntegrationTestCase
{
    /**
     * @dataProvider invalidModeProvider
     */
    public function testHandleFailsIfModeIsInvalid($invalidMode)
    {
        $request = new \Minz\Request('POST', '/', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
            'hub_mode' => $invalidMode,
        ]);

        $response = self::$application->run($request);

        $this->assertResponse(
            $response,
            400,
            "{$invalidMode} mode is invalid.\n",
            ['Content-Type' => 'text/plain']
        );
    }

    public function testSubscribe()
    {
        $request = new \Minz\Request('CLI', '/requests/subscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
            'hub_lease_seconds' => 432000,
            'hub_secret' => 'a cryptographically random unique secret string',
        ]);

        $response = self::$application->run($request);

        $dao = new models\dao\Subscription();
        $this->assertSame(1, $dao->count());
        $this->assertResponse($response, 202);

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
        $id = self::$factories['subscriptions']->create([
            'callback' => $callback,
            'topic' => $topic,
            'lease_seconds' => 432000,
            'pending_request' => null,
        ]);
        $request = new \Minz\Request('CLI', '/requests/subscribe', [
            'hub_callback' => $callback,
            'hub_topic' => $topic,
            'hub_lease_seconds' => 543000,
            'hub_secret' => 'a secret string',
        ]);

        $this->assertSame(1, $dao->count());

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertSame(1, $dao->count());
        $this->assertResponse($response, 202);
        $this->assertSame('543000', $subscription['lease_seconds']);
        $this->assertSame('a secret string', $subscription['secret']);
        $this->assertSame('subscribe', $subscription['pending_request']);
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testSubscribeFailsIfCallbackIsInvalid($invalid_url)
    {
        $request = new \Minz\Request('CLI', '/requests/subscribe', [
            'hub_callback' => $invalid_url,
            'hub_topic' => 'https://some.site.fr/feed.xml',
        ]);

        $response = self::$application->run($request);

        $this->assertResponse(
            $response,
            400,
            "`callback` property is invalid ({$invalid_url}).\n",
            ['Content-Type' => 'text/plain']
        );
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testSubscribeFailsIfTopicIsInvalid($invalid_url)
    {
        $request = new \Minz\Request('CLI', '/requests/subscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => $invalid_url,
        ]);

        $response = self::$application->run($request);

        $this->assertResponse(
            $response,
            400,
            "`topic` property is invalid ({$invalid_url}).\n",
            ['Content-Type' => 'text/plain']
        );
    }

    public function testUnsubscribe()
    {
        $callback = 'https://subscriber.com/callback';
        $topic = 'https://some.site.fr/feed.xml';
        $dao = new models\dao\Subscription();
        $id = self::$factories['subscriptions']->create([
            'callback' => $callback,
            'topic' => $topic,
            'status' => 'new',
            'pending_request' => null,
        ]);

        $request = new \Minz\Request('CLI', '/requests/unsubscribe', [
            'hub_callback' => $callback,
            'hub_topic' => $topic,
        ]);

        $response = self::$application->run($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 202);
        $this->assertSame('new', $subscription['status']);
        $this->assertSame('unsubscribe', $subscription['pending_request']);
    }

    public function testUnsubscribeWithUnknownSubscription()
    {
        $request = new \Minz\Request('CLI', '/requests/unsubscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
        ]);

        $response = self::$application->run($request);

        $dao = new models\dao\Subscription();
        $this->assertResponse($response, 400, "Unknown subscription.\n");
        $this->assertSame(0, $dao->count());
    }

    public function testPublish()
    {
        $dao = new models\dao\Content();
        $url = 'https://some.site.fr/feed.xml';
        $request = new \Minz\Request('CLI', '/requests/publish', [
            'hub_url' => $url,
        ]);

        $this->assertSame(0, $dao->count());

        $response = self::$application->run($request);

        $this->assertResponse($response, 200);
        $this->assertSame(1, $dao->count());
        $content = $dao->listAll()[0];
        $this->assertSame($url, $content['url']);
    }

    public function testPublishAcceptsTopic()
    {
        $dao = new models\dao\Content();
        $url = 'https://some.site.fr/feed.xml';
        $request = new \Minz\Request('CLI', '/requests/publish', [
            'hub_topic' => $url,
        ]);

        $this->assertSame(0, $dao->count());

        $response = self::$application->run($request);

        $this->assertResponse($response, 200);
        $this->assertSame(1, $dao->count());
        $content = $dao->listAll()[0];
        $this->assertSame($url, $content['url']);
    }

    public function testPublishWithSameUrlAndNewStatus()
    {
        $dao = new models\dao\Content();
        $url = 'https://some.site.fr/feed.xml';
        self::$factories['contents']->create([
            'url' => $url,
            'status' => 'new',
        ]);
        $request = new \Minz\Request('CLI', '/requests/publish', [
            'hub_url' => $url,
        ]);

        $this->assertSame(1, $dao->count());

        $response = self::$application->run($request);

        $this->assertResponse($response, 200);
        $this->assertSame(1, $dao->count());
    }

    public function testPublishWithSameUrlAndFetchedStatus()
    {
        $dao = new models\dao\Content();
        $url = 'https://some.site.fr/feed.xml';
        self::$factories['contents']->create([
            'url' => $url,
            'status' => 'fetched',
        ]);
        $request = new \Minz\Request('CLI', '/requests/publish', [
            'hub_url' => $url,
        ]);

        $this->assertSame(1, $dao->count());

        $response = publish($request);

        $this->assertResponse($response, 200);
        $this->assertSame(2, $dao->count());
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testPublishFailsIfUrlIsInvalid($invalid_url)
    {
        $request = new \Minz\Request('CLI', '/requests/publish', [
            'hub_url' => $invalid_url,
        ]);

        $response = self::$application->run($request);

        $dao = new models\dao\Content();
        $this->assertSame(0, $dao->count());
        $this->assertResponse(
            $response,
            400,
            "`url` property is invalid ({$invalid_url}).\n",
            ['Content-Type' => 'text/plain']
        );
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
