<?php
declare(strict_types=1);

namespace Granam\WebContentBuilder;

use Granam\Strict\Object\StrictObject;
use Granam\String\StringTools;
use Gt\Dom\Element;
use Gt\Dom\HTMLCollection;

class HtmlHelper extends StrictObject
{

    public const INVISIBLE_ID_CLASS = 'invisible-id';
    public const DATA_ORIGINAL_ID = 'data-original-id';
    public const EXTERNAL_URL_CLASS = 'external-url';
    public const INTERNAL_URL_CLASS = 'internal-url';
    public const COVERED_BY_CODE_CLASS = 'covered-by-code';
    public const EXCLUDED_CLASS = 'excluded';
    public const HIDDEN_CLASS = 'hidden';
    public const INVISIBLE_CLASS = 'invisible';
    public const META_REDIRECT_ID = 'meta_redirect';
    public const CONTENT_CLASS = 'content';
    public const DATA_CACHE_STAMP = 'data-cache-stamp';
    public const DATA_CACHED_AT = 'data-cached-at';

    /**
     * Turn link into local version
     * @param string $link
     * @return string
     */
    public static function turnToLocalLink(string $link): string
    {
        return \preg_replace('~https?://((?:[^.]+[.])*)drdplus\.info~', 'http://$1drdplus.loc', $link);
    }

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

    /**
     * @param HtmlDocument $html
     */
    public function addIdsToHeadings(HtmlDocument $html): void
    {
        $elementNames = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        foreach ($elementNames as $elementName) {
            /** @var Element $headerCell */
            foreach ($html->getElementsByTagName($elementName) as $headerCell) {
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
    }

    public function replaceDiacriticsFromIds(HtmlDocument $html): void
    {
        $this->replaceDiacriticsFromChildrenIds($html->body->children);
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
            $invisibleId->className = self::INVISIBLE_ID_CLASS;
        }
    }

    private function sanitizeId(string $id): string
    {
        return \str_replace('#', '_', $id);
    }

    public function replaceDiacriticsFromAnchorHashes(HtmlDocument $html): void
    {
        $this->replaceDiacriticsFromChildrenAnchorHashes($html->getElementsByTagName('a'));
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
                && !$child->prop_get_classList()->contains(self::INVISIBLE_ID_CLASS)
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
                && !$anchor->classList->contains(self::INTERNAL_URL_CLASS)
                && \preg_match('~^(https?:)?//[^#]~', $anchor->getAttribute('href') ?? '')
            ) {
                $anchor->setAttribute('target', '_blank');
            }
        }
    }

    public function addVersionHashToAssets(HtmlDocument $htmlDocument): void
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

    private function isLinkExternal(string $link): bool
    {
        $urlParts = \parse_url($link);

        return !empty($urlParts['host']);
    }

    private function getAbsolutePath(string $relativePath, string $masterDocumentRoot): string
    {
        $relativePath = \ltrim($relativePath, '\\/');

        return $masterDocumentRoot . '/' . $relativePath;
    }

    private function getFileHash(string $fileName): string
    {
        return \md5_file($fileName) ?: (string)\time(); // time is a fallback
    }
}