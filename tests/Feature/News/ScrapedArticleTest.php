<?php

declare(strict_types=1);

use App\News\ScrapedArticle;
use Carbon\CarbonImmutable;

it('returns a new instance when content is attached', function (): void {
    $article = new ScrapedArticle(
        externalId: 'b2qbp9uhh',
        title: 'realme 16 Pro Harry Potter Edition',
        excerpt: 'Meluncur di Indonesia.',
        url: 'https://www.tek.id/gadget/realme-b2qbp9uhh',
        publishedAt: CarbonImmutable::parse('2026-09-21 12:18:00'),
    );

    $filled = $article->withContent('<p>Isi artikel.</p>', 'https://static.tek.id/a.jpeg');

    expect($article->body)->toBeNull()
        ->and($article->imageUrl)->toBeNull()
        ->and($filled->body)->toBe('<p>Isi artikel.</p>')
        ->and($filled->imageUrl)->toBe('https://static.tek.id/a.jpeg')
        ->and($filled->externalId)->toBe('b2qbp9uhh')
        ->and($filled->title)->toBe('realme 16 Pro Harry Potter Edition');
});
