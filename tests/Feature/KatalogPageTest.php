<?php

declare(strict_types=1);

use App\Livewire\KatalogPage;
use Livewire\Livewire;

beforeEach(function (): void {
    seedCatalog();
});

it('lists the whole catalogue by default', function (): void {
    Livewire::test(KatalogPage::class)
        ->assertSee('12 HP ditemukan dari 12 total')
        ->assertSee('Redmi Note 14 Pro 5G');
});

it('narrows results by search term', function (): void {
    Livewire::test(KatalogPage::class)
        ->set('query', 'poco')
        ->assertSee('2 HP ditemukan dari 12 total')
        ->assertSee('POCO X7 Pro')
        ->assertDontSee('iPhone 16');
});

it('shows the empty state when nothing matches', function (): void {
    Livewire::test(KatalogPage::class)
        ->set('query', 'tidak ada hp begini')
        ->assertSee('Tidak ada HP yang cocok')
        ->assertSee('Reset semua filter');
});

it('filters by brand and by price band together', function (): void {
    Livewire::test(KatalogPage::class)
        ->call('toggleBrand', 'Samsung')
        ->assertSee('Galaxy A56 5G')
        ->assertDontSee('POCO X7 Pro')
        // Samsung phones above Rp7 juta leaves only the S25.
        ->call('togglePrice', 'p4')
        ->assertSee('Galaxy S25')
        ->assertDontSee('Galaxy A16 5G');
});

it('applies minimum-spec filters', function (): void {
    Livewire::test(KatalogPage::class)
        ->call('toggleSpec', 'ip68')
        ->assertSee('POCO X7 Pro')
        ->assertSee('realme C75')
        ->assertDontSee('Redmi Note 14 Pro 5G');
});

it('sorts by price in both directions', function (): void {
    $cheapestFirst = Livewire::test(KatalogPage::class)->call('setSort', 'murah')->html();
    $dearestFirst = Livewire::test(KatalogPage::class)->call('setSort', 'mahal')->html();

    expect(strpos($cheapestFirst, 'Infinix Hot 50 Pro+'))->toBeLessThan(strpos($cheapestFirst, 'iPhone 16'))
        ->and(strpos($dearestFirst, 'iPhone 16'))->toBeLessThan(strpos($dearestFirst, 'Infinix Hot 50 Pro+'));
});

it('keeps only the two most recent phones picked for a duel', function (): void {
    Livewire::test(KatalogPage::class)
        ->call('toggleCompare', 'a56')
        ->call('toggleCompare', 's25')
        ->assertSet('picked', ['a56', 's25'])
        // A third pick drops the oldest.
        ->call('toggleCompare', 'ip16')
        ->assertSet('picked', ['s25', 'ip16']);
});

it('prompts for a second phone and then links to the duel', function (): void {
    Livewire::test(KatalogPage::class)
        ->call('toggleCompare', 'a56')
        ->assertSee('pilih satu lagi')
        ->call('toggleCompare', 'note14pro')
        ->assertSee('left=a56&amp;right=note14pro', escape: false);
});

it('clears the duel selection', function (): void {
    Livewire::test(KatalogPage::class)
        ->call('toggleCompare', 'a56')
        ->call('clearSelection')
        ->assertSet('picked', []);
});

it('resets every filter at once', function (): void {
    Livewire::test(KatalogPage::class)
        ->set('query', 'poco')
        ->call('toggleBrand', 'POCO')
        ->call('toggleSpec', 'ip68')
        ->call('setSort', 'mahal')
        ->call('resetFilters')
        ->assertSet('query', '')
        ->assertSet('brands', [])
        ->assertSet('specs', [])
        ->assertSet('sort', 'populer')
        ->assertSee('12 HP ditemukan dari 12 total');
});

it('accepts a search term handed over in the query string', function (): void {
    // The 404 page's search form submits ?q= here.
    $this->get('/katalog?q=poco')
        ->assertOk()
        ->assertSee('2 HP ditemukan dari 12 total');
});
