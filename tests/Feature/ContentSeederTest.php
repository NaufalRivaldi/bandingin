<?php

declare(strict_types=1);

use App\Enums\ArticleCategory;
use App\Enums\FaqTopic;
use App\Models\Article;
use App\Models\Faq;
use App\Models\FeaturedMatchup;
use App\Support\ArticleRepository;
use App\Support\FaqRepository;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\FaqSeeder;

it('seeds every article with a published date', function (): void {
    $this->seed(ArticleSeeder::class);

    expect(Article::count())->toBe(count(ArticleRepository::all()))
        ->and(Article::whereNull('published_at')->count())->toBe(0);
});

it('preserves the original publish dates', function (): void {
    $this->seed(ArticleSeeder::class);

    $article = Article::where('slug', 'galaxy-a57-bocor-layar-120-hz')->sole();

    expect($article->published_at->format('Y-m-d'))->toBe('2026-08-12');
});

it('folds the split prose into one html body', function (): void {
    $this->seed(ArticleSeeder::class);

    $article = Article::first();

    expect($article->body)->toContain('<p>')
        ->and($article->category)->toBeInstanceOf(ArticleCategory::class);
});

it('seeds articles idempotently', function (): void {
    $this->seed(ArticleSeeder::class);
    $this->seed(ArticleSeeder::class);

    expect(Article::count())->toBe(count(ArticleRepository::all()));
});

it('seeds every faq under a valid topic', function (): void {
    $this->seed(FaqSeeder::class);

    expect(Faq::count())->toBe(count(FaqRepository::all()))
        ->and(Faq::where('topic', FaqTopic::KartuDuel)->count())->toBe(3);
});

it('seeds the full database in dependency order', function (): void {
    $this->seed();

    expect(FeaturedMatchup::count())->toBe(4)
        ->and(FeaturedMatchup::with(['leftPhone', 'rightPhone'])->first()->label())
        ->toBe('Galaxy A56 5G vs Redmi Note 14 Pro 5G');
});
