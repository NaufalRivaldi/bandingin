<?php

declare(strict_types=1);

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Models\Brand;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('creates a brand with its pros and cons', function (): void {
    Livewire::test(CreateBrand::class)
        ->fillForm([
            'name' => 'Vivo',
            'slug' => 'vivo',
            'blurb' => 'Merek dengan fokus kamera selfie.',
            'bestselling_range' => 'Rp3–5 jt',
            'os_update_note' => '3 tahun',
            'pros' => [['item' => 'Kamera depan tajam']],
            'cons' => [['item' => 'Harga jual kembali rendah']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $brand = Brand::where('slug', 'vivo')->sole();

    expect($brand->pros)->toBe(['Kamera depan tajam'])
        ->and($brand->cons)->toBe(['Harga jual kembali rendah']);
});
