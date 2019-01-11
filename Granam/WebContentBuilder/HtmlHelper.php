<?php
declare(strict_types=1);

namespace Granam\WebContentBuilder;

use Granam\Strict\Object\StrictObject;
use Granam\String\StringTools;
use Gt\Dom\Element;
use Gt\Dom\HTMLCollection;

class HtmlHelper extends StrictObject
{

    public const CLASS_INVISIBLE_ID = 'invisible-id';
    public const DATA_ORIGINAL_ID = 'data-original-id';
    public const CLASS_INTERNAL_URL = 'internal-url';

    /** @var Dirs */
    private $dirs;

    /**
     * Turn link into local version
     * @param string $name
     * @return string
     * @throws \Granam\WebContentBuilder\Exceptions\NameToCreateHtmlIdFromIsEmpty
     */
    public static function toId(string $name): string
    {
        if ($name === '') {
            throw new Exceptions\NameToCreateHtmlIdFromIsEmpty('Expected some name to create HTML ID from');
        }

        return StringTools::toSnakeCaseId($name);
    }

    public function __construct(Dirs $dirs)
    {
        $this->dirs = $dirs;
    }

    public function addIdsToHeadings(HtmlDocument $htmlDocument): HtmlDocument
    {
        $elementNames = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        foreach ($elementNames as $elementName) {
            /** @var Element $headerCell */
            foreach ($htmlDocument->getElementsByTagName($elementName) as $headerCell) {
                if ($headerCell->getAttribute('id')) {
                    continue;
                }
                $id = false;
                /** @var \DOMNode $childNode */
                foreach ($headerCell->childNodes as $childNode) {
                    if ($childNode->nodeType === \XML_TEXT_NODE) {
                        $id = \trim($childNode->nodeValue);
                        break;
                    }
                }
                if (!$id) {
                    continue;
                }
                $headerCell->setAttribute('id', $id);
            }
        }

        return $htmlDocument;
    }

    public function replaceDiacriticsFromIds(HtmlDocument $htmlDocument): HtmlDocument
    {
        $this->replaceDiacriticsFromChildrenIds($htmlDocument->body->children);

        return $htmlDocument;
    }

    private function replaceDiacriticsFromChildrenIds(HTMLCollection $children): void
    {
        foreach ($children as $child) {
            // recursion
            $this->replaceDiacriticsFromChildrenIds($child->children);
            $id = $child->getAttribute('id');
            if (!$id) {
                continue;
            }
            $idWithoutDiacritics = static::toId($id);
            if ($idWithoutDiacritics === $id) {
                continue;
            }
            $child->setAttribute(self::DATA_ORIGINAL_ID, $id);
            $child->setAttribute('id', $this->sanitizeId($idWithoutDiacritics));
            $child->appendChild($invisibleId = new Element('span'));
            $invisibleId->setAttribute('id', $this->sanitizeId($id));
            $invisibleId->className = self::CLASS_INVISIBLE_ID;
        }
    }

    private function sanitizeId(string $id): string
    {
        return \str_replace('#', '_', $id);
    }

    public function replaceDiacriticsFromAnchorHashes(HtmlDocument $htmlDocument): void
    {
        $this->replaceDiacriticsFromChildrenAnchorHashes($htmlDocument->getElementsByTagName('a'));
    }

    private function replaceDiacriticsFromChildrenAnchorHashes(\Traversable $children): void
    {
        /** @var Element $child */
        foreach ($children as $child) {
            // recursion
            $this->replaceDiacriticsFromChildrenAnchorHashes($child->children);
            $href = $child->getAttribute('href');
            if (!$href) {
                continue;
            }
            $hashPosition = \strpos($href, '#');
            if ($hashPosition === false) {
                continue;
            }
            $hash = substr($href, $hashPosition + 1);
            if ($hash === '') {
                continue;
            }
            $hashWithoutDiacritics = static::toId($hash);
            if ($hashWithoutDiacritics === $hash) {
                continue;
            }
            $hrefWithoutDiacritics = substr($href, 0, $hashPosition) . '#' . $hashWithoutDiacritics;
            $child->setAttribute('href', $hrefWithoutDiacritics);
        }
    }

