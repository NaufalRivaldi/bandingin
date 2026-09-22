<?php

declare(strict_types=1);

use App\Models\Article;
use Illuminate\Database\UniqueConstraintViolationException;

it('treats an article with no source as our own', function (): void {
    $article = Article::factory()->create();

    expect($article->source)->toBeNull()
        ->and($article->isScraped())->toBeFalse();
});

it('treats an article with a source as scraped', function (): void {
    $article = Article::factory()->scraped()->create();

    expect($article->source)->toBe('tek.id')
        ->and($article->isScraped())->toBeTrue()
        ->and($article->source_url)->toStartWith('https://www.tek.id/');
});

it('scopes to scraped and to our own articles', function (): void {
    Article::factory()->count(2)->create();
    Article::factory()->scraped()->count(3)->create();

    expect(Article::own()->count())->toBe(2)
        ->and(Article::scraped()->count())->toBe(3);
});

it('rejects a duplicate external id', function (): void {
    Article::factory()->scraped()->create(['external_id' => 'b2qbp9uhh']);

    expect(fn () => Article::factory()->scraped()->create(['external_id' => 'b2qbp9uhh']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('allows many of our own articles to share a null external id', function (): void {
    Article::factory()->count(3)->create();

    expect(Article::own()->count())->toBe(3);
});

it('stores a hero image url', function (): void {
    $article = Article::factory()->scraped()->create(['image_url' => 'https://static.tek.id/a.jpeg']);

    expect($article->fresh()->image_url)->toBe('https://static.tek.id/a.jpeg');
});
