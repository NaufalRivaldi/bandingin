<?php

declare(strict_types=1);

namespace App\Actions\News;

use App\News\ScrapedArticle;
use App\News\TekChannel;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;

/**
 * Fetches and parses one tek.id RSS channel.
 *
 * Touches no database, so it is testable with Http::fake() alone. A whole-feed
 * problem throws and lets the caller fail that channel; a single malformed item
 * is skipped so one bad entry cannot cost us the other nine.
 */
final class FetchTekChannel
{
    /** @return list<ScrapedArticle> */
    public function handle(TekChannel $channel): array
    {
        $body = Http::withUserAgent((string) config('services.tek.user_agent'))
            ->timeout(15)
            ->retry(2, 500)
            ->get($channel->feedUrl())
            ->throw()
            ->body();

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            throw new RuntimeException(
                "tek.id {$channel->value} feed is not valid XML (".strlen($body).' bytes).'
            );
        }

        $articles = [];

        foreach ($xml->channel->item as $item) {
            $article = $this->toArticle($channel, $item);

            if ($article instanceof ScrapedArticle) {
                $articles[] = $article;
            }
        }

        return $articles;
    }

    private function toArticle(TekChannel $channel, SimpleXMLElement $item): ?ScrapedArticle
    {
        $url = trim((string) $item->link);
        $title = trim((string) $item->title);
        $externalId = $this->externalId($url);
        $publishedAt = $this->publishedAt(trim((string) $item->pubDate));

        if ($title === '' || $externalId === null || ! $publishedAt instanceof CarbonImmutable) {
            return null;
        }

        return new ScrapedArticle(
            externalId: $externalId,
            channel: $channel,
            title: html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            excerpt: html_entity_decode(trim((string) $item->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            url: $url,
            publishedAt: $publishedAt,
        );
    }

    /**
     * tek.id URLs end in a stable token ("...-b2qbp9uhh") that survives edits to
     * the title slug in front of it. That token, not the URL, is our identity.
     */
    private function externalId(string $url): ?string
    {
        if ($url === '' || ! str_contains($url, '-')) {
            return null;
        }

        $token = Str::afterLast($url, '-');

        return preg_match('/^[A-Za-z0-9]{6,}$/', $token) === 1 ? $token : null;
    }

    /** The feed emits Jakarta local time with no offset. Normalise to UTC here. */
    private function publishedAt(string $raw): ?CarbonImmutable
    {
        if ($raw === '') {
            return null;
        }

        try {
            $parsed = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $raw, 'Asia/Jakarta');
        } catch (InvalidFormatException) {
            return null;
        }

        return $parsed instanceof CarbonImmutable ? $parsed->utc() : null;
    }
}
