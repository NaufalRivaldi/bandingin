<?php

declare(strict_types=1);

use App\Livewire\HomePage;
use Livewire\Livewire;

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

it('filters picker suggestions by the typed query', function (): void {
    // Both strings below appear only inside the suggestion dropdown, so they isolate it
    // from the matchup shortcuts and budget cards that also name phones.
    Livewire::test(HomePage::class)
        ->call('openPicker', 'left')
        ->assertSet('open', 'left')
        ->assertSee('realme 13 Pro+ 5G')
        ->set('leftQuery', 'poco')
        ->assertSee('POCO M7 Pro 5G')
        ->assertDontSee('realme 13 Pro+ 5G');
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
