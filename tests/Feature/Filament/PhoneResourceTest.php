<?php

declare(strict_types=1);

use App\Filament\Resources\Phones\Pages\CreatePhone;
use App\Models\Brand;
use App\Models\Phone;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('creates a phone through the form', function (): void {
    $brand = Brand::factory()->create();

    Livewire::test(CreatePhone::class)
        ->fillForm([
            'brand_id' => $brand->id,
            'slug' => 'a57',
            'name' => 'Galaxy A57 5G',
            'price' => 5_999_000,
            'nfc' => true,
            'ip68' => false,
            'dimensions' => '162,0 x 77,0 x 7,4 mm',
            'network' => '5G Dual SIM · Wi-Fi 6 · NFC',
            'released_label' => 'Maret 2026',
            'released_at' => '2026-03-01',
            'pros' => [['item' => 'Layar cerah']],
            'cons' => [['item' => 'Cas lambat']],
            'score_performa' => 75,
            'score_layar' => 82,
            'score_kamera' => 79,
            'score_baterai' => 76,
            'score_nilai' => 71,
            'published_at' => now(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Phone::where('slug', 'a57')->sole()->name)->toBe('Galaxy A57 5G');
});

it('rejects a phone without a brand', function (): void {
    Livewire::test(CreatePhone::class)
        ->fillForm(['slug' => 'tanpa-merek', 'name' => 'Tanpa Merek'])
        ->call('create')
        ->assertHasFormErrors(['brand_id']);
});
