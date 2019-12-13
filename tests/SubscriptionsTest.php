<?php

namespace Webubbub\controllers\subscriptions;

use Minz\Tests\ActionControllerTestCase;
use Webubbub\models;

class SubscriptionsTest extends ActionControllerTestCase
{
    private static $schema;

    public static function setUpBeforeClass(): void
    {
        self::includeController();

        $configuration_path = \Minz\Configuration::$configuration_path;
        self::$schema = file_get_contents($configuration_path . '/schema.sql');
    }

    public function setUp(): void
    {
        $database = \Minz\Database::get();
        $database->exec(self::$schema);
    }

    public function tearDown(): void
    {
        \Minz\Database::drop();
    }

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

        $response = handle($request);

        $this->assertResponse(
            $response,
            400,
            "{$invalidMode} mode is invalid.\n",
            ['Content-Type' => 'text/plain']
        );
    }

    public function testSubscribe()
    {
        $request = new \Minz\Request('CLI', '/subscriptions/subscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
            'hub_lease_seconds' => 432000,
            'hub_secret' => 'a cryptographically random unique secret string',
        ]);

        $response = subscribe($request);

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
        $id = $dao->create([
            'callback' => $callback,
            'topic' => $topic,
            'created_at' => time(),
            'status' => 'new',
            'lease_seconds' => 432000,
            'pending_request' => null,
        ]);
        $request = new \Minz\Request('CLI', '/subscriptions/subscribe', [
            'hub_callback' => $callback,
            'hub_topic' => $topic,
            'hub_lease_seconds' => 543000,
            'hub_secret' => 'a secret string',
        ]);

        $response = subscribe($request);

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
        $request = new \Minz\Request('CLI', '/subscriptions/subscribe', [
            'hub_callback' => $invalid_url,
            'hub_topic' => 'https://some.site.fr/feed.xml',
        ]);

        $response = subscribe($request);

        $this->assertResponse(
            $response,
            400,
            "{$invalid_url} callback is invalid.\n",
            ['Content-Type' => 'text/plain']
        );
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testSubscribeFailsIfTopicIsInvalid($invalid_url)
    {
        $request = new \Minz\Request('CLI', '/subscriptions/subscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => $invalid_url,
        ]);

        $response = subscribe($request);

        $this->assertResponse(
            $response,
            400,
            "{$invalid_url} topic is invalid.\n",
            ['Content-Type' => 'text/plain']
        );
    }

    public function testUnsubscribe()
    {
        $callback = 'https://subscriber.com/callback';
        $topic = 'https://some.site.fr/feed.xml';
        $dao = new models\dao\Subscription();
        $id = $dao->create([
            'callback' => $callback,
            'topic' => $topic,
            'created_at' => time(),
            'status' => 'new',
            'lease_seconds' => 432000,
            'pending_request' => null,
        ]);

        $request = new \Minz\Request('CLI', '/subscriptions/unsubscribe', [
            'hub_callback' => $callback,
            'hub_topic' => $topic,
        ]);

        $response = unsubscribe($request);

        $subscription = $dao->find($id);
        $this->assertResponse($response, 202);
        $this->assertSame('new', $subscription['status']);
        $this->assertSame('unsubscribe', $subscription['pending_request']);
    }

    public function testUnsubscribeWithUnknownSubscription()
    {
        $request = new \Minz\Request('CLI', '/subscriptions/unsubscribe', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
        ]);

        $response = unsubscribe($request);

        $dao = new models\dao\Subscription();
        $this->assertResponse($response, 400, "Unknown subscription.\n");
        $this->assertSame(0, $dao->count());
    }

    public function testItems()
    {
        $dao = new models\dao\Subscription();
        $dao->create([
            'callback' => 'https://subscriber.com/callback',
            'topic' => 'https://some.site.fr/feed.xml',
            'created_at' => time(),
            'status' => 'new',
            'lease_seconds' => 432000,
        ]);
        $request = new \Minz\Request('CLI', '/subscriptions/items');

        $response = items($request);

        $output = $response->render();
        $this->assertResponse($response, 200);
        $this->assertStringContainsString('https://subscriber.com/callback', $output);
        $this->assertStringContainsString('https://some.site.fr/feed.xml', $output);
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
