<?php

declare(strict_types=1);

use App\Enums\ArticleCategory;
use App\Models\Article;

it('casts the category to an enum', function (): void {
    $article = Article::factory()->create(['category' => ArticleCategory::Panduan]);

    expect($article->fresh()->category)->toBe(ArticleCategory::Panduan);
});

it('casts tags to an array', function (): void {
    $article = Article::factory()->create(['tags' => ['Xiaomi', 'Harga']]);

    expect($article->fresh()->tags)->toBe(['Xiaomi', 'Harga']);
});

it('scopes to published articles only', function (): void {
    Article::factory()->create(['slug' => 'tayang']);
    Article::factory()->draft()->create(['slug' => 'draf']);

    expect(Article::published()->pluck('slug')->all())->toBe(['tayang']);
});

it('resolves route bindings by slug', function (): void {
    expect((new Article)->getRouteKeyName())->toBe('slug');
});

it('lists the four frontend categories', function (): void {
    expect(ArticleCategory::values())->toBe(['Rilis', 'Harga', 'Panduan', 'Ulasan']);
});
