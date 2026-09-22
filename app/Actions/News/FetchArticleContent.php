<?php

declare(strict_types=1);

namespace App\Actions\News;

use App\News\ArticleContent;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Extracts the publishable body and hero image from one tek.id article page.
 *
 * Selectors are tek.id-specific. They move onto the source row when a second
 * site with different markup is actually added, not before.
 *
 * Every failure yields null, and the importer refuses to store a body-less
 * article — so a failed extraction retries on the next run instead of
 * publishing an empty page.
 */
final class FetchArticleContent
{
    /** Shortest body worth publishing, in characters of visible text. */
    private const int MIN_TEXT_LENGTH = 200;

    /**
     * Ads, share widgets and the "Baca Juga" related-articles block, all of
     * which tek.id injects inline between paragraphs.
     */
    private const string JUNK_XPATH = './/div[contains(@class,"bacajuga")]'
        .' | .//div[contains(@class,"flying_carpet")]'
        .' | .//section[contains(@class,"flying_carpet")]'
        .' | .//div[contains(@id,"div-gpt-ad")]'
        .' | .//div[contains(@class,"sharethis")]'
        .' | .//script | .//style | .//iframe | .//ins | .//noscript';

    public function handle(string $url): ?ArticleContent
    {
        try {
            $response = Http::withUserAgent((string) config('services.tek.user_agent'))
                ->timeout(15)
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $xpath = $this->parse($response->body());

        if (! $xpath instanceof DOMXPath) {
            return null;
        }

        $body = $this->body($xpath);

        return $body === null ? null : new ArticleContent($body, $this->heroImage($xpath));
    }

    private function parse(string $html): ?DOMXPath
    {
        if (trim($html) === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument;
        // The meta hint forces UTF-8; tek.id's markup is not always declared.
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8">'.$html,
            LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? new DOMXPath($document) : null;
    }

    private function body(DOMXPath $xpath): ?string
    {
        $content = $xpath->query('//div[contains(@class,"artikel__content")]')->item(0);

        if (! $content instanceof DOMElement) {
            return null;
        }

        foreach (iterator_to_array($xpath->query(self::JUNK_XPATH, $content)) as $junk) {
            $junk->parentNode?->removeChild($junk);
        }

        $html = '';

        foreach ($content->childNodes as $child) {
            $html .= $content->ownerDocument->saveHTML($child);
        }

        $html = $this->normaliseWhitespace($html);

        $textLength = mb_strlen(trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $textLength >= self::MIN_TEXT_LENGTH ? $html : null;
    }

    /** tek.id's source is heavily indented; left alone it bloats every row. */
    private function normaliseWhitespace(string $html): string
    {
        $html = preg_replace('/\s+/u', ' ', $html) ?? $html;
        $html = str_replace('> <', '><', $html);

        return trim($html);
    }

    private function heroImage(DOMXPath $xpath): ?string
    {
        $image = $xpath->query('//div[contains(@class,"artikel__imagecontent")]//img/@src')->item(0);
        $source = $image?->nodeValue;

        if ($source === null || $source === '') {
            // Fall back to the social card, which every article page carries.
            $source = $xpath->query('//meta[@property="og:image"]/@content')->item(0)?->nodeValue;
        }

        return is_string($source) && str_starts_with($source, 'http') ? $source : null;
    }
}
