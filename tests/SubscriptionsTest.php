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

    public function testCreateWithSubscribe()
    {
        $request = new \Minz\Request('POST', '/', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
            'hub_lease_seconds' => 432000,
            'hub_secret' => 'a cryptographically random unique secret string',
            'hub_mode' => 'subscribe',
        ]);

        $response = create($request);

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

    public function testCreateWithSubscribeWithExistingSubscription()
    {
        $callback = 'https://subscriber.com/callback';
        $topic = 'https://some.site.fr/feed.xml';
        $dao = new models\dao\Subscription();
        $dao->create([
            'callback' => $callback,
            'topic' => $topic,
            'created_at' => time(),
            'status' => 'new',
            'lease_seconds' => 432000,
        ]);
        $request = new \Minz\Request('POST', '/', [
            'hub_callback' => $callback,
            'hub_topic' => $topic,
            'hub_mode' => 'subscribe',
        ]);

        $response = create($request);

        $this->assertSame(1, $dao->count());
        $this->assertResponse($response, 202);
    }

    /**
     * @dataProvider invalidModeProvider
     */
    public function testCreateFailsIfModeIsInvalid($invalidMode)
    {
        $request = new \Minz\Request('POST', '/', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => 'https://some.site.fr/feed.xml',
            'hub_mode' => $invalidMode,
        ]);

        $response = create($request);

        $this->assertResponse(
            $response,
            400,
            "{$invalidMode} mode is invalid.\n",
            ['Content-Type' => 'text/plain']
        );
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testCreateFailsIfCallbackIsInvalid($invalid_url)
    {
        $request = new \Minz\Request('POST', '/', [
            'hub_callback' => $invalid_url,
            'hub_topic' => 'https://some.site.fr/feed.xml',
            'hub_mode' => 'subscribe',
        ]);

        $response = create($request);

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
    public function testCreateFailsIfTopicIsInvalid($invalid_url)
    {
        $request = new \Minz\Request('POST', '/', [
            'hub_callback' => 'https://subscriber.com/callback',
            'hub_topic' => $invalid_url,
            'hub_mode' => 'subscribe',
        ]);

        $response = create($request);

        $this->assertResponse(
            $response,
            400,
            "{$invalid_url} topic is invalid.\n",
            ['Content-Type' => 'text/plain']
        );
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
