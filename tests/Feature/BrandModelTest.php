<?php

declare(strict_types=1);

use App\Models\Brand;
use Illuminate\Database\UniqueConstraintViolationException;

it('casts pros and cons to arrays', function (): void {
    $brand = Brand::factory()->create([
        'pros' => ['Layar cerah', 'Servis luas'],
        'cons' => ['Update singkat'],
    ]);

    expect($brand->fresh()->pros)->toBe(['Layar cerah', 'Servis luas'])
        ->and($brand->fresh()->cons)->toBe(['Update singkat']);
});

it('resolves route bindings by slug', function (): void {
    expect((new Brand)->getRouteKeyName())->toBe('slug');
});

it('rejects a duplicate slug', function (): void {
    Brand::factory()->create(['slug' => 'samsung']);

    expect(fn (): Brand => Brand::factory()->create(['slug' => 'samsung']))
        ->toThrow(UniqueConstraintViolationException::class);
});
