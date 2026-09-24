<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Models\Article;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class BuildArticlesSitemap
{
    /**
     * Every published article, scraped ones included.
     *
     * Scraped articles still canonicalise to their publisher, so they are
     * offered for crawling rather than claimed as canonical.
     */
    public function handle(): Sitemap
    {
        $sitemap = Sitemap::create();

        Article::published()
            ->select(['id', 'slug', 'updated_at'])
            ->chunkById(500, function ($articles) use ($sitemap): void {
                foreach ($articles as $article) {
                    $sitemap->add(
                        Url::create(route('berita.show', $article->slug))
                            ->setLastModificationDate($article->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                            ->setPriority(0.6)
                    );
                }
            });

        return $sitemap;
    }
}
