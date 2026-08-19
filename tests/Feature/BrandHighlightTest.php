<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Phone;
use Illuminate\Database\UniqueConstraintViolationException;

it('returns highlighted phones in sort order', function (): void {
    $brand = Brand::factory()->create();
    $first = Phone::factory()->create(['name' => 'Redmi Note 14 Pro 5G']);
    $second = Phone::factory()->create(['name' => 'Redmi Note 14']);

    $brand->highlightedPhones()->attach($second, ['sort' => 2]);
    $brand->highlightedPhones()->attach($first, ['sort' => 1]);

    expect($brand->highlightedPhones->pluck('name')->all())
        ->toBe(['Redmi Note 14 Pro 5G', 'Redmi Note 14']);
});

it('rejects highlighting the same phone twice for one brand', function (): void {
    $brand = Brand::factory()->create();
    $phone = Phone::factory()->create();

    $brand->highlightedPhones()->attach($phone, ['sort' => 1]);

    expect(fn () => $brand->highlightedPhones()->attach($phone, ['sort' => 2]))
        ->toThrow(UniqueConstraintViolationException::class);
});
