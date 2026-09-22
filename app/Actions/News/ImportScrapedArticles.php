<?php

declare(strict_types=1);

namespace App\Actions\News;

use App\Models\Article;
use App\Models\ScrapeSource;
use App\News\ScrapedArticle;
use Illuminate\Support\Str;

/**
 * Upserts scraped articles into the articles table.
 *
 * Every query here is scoped to rows where `source` is set. The importer can
 * create and update scraped articles and can never modify or overwrite one a
 * human wrote — including when a slug collides, where the scraped article takes
 * a numeric suffix instead. That invariant is what makes sharing the table safe.
 */
final class ImportScrapedArticles
{
    /** Average reading speed, in words per minute. */
    private const int WORDS_PER_MINUTE = 200;

    /** articles.read_minutes is an unsigned tiny int. */
    private const int MAX_READ_MINUTES = 255;

    /**
     * @param  list<ScrapedArticle>  $articles
     * @return int the number of articles created
     */
    public function handle(array $articles, ScrapeSource $source): int
    {
        $created = 0;

        foreach ($articles as $article) {
            // A missing body makes the article worthless. Skipping it without
            // storing anything means the next run retries it.
            if ($article->body === null || trim($article->body) === '') {
                continue;
            }

            $existing = Article::scraped()->where('external_id', $article->externalId)->first();

            $model = $existing ?? new Article(['external_id' => $article->externalId]);

            if ($existing === null) {
                $created++;
            }

            $model->fill([
                'slug' => $this->uniqueSlug($article->title, $existing?->getKey()),
                'title' => $article->title,
                'category' => $source->category,
                'excerpt' => $article->excerpt,
                'body' => $article->body,
                'tags' => [],
                'read_minutes' => $this->readMinutes($article->body),
                'published_at' => $article->publishedAt,
                'source' => 'tek.id',
                'source_url' => $article->url,
                'external_id' => $article->externalId,
            ]);

            if ($article->imageUrl !== null) {
                $model->image_url = $article->imageUrl;
            }

            $model->save();
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

        return Article::scraped()
            ->whereIn('external_id', $ids)
            ->pluck('external_id')
            ->all();
    }

    /** Checks the whole table, so a scraped article can never take our slug. */
    private function uniqueSlug(string $title, ?int $ignoreId): string
    {
        $base = Str::limit(Str::slug($title), 180, '');

        if ($base === '') {
            $base = 'artikel';
        }

        $slug = $base;
        $suffix = 1;

        while ($this->slugTaken($slug, $ignoreId)) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }

    private function slugTaken(string $slug, ?int $ignoreId): bool
    {
        return Article::where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    private function readMinutes(string $body): int
    {
        $text = trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $words = $text === '' ? 0 : count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return max(1, min(self::MAX_READ_MINUTES, (int) ceil($words / self::WORDS_PER_MINUTE)));
    }
}
