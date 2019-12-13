<?php

namespace Webubbub\controllers\contents;

use Minz\Tests\ActionControllerTestCase;
use Webubbub\models;

class ContentsTest extends ActionControllerTestCase
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

    public function testItems()
    {
        $dao = new models\dao\Content();
        $dao->create([
            'url' => 'https://some.site.fr/feed.xml',
            'created_at' => time(),
        ]);
        $request = new \Minz\Request('CLI', '/contents/items');

        $response = items($request);

        $output = $response->render();
        $this->assertResponse($response, 200);
        $this->assertStringContainsString('https://some.site.fr/feed.xml', $output);
    }
}
