<?php
declare(strict_types=1);

namespace Granam\WebContentBuilder\Web;

use Granam\String\StringInterface;
use Granam\WebContentBuilder\HtmlDocument;
use Granam\WebContentBuilder\HtmlHelper;
use Granam\Strict\Object\StrictObject;

class Content extends StrictObject implements StringInterface
{
    /** @var HtmlHelper */
    protected $htmlHelper;
    /** @var Head */
    private $head;
    /** @var Body */
    private $body;
    /** @var HtmlDocument */
    private $htmlDocument;

    public function __construct(HtmlHelper $htmlHelper, Head $head, Body $body)
    {
        $this->htmlHelper = $htmlHelper;
        $this->head = $head;
        $this->body = $body;
    }

    public function __toString()
    {
        return $this->getValue();
    }

    public function getValue(): string
    {
        return $this->getHtmlDocument()->saveHTML();
    }

    public function getHtmlDocument(): HtmlDocument
    {
        if (!$this->htmlDocument) {
            $content = $this->composeContent();
            $this->htmlDocument = $this->buildHtmlDocument($content);
        }

        return $this->htmlDocument;
    }

    protected function buildHtmlDocument(string $content): HtmlDocument
    {
        $htmlDocument = new HtmlDocument($content);
        $this->htmlHelper->addIdsToHeadings($htmlDocument);
        $this->htmlHelper->replaceDiacriticsFromIds($htmlDocument);
        $this->htmlHelper->replaceDiacriticsFromAnchorHashes($htmlDocument);
        $this->htmlHelper->addAnchorsToIds($htmlDocument);
        $this->htmlHelper->externalLinksTargetToBlank($htmlDocument);
        $this->htmlHelper->addVersionHashToAssets($htmlDocument);

        return $htmlDocument;
    }

    private function composeContent(): string
    {
        $head = $this->head->getHeadString();
        $body = $this->body->getBodyString();

        return <<<HTML
<!DOCTYPE html>
<html lang="cs">
<head>
    {$head}
</head>
<body class="container">
    {$body}
</body>
</html>
HTML;
    }
}