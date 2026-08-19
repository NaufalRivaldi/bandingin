<?php

declare(strict_types=1);

use App\Enums\ArticleCategory;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Models\Article;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('creates an article with html body and tags', function (): void {
    Livewire::test(CreateArticle::class)
        ->fillForm([
            'slug' => 'panduan-beli-hp-2026',
            'title' => 'Panduan beli HP 2026',
            'category' => ArticleCategory::Panduan->value,
            'excerpt' => 'Cara memilih HP tanpa menyesal.',
            'body' => '<p>Paragraf pertama.</p>',
            'tags' => [['item' => 'Panduan']],
            'read_minutes' => 6,
            'published_at' => now(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::where('slug', 'panduan-beli-hp-2026')->sole();

    expect($article->category)->toBe(ArticleCategory::Panduan)
        ->and($article->tags)->toBe(['Panduan'])
        ->and($article->body)->toContain('Paragraf pertama');
});
