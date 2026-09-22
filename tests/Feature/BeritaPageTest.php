<?php

declare(strict_types=1);

use App\Livewire\BeritaDetailPage;
use App\Livewire\BeritaPage;
use App\Models\Article;
use App\Models\NewsItem;
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

it('shows aggregated news in its own section, not the article grid', function (): void {
    $item = NewsItem::factory()->create([
        'title' => 'Kabar dari luar',
        'url' => 'https://www.tek.id/gadget/kabar-b2qbp9uhh',
    ]);

    $response = $this->get(route('berita'));

    $response->assertSuccessful()
        ->assertSee('Kabar Teknologi')
        ->assertSee($item->url);

    // The item must not have acquired a page on this site.
    $this->get('/berita/kabar-b2qbp9uhh')->assertNotFound();
});

it('places aggregated news above the article grid', function (): void {
    NewsItem::factory()->create();

    $html = Livewire::test(BeritaPage::class)->html();

    expect(strpos($html, 'Kabar Teknologi'))->toBeLessThan(strpos($html, 'Terbaru'));
});

it('hides aggregated news when a category filter is active', function (): void {
    // The chips scope the page to our own taxonomy, so external cards must not
    // push a filtered article list down. Same rule as the featured hero.
    NewsItem::factory()->create(['title' => 'Kabar dari luar']);

    Livewire::test(BeritaPage::class)
        ->assertSee('Kabar Teknologi')
        ->call('setCategory', 'Panduan')
        ->assertDontSee('Kabar Teknologi')
        ->assertDontSee('Kabar dari luar');
});

it('keeps aggregated news out of the article count', function (): void {
    // The grid's "N artikel" tally counts Articles only. Asserting it is
    // unchanged by news items avoids restating BeritaPage's own rule that the
    // featured article is dropped from the grid.
    $countBefore = articleTallyOnBeritaPage();

    NewsItem::factory()->count(5)->create();

    expect(articleTallyOnBeritaPage())->toBe($countBefore);
});

/** The "N artikel" number rendered above the article grid. */
function articleTallyOnBeritaPage(): int
{
    $html = Livewire::test(BeritaPage::class)->html();

    expect($html)->toMatch('/(\d+) artikel/');

    preg_match('/(\d+) artikel/', $html, $matches);

    return (int) $matches[1];
}
