<?php

declare(strict_types=1);

namespace App\Seo;

use App\Models\Article;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Artesaos\SEOTools\Facades\SEOMeta;

class ArticleStructuredData
{
    public function __construct(private readonly BreadcrumbStructuredData $breadcrumbs) {}

    public function handle(Article $article): void
    {
        $this->canonicalise($article);

        JsonLdMulti::setType('NewsArticle');
        JsonLdMulti::addValues(array_filter([
            'headline' => $article->title,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'articleSection' => $article->category->value,
            'keywords' => $article->tags,
            'author' => $this->author($article),
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('seo.organization.name'),
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('berita.show', $article->slug),
            ],
        ]));

        if (filled($article->image_url)) {
            JsonLdMulti::setImage($article->image_url);
        }

        $this->breadcrumbs->handle([
            'Berita' => route('berita'),
            $article->title => route('berita.show', $article->slug),
        ]);
    }

    /** A scraped article credits the publisher it came from, not us. */
    private function canonicalise(Article $article): void
    {
        if ($article->isScraped()) {
            SEOMeta::setCanonical($article->source_url);
        }
    }

    /** @return array<string, string> */
    private function author(Article $article): array
    {
        if ($article->isScraped()) {
            return ['@type' => 'Organization', 'name' => $article->source];
        }

        return ['@type' => 'Person', 'name' => config('seo.author')];
    }
}
