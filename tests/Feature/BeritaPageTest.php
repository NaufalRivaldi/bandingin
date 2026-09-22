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

it('shows only the first page of articles', function (): void {
    Article::factory()->count(30)->create();

    $component = Livewire::test(BeritaPage::class);

    expect($component->instance()->articles())->toHaveCount(BeritaPage::PER_PAGE);
});

it('loads another page when asked for more', function (): void {
    Article::factory()->count(30)->create();

    $component = Livewire::test(BeritaPage::class)->call('loadMore');

    expect($component->instance()->articles())->toHaveCount(BeritaPage::PER_PAGE * 2);
});

it('never returns more articles than exist', function (): void {
    Article::query()->delete();
    Article::factory()->count(4)->create();

    $component = Livewire::test(BeritaPage::class)->call('loadMore')->call('loadMore');

    // Four articles, one of which is the featured hero.
    expect($component->instance()->articles())->toHaveCount(3);
});

it('hides the load-more button once everything is shown', function (): void {
    Article::query()->delete();
    Article::factory()->count(3)->create();

    Livewire::test(BeritaPage::class)->assertDontSee('Muat lebih banyak');
});

it('shows the load-more button while articles remain', function (): void {
    Article::factory()->count(30)->create();

    Livewire::test(BeritaPage::class)->assertSee('Muat lebih banyak');
});

it('reports the total, not just the page currently shown', function (): void {
    Article::query()->delete();
    Article::factory()->count(21)->create();

    // Twenty-one articles minus the featured hero. The tally answers "how big
    // is this list", which is why it does not shrink to the page size.
    Livewire::test(BeritaPage::class)->assertSee('20 artikel');
});

it('starts again from the first page when the category changes', function (): void {
    Article::factory()->count(30)->create();

    $component = Livewire::test(BeritaPage::class)
        ->call('loadMore')
        ->call('setCategory', 'Panduan')
        ->call('setCategory', BeritaPage::ALL_CATEGORIES);

    expect($component->instance()->articles())->toHaveCount(BeritaPage::PER_PAGE);
});

it('does not query every article just to count them', function (): void {
    Article::factory()->count(30)->create();

    $component = Livewire::test(BeritaPage::class);

    // The grid must hold one page, not the whole table, however big it gets.
    expect($component->instance()->articles())->toHaveCount(BeritaPage::PER_PAGE)
        ->and($component->instance()->total())->toBe(30 + 12 - 1);
});
