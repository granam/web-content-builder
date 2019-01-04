<?php
declare(strict_types=1);

namespace Granam\WebContentBuilder\Web;

use Granam\WebContentBuilder\HtmlDocument;
use Granam\WebContentBuilder\HtmlHelper;
use Granam\Strict\Object\StrictObject;

class Content extends StrictObject
{
    /** @var HtmlHelper */
    private $htmlHelper;
    /** @var Head */
    private $head;
    /** @var Body */
    private $body;

    public function __construct(HtmlHelper $htmlHelper, Head $head, Body $body)
    {
        $this->htmlHelper = $htmlHelper;
        $this->head = $head;
        $this->body = $body;
    }

    public function __toString()
    {
        return $this->getStringContent();
    }

    public function getStringContent(): string
    {
        $content = $this->composeContent();
        $htmlDocument = $this->buildHtmlDocument($content);

        return $htmlDocument->saveHTML();
    }

    private function buildHtmlDocument(string $content): HtmlDocument
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