<?php

namespace Webubbub\cli;

use tests\factories\ContentFactory;

class ContentsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testItems(): void
    {
        ContentFactory::create([
            'url' => 'https://some.site.fr/feed.xml',
        ]);

        $response = $this->appRun('CLI', '/contents');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'https://some.site.fr/feed.xml');
    }
}
