<?php

declare(strict_types=1);

use App\Actions\News\ImportScrapedArticles;
use App\Enums\ArticleCategory;
use App\Models\Article;
use App\Models\ScrapeSource;
use App\News\ScrapedArticle;
use Carbon\CarbonImmutable;

function feedItem(
    string $externalId = 'b2qbp9uhh',
    string $title = 'realme 16 Pro',
    ?string $body = '<p>Isi artikel yang panjang.</p>',
    ?string $imageUrl = null,
): ScrapedArticle {
    return new ScrapedArticle(
        externalId: $externalId,
        title: $title,
        excerpt: 'Meluncur di Indonesia.',
        url: 'https://www.tek.id/gadget/realme-'.$externalId,
        publishedAt: CarbonImmutable::parse('2026-09-21 12:18:00'),
        body: $body,
        imageUrl: $imageUrl,
    );
}

function sourceForImport(ArticleCategory $category = ArticleCategory::Rilis): ScrapeSource
{
    return ScrapeSource::factory()->create(['category' => $category]);
}

it('creates an article for each new item', function (): void {
    $created = app(ImportScrapedArticles::class)->handle(
        [feedItem('b2qbp9uhh'), feedItem('b2qbb9ugd')],
        sourceForImport(),
    );

    expect($created)->toBe(2)
        ->and(Article::scraped()->count())->toBe(2);
});

it('stores every field', function (): void {
    app(ImportScrapedArticles::class)->handle(
        [feedItem(imageUrl: 'https://static.tek.id/a.jpeg')],
        sourceForImport(ArticleCategory::Ulasan),
    );

    $article = Article::scraped()->sole();

    expect($article->title)->toBe('realme 16 Pro')
        ->and($article->slug)->toBe('realme-16-pro')
        ->and($article->category)->toBe(ArticleCategory::Ulasan)
        ->and($article->excerpt)->toBe('Meluncur di Indonesia.')
        ->and($article->body)->toBe('<p>Isi artikel yang panjang.</p>')
        ->and($article->image_url)->toBe('https://static.tek.id/a.jpeg')
        ->and($article->source)->toBe('tek.id')
        ->and($article->source_url)->toBe('https://www.tek.id/gadget/realme-b2qbp9uhh')
        ->and($article->external_id)->toBe('b2qbp9uhh')
        ->and($article->tags)->toBe([])
        ->and($article->published_at->toDateTimeString())->toBe('2026-09-21 12:18:00');
});

it('never stores an article with no body', function (): void {
    $created = app(ImportScrapedArticles::class)->handle(
        [feedItem('b2qbp9uhh', body: null), feedItem('b2qbb9ugd')],
        sourceForImport(),
    );

    expect($created)->toBe(1)
        ->and(Article::scraped()->count())->toBe(1)
        ->and(Article::scraped()->sole()->external_id)->toBe('b2qbb9ugd');
});

it('computes read minutes from the body length', function (): void {
    $body = '<p>'.str_repeat('kata ', 600).'</p>';

    app(ImportScrapedArticles::class)->handle([feedItem(body: $body)], sourceForImport());

    // 600 words at 200 words per minute.
    expect(Article::scraped()->sole()->read_minutes)->toBe(3);
});

it('gives a very short article at least one minute', function (): void {
    app(ImportScrapedArticles::class)->handle([feedItem(body: '<p>Singkat sekali.</p>')], sourceForImport());

    expect(Article::scraped()->sole()->read_minutes)->toBe(1);
});

it('is idempotent across repeated runs', function (): void {
    $items = [feedItem('b2qbp9uhh'), feedItem('b2qbb9ugd')];
    $source = sourceForImport();

    app(ImportScrapedArticles::class)->handle($items, $source);
    $second = app(ImportScrapedArticles::class)->handle($items, $source);

    expect($second)->toBe(0)
        ->and(Article::scraped()->count())->toBe(2);
});

it('updates a changed title in place', function (): void {
    $source = sourceForImport();

    app(ImportScrapedArticles::class)->handle([feedItem(title: 'Judul lama')], $source);
    app(ImportScrapedArticles::class)->handle([feedItem(title: 'Judul baru')], $source);

    expect(Article::scraped()->count())->toBe(1)
        ->and(Article::scraped()->sole()->title)->toBe('Judul baru');
});

it('suffixes a slug that collides with one of our own articles', function (): void {
    Article::factory()->create(['slug' => 'realme-16-pro', 'title' => 'Tulisan kami sendiri']);

    app(ImportScrapedArticles::class)->handle([feedItem()], sourceForImport());

    expect(Article::scraped()->sole()->slug)->toBe('realme-16-pro-2')
        ->and(Article::own()->sole()->title)->toBe('Tulisan kami sendiri');
});

it('never modifies an article we wrote', function (): void {
    $ours = Article::factory()->create([
        'title' => 'Tulisan kami',
        'body' => '<p>Isi kami.</p>',
        'external_id' => null,
    ]);

    app(ImportScrapedArticles::class)->handle([feedItem()], sourceForImport());

    expect($ours->fresh()->title)->toBe('Tulisan kami')
        ->and($ours->fresh()->body)->toBe('<p>Isi kami.</p>')
        ->and($ours->fresh()->source)->toBeNull();
});

it('reports which external ids it already has', function (): void {
    $source = sourceForImport();
    app(ImportScrapedArticles::class)->handle([feedItem('b2qbp9uhh')], $source);

    $known = app(ImportScrapedArticles::class)->existingIds([feedItem('b2qbp9uhh'), feedItem('b2qbb9ugd')]);

    expect($known)->toBe(['b2qbp9uhh']);
});

it('reports no known ids for an empty batch', function (): void {
    expect(app(ImportScrapedArticles::class)->existingIds([]))->toBe([]);
});
