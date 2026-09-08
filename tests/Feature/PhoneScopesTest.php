<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;

function phoneWithSpec(string $key, int $comparable, array $attributes = []): Phone
{
    $phone = Phone::factory()->create($attributes);
    $definition = SpecDefinition::firstOrCreate(
        ['key' => $key],
        ['label' => ucfirst($key), 'group' => 'Performa', 'sort' => 1],
    );

    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
        'display_value' => (string) $comparable,
        'comparable_value' => $comparable,
    ]);

    return $phone;
}

it('searches by phone name', function (): void {
    Phone::factory()->create(['name' => 'Galaxy A56 5G']);
    Phone::factory()->create(['name' => 'Redmi Note 14']);

    expect(Phone::search('galaxy')->pluck('name')->all())->toBe(['Galaxy A56 5G']);
});

it('searches by brand name', function (): void {
    $samsung = Brand::factory()->create(['name' => 'Samsung']);
    Phone::factory()->for($samsung)->create(['name' => 'Galaxy A56 5G']);
    Phone::factory()->create(['name' => 'Redmi Note 14']);

    expect(Phone::search('samsung')->pluck('name')->all())->toBe(['Galaxy A56 5G']);
});

it('ignores a blank search term', function (): void {
    Phone::factory()->count(3)->create();

    expect(Phone::search('')->count())->toBe(3);
});

it('filters by one price band', function (): void {
    Phone::factory()->create(['price' => 1_500_000]);
    Phone::factory()->create(['price' => 5_000_000]);

    expect(Phone::inPriceBands(['p1'])->pluck('price')->all())->toBe([1_500_000]);
});

it('treats several price bands as alternatives', function (): void {
    Phone::factory()->create(['price' => 1_500_000]);
    Phone::factory()->create(['price' => 5_000_000]);
    Phone::factory()->create(['price' => 9_000_000]);

    expect(Phone::inPriceBands(['p1', 'p4'])->count())->toBe(2);
});

it('filters by brand names', function (): void {
    $samsung = Brand::factory()->create(['name' => 'Samsung']);
    Phone::factory()->for($samsung)->create();
    Phone::factory()->create();

    expect(Phone::ofBrands(['Samsung'])->count())->toBe(1);
});

it('filters by a spec threshold', function (): void {
    phoneWithSpec('ram', 12, ['name' => 'Besar']);
    phoneWithSpec('ram', 8, ['name' => 'Kecil']);

    expect(Phone::matchingSpecFilters(['ram12'])->pluck('name')->all())->toBe(['Besar']);
});

it('combines several spec thresholds as AND', function (): void {
    $both = phoneWithSpec('ram', 12, ['name' => 'Dua-duanya']);
    $definition = SpecDefinition::firstOrCreate(
        ['key' => 'baterai'],
        ['label' => 'Kapasitas', 'group' => 'Baterai & Daya', 'sort' => 2],
    );
    PhoneSpec::factory()->create([
        'phone_id' => $both->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.500 mAh',
        'comparable_value' => 5500,
    ]);

    phoneWithSpec('ram', 12, ['name' => 'Cuma RAM']);

    expect(Phone::matchingSpecFilters(['ram12', 'bat5k'])->pluck('name')->all())
        ->toBe(['Dua-duanya']);
});

it('filters nfc and ip68 as boolean columns', function (): void {
    Phone::factory()->create(['nfc' => true, 'ip68' => true, 'name' => 'Lengkap']);
    Phone::factory()->create(['nfc' => false, 'ip68' => false, 'name' => 'Polos']);

    expect(Phone::matchingSpecFilters(['nfc', 'ip68'])->pluck('name')->all())->toBe(['Lengkap']);
});

it('sorts by price in both directions and by release date', function (): void {
    Phone::factory()->create(['price' => 5_000_000, 'released_at' => '2024-01-01', 'name' => 'Mahal lama']);
    Phone::factory()->create(['price' => 2_000_000, 'released_at' => '2026-01-01', 'name' => 'Murah baru']);

    expect(Phone::sortedBy('murah')->pluck('name')->all())->toBe(['Murah baru', 'Mahal lama'])
        ->and(Phone::sortedBy('mahal')->pluck('name')->all())->toBe(['Mahal lama', 'Murah baru'])
        ->and(Phone::sortedBy('populer')->pluck('name')->all())->toBe(['Murah baru', 'Mahal lama']);
});

it('chains scopes together', function (): void {
    $samsung = Brand::factory()->create(['name' => 'Samsung']);
    Phone::factory()->for($samsung)->create(['price' => 5_000_000, 'name' => 'Cocok']);
    Phone::factory()->for($samsung)->create(['price' => 9_000_000, 'name' => 'Kemahalan']);
    Phone::factory()->create(['price' => 5_000_000, 'name' => 'Merek lain']);

    expect(
        Phone::query()->ofBrands(['Samsung'])->inPriceBands(['p3'])->pluck('name')->all()
    )->toBe(['Cocok']);
});

it('treats the band upper bound as exclusive so bands never overlap', function (): void {
    Phone::factory()->create(['price' => 2_000_000, 'name' => 'Tepat di batas']);

    expect(Phone::inPriceBands(['p1'])->count())->toBe(0)
        ->and(Phone::inPriceBands(['p2'])->pluck('name')->all())->toBe(['Tepat di batas']);
});
