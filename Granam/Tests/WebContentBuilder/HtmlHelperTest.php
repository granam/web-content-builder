<?php
declare(strict_types=1);

namespace Granam\Tests\WebContentBuilder;

use Granam\WebContentBuilder\HtmlDocument;
use Granam\WebContentBuilder\HtmlHelper;
use Granam\Tests\WebContentBuilder\Partials\AbstractContentTest;
use Gt\Dom\Element;

class HtmlHelperTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_can_create_id_from_any_name(): void
    {
        /** @var HtmlHelper $htmlHelperClass */
        $htmlHelperClass = static::getSutClass();
        self::assertSame('kuala_lumpur', $htmlHelperClass::toId('Kuala lumpur'));
        self::assertSame('krizaly_s_mrkvi', $htmlHelperClass::toId('Křížaly s mrkví'));
    }

    /**
     * @test
     * @expectedException \Granam\WebContentBuilder\Exceptions\NameToCreateHtmlIdFromIsEmpty
     */
    public function I_can_not_create_id_from_empty_name(): void
    {
        /** @var HtmlHelper $htmlHelperClass */
        $htmlHelperClass = static::getSutClass();
        $htmlHelperClass::toId('');
    }

    /**
     * @test
     * @dataProvider provideLinkWithHash
     * @param string $linkWithHash
     * @param null|string $includingUrlPattern
     * @param null|string $excludingUrlPattern
     * @param string $expectedLinkWithReplacedHash
     */
    public function I_can_replace_diacritics_from_link_hashes(
        string $linkWithHash,
        ?string $includingUrlPattern,
        ?string $excludingUrlPattern,
        string $expectedLinkWithReplacedHash
    ): void
    {
        $htmlHelper = $this->createHtmlHelper();
        $withReplacedHashes = $htmlHelper->replaceDiacriticsFromAnchorHashes(
            new HtmlDocument(<<<HTML
<html lang="en">
<body>
<a id="just_some_link" href="{$linkWithHash}">Just some link</a>
</body>
</html>
HTML
            ),
            $includingUrlPattern,
            $excludingUrlPattern
        );
        /** @var Element $anchor */
        $anchor = $withReplacedHashes->getElementById('just_some_link');
        self::assertNotEmpty($anchor);
        self::assertSame($expectedLinkWithReplacedHash, $anchor->getAttribute('href'));
    }

    public function provideLinkWithHash(): array
    {
        return [
            'no hash at all' => ['https://example.com', null, null, 'https://example.com'],
            'with simple hash' => ['https://example.com#foo', null, null, 'https://example.com#foo'],
            'hash with diacritics' => ['https://example.com#fůů', null, null, 'https://example.com#fuu'],
            'hash with diacritics and included link' => ['https://example.com#fůů', '~example[.]com~', null, 'https://example.com#fuu'],
            'hash with diacritics and included but also excluded link' => ['https://example.com#fůů', '~example[.]com~', '~example~', 'https://example.com#fůů'],
            'hash with diacritics and missing match' => ['https://example.com#fůů', '~bar~', null, 'https://example.com#fůů'],
            'hash with diacritics and "including" hash' => ['https://example.com#fůů', '~fůů~', null, 'https://example.com#fůů'],
            'hash with diacritics and "excluding" hash' => ['https://example.com#fůů', null, '~fůů~', 'https://example.com#fuu'],
        ];
    }
}