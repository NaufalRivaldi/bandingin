<?php

declare(strict_types=1);

use App\Enums\ArticleCategory;
use App\Models\Article;

it('formats its publish date the way the design prints it', function (): void {
    $article = Article::factory()->create([
        'published_at' => '2026-08-12 08:00:00',
        'category' => ArticleCategory::Rilis,
    ]);

    expect($article->dateShort())->toBe('12 Agu 2026')
        ->and($article->dateLong())->toBe('12 Agustus 2026')
        ->and($article->meta())->toBe('Rilis · 12 Agu 2026');
});

it('handles every month name', function (): void {
    $article = Article::factory()->create(['published_at' => '2026-01-05 08:00:00']);

    expect($article->dateLong())->toBe('5 Januari 2026');
});

it('falls back to an empty date when unpublished', function (): void {
    $article = Article::factory()->draft()->create();

    expect($article->dateShort())->toBe('')
        ->and($article->dateLong())->toBe('');
});
