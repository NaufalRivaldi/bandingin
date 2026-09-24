<?php

declare(strict_types=1);

use App\Livewire\HomePage;
use App\Models\Article;
use Livewire\Livewire;

beforeEach(function (): void {
    seedCatalog();
});

it('renders the hero and the default duel', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Jangan Beli HP')
        ->assertSee('Kartu Duel')
        ->assertSee('Galaxy A56 5G')
        ->assertSee('Redmi Note 14 Pro 5G')
        ->assertSee('Cari Berdasarkan Budget');
});

it('swaps both phones when a popular matchup is picked', function (): void {
    Livewire::test(HomePage::class)
        ->call('setMatchup', 'ip16', 's25')
        ->assertSet('left', 'ip16')
        ->assertSet('right', 's25')
        ->assertSee('iPhone 16')
        ->assertSee('Galaxy S25');
});

it('carries the chosen pair into the compare page link', function (): void {
    $target = route('bandingkan', ['left' => 'ip16', 'right' => 's25']);

    Livewire::test(HomePage::class)
        ->call('setMatchup', 'ip16', 's25')
        ->assertSee($target);
});

it('points the compare link at the phones actually on screen when a slug is unknown', function (): void {
    // resolve() falls back to the first published phone, so the link must follow
    // what the duel card shows rather than the bogus slug.
    $page = Livewire::test(HomePage::class)->set('left', 'tidak-ada');

    $shown = $page->instance()->leftPhone()->slug;

    $page->assertSee(route('bandingkan', ['left' => $shown, 'right' => 'note14pro']))
        ->assertDontSee('left=tidak-ada', escape: false);
});

it('filters picker suggestions by the typed query', function (): void {
    // Asserted against the suggestion collection rather than rendered copy: matchup
    // shortcut labels are derived from full phone names, so they also mention phones.
    $component = Livewire::test(HomePage::class)
        ->call('openPicker', 'left')
        ->assertSet('open', 'left');

    expect($component->instance()->leftSuggestions()->pluck('name'))
        ->toContain('realme 13 Pro+ 5G');

    $filtered = $component->set('leftQuery', 'poco')->instance()->leftSuggestions()->pluck('name');

    expect($filtered)->toContain('POCO M7 Pro 5G')
        ->and($filtered)->not->toContain('realme 13 Pro+ 5G');
});

it('selects a phone from the picker and closes the dropdown', function (): void {
    Livewire::test(HomePage::class)
        ->call('openPicker', 'left')
        ->call('pick', 'left', 'x7pro')
        ->assertSet('left', 'x7pro')
        ->assertSet('open', null)
        // A closed picker shows the chosen phone rather than the search term.
        ->assertSet('leftQuery', 'POCO X7 Pro');
});

it('hides tied rows when the differences toggle is on', function (): void {
    Livewire::test(HomePage::class)
        ->call('setMatchup', 'a56', 'note14pro')
        ->assertSee('IP Rating')
        ->call('toggleOnlyDifferences')
        ->assertSet('onlyDifferences', true)
        // Both phones are IP67, so that row disappears while Chipset stays.
        ->assertDontSee('IP Rating')
        ->assertSee('Chipset');
});

it('writes the news heading without an html entity', function (): void {
    // The section-heading component escapes {{ $title }}, so passing "&amp;"
    // rendered the entity literally on the page.
    $this->get(route('home'))
        ->assertSee('Berita dan Ulasan')
        ->assertDontSee('&amp;amp;', escape: false)
        ->assertDontSee('Berita &amp; Ulasan', escape: false);
});

it('shows the featured article image in the news section', function (): void {
    Article::query()->delete();

    Article::factory()->create([
        'title' => 'Artikel utama',
        'image_url' => 'https://static.tek.id/utama.jpeg',
        'published_at' => now(),
    ]);

    $this->get(route('home'))->assertSee('https://static.tek.id/utama.jpeg');
});

it('shows thumbnails for the latest article list', function (): void {
    Article::query()->delete();

    Article::factory()->create([
        'image_url' => 'https://static.tek.id/utama.jpeg',
        'published_at' => now(),
    ]);
    Article::factory()->create([
        'image_url' => 'https://static.tek.id/kecil.jpeg',
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('home'))->assertSee('https://static.tek.id/kecil.jpeg');
});

it('falls back to the placeholder when an article has no image', function (): void {
    Article::query()->delete();

    Article::factory()->create([
        'image_url' => null,
        'published_at' => now(),
    ]);

    $this->get(route('home'))->assertSee('photo-slot');
});

it('sends the hero call to action to the comparison page', function (): void {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('href="'.route('bandingkan').'"', escape: false)
        ->assertSee('Bandingkan Sekarang');
});
