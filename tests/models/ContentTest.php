<?php

namespace Webubbub\models;

use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    public function testConstructor()
    {
        $url = 'https://some.site.fr/feed.xml';

        $content = new Content($url);

        $this->assertSame($url, $content->url());
    }

    public function testConstructorDecodesUrls()
    {
        $url = 'https://some.site.fr/feed.xml?foo%2Bbar';

        $content = new Content($url);

        $this->assertSame(
            'https://some.site.fr/feed.xml?foo+bar',
            $content->url()
        );
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testConstructorFailsIfUrlIsInvalid($invalid_url)
    {
        $this->expectException(Errors\ContentError::class);
        $this->expectExceptionMessage("{$invalid_url} url is invalid.");

        new Content($invalid_url);
    }

    public function testFromValues()
    {
        $content = Content::fromValues([
            'id' => '1',
            'created_at' => '10000',
            'url' => 'https://some.site.fr/feed.xml',
        ]);

        $this->assertSame(1, $content->id());
        $this->assertSame(10000, $content->createdAt()->getTimestamp());
        $this->assertSame('https://some.site.fr/feed.xml', $content->url());
    }

    /**
     * @dataProvider missingValuesProvider
     */
    public function testFromValuesFailsIfRequiredValueIsMissing($values, $missing_value_name)
    {
        $this->expectException(Errors\ContentError::class);
        $this->expectExceptionMessage("{$missing_value_name} value is required.");

        Content::fromValues($values);
    }

    /**
     * @dataProvider integerValuesNamesProvider
     */
    public function testFromValuesFailsIfIntegerValueCannotBeParsed($value_name)
    {
        $this->expectException(Errors\ContentError::class);
        $this->expectExceptionMessage("{$value_name} value must be an integer.");

        $values = [
            'id' => '1',
            'created_at' => '10000',
            'url' => 'https://some.site.fr/feed.xml',
        ];
        $values[$value_name] = 'not an integer';

        Content::fromValues($values);
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

    public function integerValuesNamesProvider()
    {
        return [
            ['id'],
            ['created_at'],
        ];
    }

    public function missingValuesProvider()
    {
        $default_values = [
            'id' => '1',
            'created_at' => '10000',
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
