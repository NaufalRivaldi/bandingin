<?php

declare(strict_types=1);

use App\Livewire\MerekPage;
use App\Models\Brand;
use Livewire\Livewire;

beforeEach(function (): void {
    seedCatalog();
});

it('shows the active brand and its blurb', function (): void {
    Livewire::test(MerekPage::class)
        ->assertSee('Xiaomi')
        ->assertSee(Brand::where('name', 'Xiaomi')->value('blurb'));
});

it('computes model count and lowest price from real phones', function (): void {
    $brand = Brand::where('name', 'Samsung')->withCount('phones')->sole();

    $stats = Livewire::test(MerekPage::class)
        ->call('selectBrand', 'Samsung')
        ->instance()
        ->stats();

    expect($stats['Model aktif'])->toBe((string) $brand->phones_count)
        ->and($stats)->toHaveKeys(['Model aktif', 'Harga terendah', 'Rentang terlaris', 'Update OS']);
});

it('reads the stored editorial stats', function (): void {
    $stats = Livewire::test(MerekPage::class)
        ->call('selectBrand', 'Samsung')
        ->instance()
        ->stats();

    expect($stats['Update OS'])->toBe(Brand::where('name', 'Samsung')->value('os_update_note'));
});

it('lists four highlighted models for the active brand', function (): void {
    $highlights = Livewire::test(MerekPage::class)
        ->instance()
        ->activeBrand()
        ->highlightedPhones;

    expect($highlights)->toHaveCount(4);
});

it('falls back to the first brand for an unknown name', function (): void {
    Livewire::test(MerekPage::class, ['brand' => 'TidakAda'])
        ->assertSuccessful();
});
