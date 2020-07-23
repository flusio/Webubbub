<?php

namespace Webubbub\models;

use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    use \Minz\Tests\TimeHelper;

    public function testNew()
    {
        $url = 'https://some.site.fr/feed.xml';

        $content = Content::new($url);

        $this->assertSame($url, $content->url);
        $this->assertSame('new', $content->status);
    }

    public function testNewDecodesUrls()
    {
        $url = 'https://some.site.fr/feed.xml?foo%2Bbar';

        $content = Content::new($url);

        $this->assertSame(
            'https://some.site.fr/feed.xml?foo+bar',
            $content->url
        );
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testNewFailsIfUrlIsInvalid($invalid_url)
    {
        $content = Content::new($invalid_url);

        $errors = $content->validate();

        $this->assertArrayHasKey('url', $errors);
    }

    public function testFetch()
    {
        $this->freeze(1000);

        $url = 'https://some.site.fr/feed';
        $links = 'https://some.site.fr/feed.xml';
        $type = 'application/rss+xml';
        $content_text = '<some>xml</some>';
        $content = Content::new($url);

        $content->fetch($content_text, $type, $links);

        $this->assertSame('fetched', $content->status);
        $this->assertSame(1000, $content->fetched_at->getTimestamp());
        $this->assertSame($links, $content->links);
        $this->assertSame($type, $content->type);
        $this->assertSame($content_text, $content->content);
    }

    public function testDeliver()
    {
        $content = new Content([
            'url' => 'https://some.site.fr/feed',
            'status' => 'fetched',
        ]);

        $content->deliver();

        $this->assertSame('delivered', $content->status);
    }

    public function testDeliverFailsIfStatusIsNew()
    {
        $this->expectException(Errors\ContentError::class);
        $this->expectExceptionMessage('Content cannot be delivered with `new` status.');

        $content = new Content([
            'url' => 'https://some.site.fr/feed',
            'status' => 'new',
        ]);

        $content->deliver();
    }

    public function testConstructor()
    {
        $content = new Content([
            'id' => '1',
            'created_at' => '10000',
            'status' => 'new',
            'url' => 'https://some.site.fr/feed.xml',
        ]);

        $this->assertSame(1, $content->id);
        $this->assertSame(10000, $content->created_at->getTimestamp());
        $this->assertSame('https://some.site.fr/feed.xml', $content->url);
    }

    /**
     * @dataProvider missingValuesProvider
     */
    public function testConstructorFailsIfRequiredValueIsMissing($values, $missing_value_name)
    {
        $content = new Content($values);

        $errors = $content->validate();

        $this->assertArrayHasKey($missing_value_name, $errors);
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

    public function missingValuesProvider()
    {
        $default_values = [
            'status' => 'new',
            'url' => 'https://some.site.fr/feed.xml',
        ];

        $dataset = [];
        foreach (array_keys($default_values) as $missing_value_name) {
            $values = $default_values;
            unset($values[$missing_value_name]);
            $dataset[] = [$values, $missing_value_name];
        }

        return $dataset;
    }
}
