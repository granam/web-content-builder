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
     * @backupGlobals enabled
     * @dataProvider provideDocumentRootSource
     * @param string $testingDocumentRoot
     * @param string $serverProjectDir
     * @param string $serverDocumentRoot
     * @param string $testingCwd
     * @param string $expectedDocumentRoot
     */
    public function I_can_create_it_from_globals(
        ?string $testingDocumentRoot,
        ?string $serverProjectDir,
        ?string $serverDocumentRoot,
        string $testingCwd,
        string $expectedDocumentRoot
    ): void
    {
        global $documentRoot;
        $originalDocumentRoot = $documentRoot;
        $originalCwd = getcwd();
        $documentRoot = $testingDocumentRoot;
        $_SERVER['PROJECT_DIR'] = $serverProjectDir;
        $_SERVER['DOCUMENT_ROOT'] = $serverDocumentRoot;
        if ($originalCwd !== $testingCwd) {
            chdir($testingCwd);
        }
        $dirs = Dirs::createFromGlobals();
        $resultingDocumentRoot = $dirs->getDocumentRoot();
        $documentRoot = $originalDocumentRoot;
        if ($originalCwd !== $testingCwd) {
            chdir($originalCwd);
        }
        self::assertSame($expectedDocumentRoot, $resultingDocumentRoot);
    }

    public function provideDocumentRootSource(): array
    {
        return [
            ['globalDocumentRoot' => null, 'SERVER_PROJECT_DIR' => null, 'SERVER_DOCUMENT_ROOT' => null, 'cwd' => __DIR__, __DIR__],
            ['globalDocumentRoot' => null, 'SERVER_PROJECT_DIR' => null, 'SERVER_DOCUMENT_ROOT' => 'foo', 'cwd' => __DIR__, 'foo'],
            ['globalDocumentRoot' => null, 'SERVER_PROJECT_DIR' => 'bar', 'SERVER_DOCUMENT_ROOT' => 'foo', 'cwd' => __DIR__, 'bar'],
            ['globalDocumentRoot' => 'baz', 'SERVER_PROJECT_DIR' => 'bar', 'SERVER_DOCUMENT_ROOT' => 'foo', 'cwd' => __DIR__, 'baz'],
        ];
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