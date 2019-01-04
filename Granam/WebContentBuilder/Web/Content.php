<?php
declare(strict_types=1);

namespace Granam\WebContentBuilder\Web;

use Granam\WebContentBuilder\HtmlDocument;
use Granam\WebContentBuilder\HtmlHelper;
use Granam\WebContentBuilder\Redirect;
use Granam\Strict\Object\StrictObject;

class Content extends StrictObject
{
    /** @var HtmlHelper */
    private $htmlHelper;
    /** @var Head */
    private $head;
    /** @var Body */
    private $body;
    /** @var Redirect|null */
    private $redirect;

    public function __construct(HtmlHelper $htmlHelper, Head $head, Body $body, ?Redirect $redirect)
    {
        $this->htmlHelper = $htmlHelper;
        $this->head = $head;
        $this->body = $body;
        $this->redirect = $redirect;
    }

    public function __toString()
    {
        return $this->getStringContent();
    }

    public function getStringContent(): string
    {
        $content = $this->composeContent();
        $htmlDocument = $this->buildHtmlDocument($content);
        $updatedContent = $htmlDocument->saveHTML();

        // has to be AFTER cache as we do not want to cache it
        return $this->injectRedirectIfAny($updatedContent);
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

    private function injectRedirectIfAny(string $content): string
    {
        if (!$this->getRedirect()) {
            return $content;
        }
        $cachedDocument = new HtmlDocument($content);
        $meta = $cachedDocument->createElement('meta');
        $meta->setAttribute('http-equiv', 'Refresh');
        $meta->setAttribute('content', $this->getRedirect()->getAfterSeconds() . '; url=' . $this->getRedirect()->getTarget());
        $meta->setAttribute('id', 'meta_redirect');
        $cachedDocument->head->appendChild($meta);

        return $cachedDocument->saveHTML();
    }

    private function getRedirect(): ?Redirect
    {
        return $this->redirect;
    }
}