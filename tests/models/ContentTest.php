<?php

namespace Webubbub\models;

use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    use \Minz\Tests\TimeHelper;

    public function testFetch(): void
    {
        $this->freeze();

        $url = 'https://some.site.fr/feed';
        $links = 'https://some.site.fr/feed.xml';
        $type = 'application/rss+xml';
        $content_text = '<some>xml</some>';
        $content = new Content($url);

        $content->fetch($content_text, $type, $links);

        $this->assertSame('fetched', $content->status);
        $this->assertEquals(\Minz\Time::now(), $content->fetched_at);
        $this->assertSame($links, $content->links);
        $this->assertSame($type, $content->type);
        $this->assertSame($content_text, $content->content);
    }

    public function testDeliver(): void
    {
        $content = new Content('https://some.site.fr/feed');
        $content->status = 'fetched';

        $content->deliver();

        $this->assertSame('delivered', $content->status);
    }

    public function testDeliverFailsIfStatusIsNew(): void
    {
        $this->expectException(Errors\ContentError::class);
        $this->expectExceptionMessage('Content cannot be delivered with `new` status.');

        $content = new Content('https://some.site.fr/feed');
        $content->status = 'new';

        $content->deliver();
    }
}
