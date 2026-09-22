<?php

declare(strict_types=1);

use App\News\ScrapedArticle;
use App\News\TekChannel;
use Carbon\CarbonImmutable;

it('covers all seven tek.id channels', function (): void {
    expect(TekChannel::cases())->toHaveCount(7);
});

it('builds a feed url from the configured base url', function (): void {
    config()->set('services.tek.base_url', 'https://www.tek.id');

    expect(TekChannel::Gadget->feedUrl())->toBe('https://www.tek.id/gadget/rss')
        ->and(TekChannel::Tektalk->feedUrl())->toBe('https://www.tek.id/tektalk/rss');
});

it('strips a trailing slash from the configured base url', function (): void {
    config()->set('services.tek.base_url', 'https://www.tek.id/');

    expect(TekChannel::Insight->feedUrl())->toBe('https://www.tek.id/insight/rss');
});

it('returns a new instance when an image is attached', function (): void {
    $article = new ScrapedArticle(
        externalId: 'b2qbp9uhh',
        channel: TekChannel::Gadget,
        title: 'realme 16 Pro Harry Potter Edition',
        excerpt: 'Meluncur di Indonesia.',
        url: 'https://www.tek.id/gadget/realme-b2qbp9uhh',
        publishedAt: CarbonImmutable::parse('2026-09-21 12:18:00'),
    );

    $withImage = $article->withImage('https://static.tek.id/a.jpeg');

    expect($article->imageUrl)->toBeNull()
        ->and($withImage->imageUrl)->toBe('https://static.tek.id/a.jpeg')
        ->and($withImage->externalId)->toBe('b2qbp9uhh')
        ->and($withImage->channel)->toBe(TekChannel::Gadget);
});
