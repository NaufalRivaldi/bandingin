<?php

declare(strict_types=1);

use App\Actions\News\ImportScrapedArticles;
use App\Models\NewsItem;
use App\News\ScrapedArticle;
use App\News\TekChannel;
use Carbon\CarbonImmutable;

function scrapedArticle(string $externalId = 'b2qbp9uhh', ?string $imageUrl = null, string $title = 'realme 16 Pro'): ScrapedArticle
{
    return new ScrapedArticle(
        externalId: $externalId,
        channel: TekChannel::Gadget,
        title: $title,
        excerpt: 'Meluncur di Indonesia.',
        url: 'https://www.tek.id/gadget/realme-'.$externalId,
        publishedAt: CarbonImmutable::parse('2026-09-21 12:18:00'),
        imageUrl: $imageUrl,
    );
}

it('creates a row for each new article', function (): void {
    $created = app(ImportScrapedArticles::class)->handle([
        scrapedArticle('b2qbp9uhh'),
        scrapedArticle('b2qbb9ugd'),
    ]);

    expect($created)->toBe(2)
        ->and(NewsItem::count())->toBe(2);
});

it('stores every field from the dto', function (): void {
    app(ImportScrapedArticles::class)->handle([scrapedArticle(imageUrl: 'https://static.tek.id/a.jpeg')]);

    $item = NewsItem::sole();

    expect($item->external_id)->toBe('b2qbp9uhh')
        ->and($item->source)->toBe('tek.id')
        ->and($item->channel)->toBe('Gadget')
        ->and($item->title)->toBe('realme 16 Pro')
        ->and($item->excerpt)->toBe('Meluncur di Indonesia.')
        ->and($item->url)->toBe('https://www.tek.id/gadget/realme-b2qbp9uhh')
        ->and($item->image_url)->toBe('https://static.tek.id/a.jpeg')
        ->and($item->published_at->toDateTimeString())->toBe('2026-09-21 12:18:00');
});

it('is idempotent across repeated runs', function (): void {
    $articles = [scrapedArticle('b2qbp9uhh'), scrapedArticle('b2qbb9ugd')];

    app(ImportScrapedArticles::class)->handle($articles);
    $secondRun = app(ImportScrapedArticles::class)->handle($articles);

    expect($secondRun)->toBe(0)
        ->and(NewsItem::count())->toBe(2);
});

it('updates a changed title in place', function (): void {
    app(ImportScrapedArticles::class)->handle([scrapedArticle(title: 'Judul lama')]);
    app(ImportScrapedArticles::class)->handle([scrapedArticle(title: 'Judul baru')]);

    expect(NewsItem::count())->toBe(1)
        ->and(NewsItem::sole()->title)->toBe('Judul baru');
});

it('does not blank an existing image when the new dto has none', function (): void {
    app(ImportScrapedArticles::class)->handle([scrapedArticle(imageUrl: 'https://static.tek.id/a.jpeg')]);
    app(ImportScrapedArticles::class)->handle([scrapedArticle(imageUrl: null)]);

    expect(NewsItem::sole()->image_url)->toBe('https://static.tek.id/a.jpeg');
});

it('reports which external ids it already has', function (): void {
    app(ImportScrapedArticles::class)->handle([scrapedArticle('b2qbp9uhh')]);

    $known = app(ImportScrapedArticles::class)->existingIds([
        scrapedArticle('b2qbp9uhh'),
        scrapedArticle('b2qbb9ugd'),
    ]);

    expect($known)->toBe(['b2qbp9uhh']);
});

it('reports no known ids for an empty batch', function (): void {
    expect(app(ImportScrapedArticles::class)->existingIds([]))->toBe([]);
});
