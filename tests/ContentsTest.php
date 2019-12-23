<?php

namespace Webubbub\controllers\contents;

use Minz\Tests\IntegrationTestCase;
use Webubbub\models;

class ContentsTest extends IntegrationTestCase
{
    public function testItems()
    {
        $dao = new models\dao\Content();
        $dao->create([
            'url' => 'https://some.site.fr/feed.xml',
            'created_at' => time(),
        ]);
        $request = new \Minz\Request('CLI', '/contents');

        $response = self::$application->run($request);

        $output = $response->render();
        $this->assertResponse($response, 200);
        $this->assertStringContainsString('https://some.site.fr/feed.xml', $output);
    }
}
