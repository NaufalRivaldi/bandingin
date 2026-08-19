<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Phone;

it('belongs to a brand', function (): void {
    $brand = Brand::factory()->create(['name' => 'Samsung']);
    $phone = Phone::factory()->for($brand)->create();

    expect($phone->brand->name)->toBe('Samsung');
});

it('casts flags, dates and lists', function (): void {
    $phone = Phone::factory()->create([
        'nfc' => true,
        'ip68' => false,
        'released_at' => '2025-03-01',
        'pros' => ['Baterai awet'],
        'cons' => ['Cas lambat'],
    ])->fresh();

    expect($phone->nfc)->toBeTrue()
        ->and($phone->ip68)->toBeFalse()
        ->and($phone->released_at->format('Y-m-d'))->toBe('2025-03-01')
        ->and($phone->pros)->toBe(['Baterai awet'])
        ->and($phone->cons)->toBe(['Cas lambat']);
});

it('scopes to published phones only', function (): void {
    Phone::factory()->create(['slug' => 'tayang']);
    Phone::factory()->unpublished()->create(['slug' => 'draf']);

    expect(Phone::published()->pluck('slug')->all())->toBe(['tayang']);
});

it('resolves route bindings by slug', function (): void {
    expect((new Phone)->getRouteKeyName())->toBe('slug');
});

it('exposes a brand to its phones', function (): void {
    $brand = Brand::factory()->create();
    Phone::factory()->count(2)->for($brand)->create();

    expect($brand->phones)->toHaveCount(2);
});
