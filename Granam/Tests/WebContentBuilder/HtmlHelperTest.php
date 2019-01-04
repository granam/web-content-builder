<?php
declare(strict_types=1);

namespace Granam\Tests\WebContentBuilder;

use Granam\WebContentBuilder\HtmlHelper;
use Granam\Tests\WebContentBuilder\Partials\AbstractContentTest;

class HtmlHelperTest extends AbstractContentTest
{
    /**
     * @test
     * @dataProvider providePublicToLocalLinks
     * @param string $publicLink
     * @param string $expectedLocalLink
     */
    public function I_can_turn_public_link_to_local(string $publicLink, string $expectedLocalLink): void
    {
        self::assertSame($expectedLocalLink, HtmlHelper::turnToLocalLink($publicLink));
    }

    public function providePublicToLocalLinks(): array
    {
        return [
            ['https://www.drdplus.info', 'http://www.drdplus.loc'],
            ['https://hranicar.drdplus.info', 'http://hranicar.drdplus.loc'],
            ['https://bestiar.ppj.drdplus.info', 'http://bestiar.ppj.drdplus.loc'],
        ];
    }

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
}