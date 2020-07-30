<?php declare(strict_types=1);

namespace Granam\Tests\WebContentBuilder;

use Granam\WebContentBuilder\AssetsVersion;
use Granam\Tests\WebContentBuilder\Partials\AbstractContentTest;

class AssetsVersionTest extends AbstractContentTest
{
    /**
     * @test
     */
    public function All_css_files_have_versioned_assets(): void
    {
        $assetsVersionClass = static::getSutClass();
        /** @var AssetsVersion $assetsVersion */
        $assetsVersion = new $assetsVersionClass(true /* scan for CSS */);
        $changedFiles = $assetsVersion->addVersionsToAssetLinks(
            $this->getProjectRoot(),
            [$this->getDirs()->getCssRoot()],
            [],
            [],
            true // dry run
        );
        self::assertCount(
            0,
            $changedFiles,
            "Expected all CSS files already transpiled to have versioned links to assets, but those are not: \n"
            . \implode("\n", $changedFiles)
            . "\ntranspile them:\nphp ./vendor/bin/assets --css --dir=css"
        );
    }

    protected function getBinAssetsFile(): string
    {
        $assetsFile = $this->getDirs()->getVendorRoot() . '/bin/assets';
        if (!\file_exists($assetsFile)) {
            $assetsFile = $this->getProjectRoot() . '/bin/assets';
        }
        if (!\file_exists($assetsFile)) {
            throw new \LogicException('Can not find bin/assets file');
        }

        return $assetsFile;
    }

    /**
     * @test
     */
    public function Can_inject_versions_to_assets(): void
    {
        $assetsVersion = new AssetsVersion();
        $changedFiles = $assetsVersion->addVersionsToAssetLinks(
            __DIR__ . '/../../..',
            [__DIR__ . '/../../../web'],
            [],
            [],
            true // dry run
        );
        self::assertSame(
            [__DIR__ . '/../../../web/foo.html'],
            $changedFiles,
            "Expected all CSS files already transpiled to have versioned links to assets, but those are not: \n"
            . \implode("\n", $changedFiles)
            . "\ntranspile them:\nphp ./vendor/bin/assets --css --dir=css"
        );
    }

    /**
     * @test
     */
    public function I_can_use_helper_script(): void
    {
        $binAssetsEscaped = \escapeshellarg($this->getBinAssetsFile());
        $output = $this->runCommand("php $binAssetsEscaped");
        self::assertNotEmpty($output);
        self::assertStringStartsWith('Options are', $output[0]);
    }

    /**
     * @test
     */
    public function I_can_run_script_for_cli_assets_control_with_dry_run(): void
    {
        $binAssetsFile = __DIR__ . '/../../../bin/assets';
        $filePermissions = \fileperms($binAssetsFile);
        $inOctal = \decoct($filePermissions & 0777);
        self::assertSame(
            '775',
            $inOctal,
            "Expected {$binAssetsFile} to has executable permissions 0775 as Composer will do that anyway later on this library installation"
        );
        $fileContentBefore = file_get_contents(__DIR__ . '/../../../web/foo.html');
        $command = \escapeshellarg($binAssetsFile) . ' --dir=. --html --dry-run 2>&1';
        \exec($command, $output, $return);
        $fileContentAfter = file_get_contents(__DIR__ . '/../../../web/foo.html');
        self::assertSame(0, $return, $command . ' failed with output ' . \implode("\n", $output));
        self::assertSame($fileContentBefore, $fileContentAfter, 'File should not be changed on --dry-run');
    }

    /**
     * @test
     */
    public function I_can_run_script_for_cli_assets_control(): void
    {
        $filePermissions = \fileperms($this->getBinAssetsFile());
        $inOctal = \decoct($filePermissions & 0777);
        self::assertSame(
            '775',
            $inOctal,
            "Expected {$this->getBinAssetsFile()} to has executable permissions 0775 as Composer will do that anyway later on this library installation"
        );
        $command = \escapeshellarg($this->getBinAssetsFile()) . ' --dir=. --css --html --md --dry-run 2>&1';
        \exec($command, $output, $return);
        self::assertSame(0, $return, $command . ' failed with output ' . \implode("\n", $output));
    }

}