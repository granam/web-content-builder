<?php
declare(strict_types=1);

namespace Granam\WebContentBuilder;

use Granam\Strict\Object\StrictObject;

class Dirs extends StrictObject
{
    /** @var string */
    private $documentRoot;
    /** @var string */
    private $vendorRoot;
    /** @var string */
    private $jsRoot;
    /** @var string */
    private $cssRoot;

    public static function createFromGlobals()
    {
        return new static($GLOBALS['documentRoot'] ?? $_SERVER['PROJECT_DIR'] ?? $_SERVER['DOCUMENT_ROOT'] ?? getcwd());
    }

    public function __construct(string $documentRoot)
    {
        $this->documentRoot = $documentRoot;
        $this->populateSubRoots($documentRoot);
    }

    private function populateSubRoots(string $documentRoot): void
    {
        $this->vendorRoot = $documentRoot . '/vendor';
        $this->jsRoot = $documentRoot . '/js';
        $this->cssRoot = $documentRoot . '/css';
    }

    /**
     * @return string
     */
    public function getDocumentRoot(): string
    {
        return $this->documentRoot;
    }

    /**
     * @return string
     */
    public function getVendorRoot(): string
    {
        return $this->vendorRoot;
    }

    public function getJsRoot(): string
    {
        return $this->jsRoot;
    }

    public function getCssRoot(): string
    {
        return $this->cssRoot;
    }
}