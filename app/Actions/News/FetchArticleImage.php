<?php

declare(strict_types=1);

namespace App\Actions\News;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Reads the og:image meta tag from one tek.id article page.
 *
 * This is the only reason an article page is ever requested — the RSS feed
 * carries no usable image. The body is never parsed or stored.
 *
 * Every failure yields null: one article's missing image must never cost us the
 * article itself, nor the rest of its channel.
 */
final class FetchArticleImage
{
    public function handle(string $url): ?string
    {
        try {
            $response = Http::withUserAgent((string) config('services.tek.user_agent'))
                ->timeout(15)
                ->get($url);
        } catch (ConnectionException|Throwable) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $matched = preg_match(
            '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i',
            $response->body(),
            $matches,
        );

        if ($matched !== 1) {
            return null;
        }

        $image = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return str_starts_with($image, 'http') ? $image : null;
    }
}
