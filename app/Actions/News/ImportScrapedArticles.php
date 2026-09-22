<?php

declare(strict_types=1);

namespace App\Actions\News;

use App\Models\NewsItem;
use App\News\ScrapedArticle;

/**
 * Upserts scraped articles into news_items.
 *
 * Makes no HTTP requests, so it tests against hand-built DTOs alone. Identity is
 * the external_id token, never the URL: tek.id URLs embed the title slug, so an
 * edited headline would otherwise create a duplicate row.
 */
final class ImportScrapedArticles
{
    /**
     * @param  list<ScrapedArticle>  $articles
     * @return int the number of rows created
     */
    public function handle(array $articles): int
    {
        $created = 0;

        foreach ($articles as $article) {
            $item = NewsItem::firstOrNew(['external_id' => $article->externalId]);

            if (! $item->exists) {
                $created++;
            }

            $item->fill([
                'source' => 'tek.id',
                'channel' => $article->channel->value,
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'url' => $article->url,
                'published_at' => $article->publishedAt,
            ]);

            // A later fetch failure must not blank an image we already have.
            if ($article->imageUrl !== null) {
                $item->image_url = $article->imageUrl;
            }

            $item->save();
        }

        return $created;
    }

    /**
     * @param  list<ScrapedArticle>  $articles
     * @return list<string>
     */
    public function existingIds(array $articles): array
    {
        if ($articles === []) {
            return [];
        }

        $ids = array_map(static fn (ScrapedArticle $article): string => $article->externalId, $articles);

        return NewsItem::whereIn('external_id', $ids)
            ->pluck('external_id')
            ->all();
    }
}