    /**
     * @param HtmlDocument $htmlDocument
     * @return HtmlDocument
     */
    public function addAnchorsToIds(HtmlDocument $htmlDocument): HtmlDocument
    {
        $this->addAnchorsToChildrenWithIds($htmlDocument->body->children);

        return $htmlDocument;
    }

    private function addAnchorsToChildrenWithIds(HTMLCollection $children): void
    {
        /** @var Element $child */
        foreach ($children as $child) {
            if (!\in_array($child->nodeName, ['a', 'button'], true)
                && $child->getAttribute('id')
                && $child->getElementsByTagName('a')->length === 0 // already have some anchors, skipp it to avoid wrapping them by another one
                && !$child->prop_get_classList()->contains(self::CLASS_INVISIBLE_ID)
            ) {
                $toMove = [];
                /** @var \DOMElement $grandChildNode */
                foreach ($child->childNodes as $grandChildNode) {
                    if (!\in_array($grandChildNode->nodeName, ['span', 'strong', 'b', 'i', '#text'], true)) {
                        break;
                    }
                    $toMove[] = $grandChildNode;
                }
                if (\count($toMove) > 0) {
                    $anchorToSelf = new Element('a');
                    $child->replaceChild($anchorToSelf, $toMove[0]); // pairs anchor with parent element
                    $anchorToSelf->setAttribute('href', '#' . $child->getAttribute('id'));
                    foreach ($toMove as $index => $item) {
                        $anchorToSelf->appendChild($item);
                    }
                }
            }
            // recursion
            $this->addAnchorsToChildrenWithIds($child->children);
        }
    }

    /**
     * @param HtmlDocument $htmlDocument
     * @throws \LogicException
     */
    public function externalLinksTargetToBlank(HtmlDocument $htmlDocument): void
    {
        /** @var Element $anchor */
        foreach ($htmlDocument->getElementsByTagName('a') as $anchor) {
            if (!$anchor->getAttribute('target')
                && !$anchor->classList->contains(self::CLASS_INTERNAL_URL)
                && \preg_match('~^(https?:)?//[^#]~', $anchor->getAttribute('href') ?? '')
            ) {
                $anchor->setAttribute('target', '_blank');
            }
        }
    }

    public function addVersionHashToAssets(HtmlDocument $htmlDocument): HtmlDocument
    {
        $documentRoot = $this->dirs->getProjectRoot();
        foreach ($htmlDocument->getElementsByTagName('img') as $image) {
            $this->addVersionToAsset($image, 'src', $documentRoot);
        }
        foreach ($htmlDocument->getElementsByTagName('link') as $link) {
            $this->addVersionToAsset($link, 'href', $documentRoot);
        }
        foreach ($htmlDocument->getElementsByTagName('script') as $script) {
            $this->addVersionToAsset($script, 'src', $documentRoot);
        }

        return $htmlDocument;
    }

    private function addVersionToAsset(Element $element, string $attributeName, string $masterDocumentRoot): void
    {
        $link = $element->getAttribute($attributeName);
        if ($this->isLinkExternal($link)) {
            return;
        }
        $absolutePath = $this->getAbsolutePath($link, $masterDocumentRoot);
        $hash = $this->getFileHash($absolutePath);
        $element->setAttribute($attributeName, $link . '?version=' . \urlencode($hash));
    }

    protected function isLinkExternal(string $link): bool
    {
        $urlParts = \parse_url($link);

        return !empty($urlParts['host']);
    }

    private function getAbsolutePath(string $relativePath, string $masterDocumentRoot): string
    {
        $relativePath = \ltrim($relativePath, '\\/');
        $absolutePath = $masterDocumentRoot . '/' . $relativePath;

        return \str_replace('/./', '/', $absolutePath);
    }

    private function getFileHash(string $fileName): string
    {
        return \md5_file($fileName) ?: (string)\time(); // time is a fallback
    }
}