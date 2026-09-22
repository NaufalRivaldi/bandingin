<?php

declare(strict_types=1);

use App\Livewire\BeritaDetailPage;
use App\Livewire\BeritaPage;
use App\Models\Article;
use Livewire\Livewire;

beforeEach(function (): void {
    seedCatalog();
});

it('lists published articles and features the newest', function (): void {
    $newest = Article::published()->latest('published_at')->first();

    Livewire::test(BeritaPage::class)
        ->assertSee($newest->title)
        ->assertSet('category', BeritaPage::ALL_CATEGORIES);
});

it('hides unpublished articles', function (): void {
    $draft = Article::factory()->draft()->create(['title' => 'Draf rahasia']);

    Livewire::test(BeritaPage::class)->assertDontSee($draft->title);
});

it('changes the list heading when a category is picked', function (): void {
    // showsFeatured is a computed method, so it is read through the instance.
    $component = Livewire::test(BeritaPage::class)->call('setCategory', 'Panduan')->instance();

    expect($component->listHeading())->toBe('Panduan')
        ->and($component->showsFeatured())->toBeFalse();
});

it('opens an article and renders its body', function (): void {
    $article = Article::published()->first();

    $this->get(route('berita.show', $article->slug))
        ->assertSuccessful()
        ->assertSee($article->title)
        ->assertSee('article-prose');
});

it('returns 404 for an unknown article', function (): void {
    $this->get(route('berita.show', 'tidak-ada'))->assertNotFound();
});

it('returns 404 for a draft article', function (): void {
    $draft = Article::factory()->draft()->create();

    $this->get(route('berita.show', $draft->slug))->assertNotFound();
});

it('prefers same-category articles in the related rail', function (): void {
    $article = Article::published()->first();

    $related = Livewire::test(BeritaDetailPage::class, ['slug' => $article->slug])
        ->instance()
        ->related();

    expect($related)->not->toBeEmpty()
        ->and($related->pluck('id')->all())->not->toContain($article->id)
        ->and($related->first()->category)->toBe($article->category);
});
