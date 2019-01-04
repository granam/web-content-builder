<?php
declare(strict_types=1);

namespace Granam\Tests\WebContentBuilder;

use Granam\WebContentBuilder\Dirs;
use Granam\Tests\WebContentBuilder\Partials\AbstractContentTest;

class DirsTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function I_can_use_it(): void
    {
        $dirsClass = static::getSutClass();
        /** @var Dirs $dirs */
        $dirs = new $dirsClass('foo');
        self::assertSame('foo', $dirs->getDocumentRoot());
        self::assertSame('foo/vendor', $dirs->getVendorRoot());
        self::assertSame('foo/css', $dirs->getCssRoot());
        self::assertSame('foo/js', $dirs->getJsRoot());
    }

    /**
     * @test
     */
    public function I_will_get_current_root_as_default_document_root(): void
    {
        $expectedDocumentRoot = \realpath($this->getDocumentRoot());
        self::assertFileExists($expectedDocumentRoot, 'No real path found from document root ' . $this->getDocumentRoot());
        self::assertSame($expectedDocumentRoot, \realpath($this->getDirs()->getDocumentRoot()));
    }
}