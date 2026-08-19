<?php

declare(strict_types=1);

use App\Livewire\PerbandinganPage;
use Livewire\Livewire;

it('compares the pair named in the query string', function (): void {
    $this->get('/bandingkan?left=x7pro&right=ip16')
        ->assertOk()
        ->assertSee('POCO X7 Pro vs iPhone 16');
});

it('falls back to the default pair for an unknown id', function (): void {
    $this->get('/bandingkan?left=tidak-ada&right=note14pro')
        ->assertOk()
        ->assertSee('Galaxy A56 5G vs Redmi Note 14 Pro 5G');
});

it('swaps the two sides', function (): void {
    Livewire::test(PerbandinganPage::class)
        ->assertSet('left', 'a56')
        ->call('swap')
        ->assertSet('left', 'note14pro')
        ->assertSet('right', 'a56')
        ->assertSee('Redmi Note 14 Pro 5G vs Galaxy A56 5G');
});

it('replaces one side from the picker', function (): void {
    Livewire::test(PerbandinganPage::class)
        ->call('openPicker', 'right')
        ->assertSet('open', 'right')
        ->call('pick', 'right', 's25')
        ->assertSet('right', 's25')
        ->assertSet('open', null)
        ->assertSee('Galaxy A56 5G vs Galaxy S25');
});

it('hides tied rows behind the differences toggle', function (): void {
    Livewire::test(PerbandinganPage::class)
        ->call('setMatchup', 'a56', 'note14pro')
        ->assertSee('Refresh Rate')
        ->call('toggleOnlyDifferences')
        ->assertSet('onlyDifferences', true)
        // Both run 120 Hz, so that row goes; chipset differs and stays.
        ->assertDontSee('Refresh Rate')
        ->assertSee('Chipset');
});

it('states a verdict and lists reasons for each side', function (): void {
    Livewire::test(PerbandinganPage::class)
        ->call('setMatchup', 'ip16', 'note14pro')
        ->assertSee('unggul di')
        ->assertSee('Pilih iPhone 16 kalau')
        ->assertSee('Pilih Redmi Note 14 Pro 5G kalau');
});

it('lists sellers for both phones', function (): void {
    Livewire::test(PerbandinganPage::class)
        ->assertSee('Beli di mana paling murah')
        ->assertSee('TikTok Shop')
        ->assertSee('Distributor');
});
