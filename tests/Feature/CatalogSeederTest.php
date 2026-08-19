<?php

declare(strict_types=1);

use App\Models\SpecDefinition;
use App\Models\Store;
use Database\Seeders\SpecDefinitionSeeder;
use Database\Seeders\StoreSeeder;

it('seeds all nineteen spec definitions', function (): void {
    $this->seed(SpecDefinitionSeeder::class);

    expect(SpecDefinition::count())->toBe(19)
        ->and(SpecDefinition::where('key', 'berat')->value('label'))->toBe('Berat')
        ->and(SpecDefinition::where('key', 'osUpdate')->value('group'))->toBe('Bodi & Lainnya');
});

it('seeds spec definitions idempotently', function (): void {
    $this->seed(SpecDefinitionSeeder::class);
    $this->seed(SpecDefinitionSeeder::class);

    expect(SpecDefinition::count())->toBe(19);
});

it('seeds the four stores with the distributor marked unofficial', function (): void {
    $this->seed(StoreSeeder::class);

    expect(Store::count())->toBe(4)
        ->and(Store::where('name', 'Toko distributor')->sole()->is_official)->toBeFalse()
        ->and(Store::where('name', 'Shopee Mall')->sole()->is_official)->toBeTrue();
});

it('seeds stores idempotently', function (): void {
    $this->seed(StoreSeeder::class);
    $this->seed(StoreSeeder::class);

    expect(Store::count())->toBe(4);
});
