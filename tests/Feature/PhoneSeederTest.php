<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Phone;
use App\Models\PhoneListing;
use App\Support\PhoneCatalog;
use App\Support\SellerPricing;
use Database\Seeders\BrandHighlightSeeder;
use Database\Seeders\BrandSeeder;
use Database\Seeders\PhoneSeeder;
use Database\Seeders\SpecDefinitionSeeder;
use Database\Seeders\StoreSeeder;

beforeEach(function (): void {
    $this->seed(SpecDefinitionSeeder::class);
    $this->seed(StoreSeeder::class);
    $this->seed(BrandSeeder::class);
    $this->seed(PhoneSeeder::class);
});

it('seeds the six brands with their pros and cons', function (): void {
    expect(Brand::count())->toBe(6)
        ->and(Brand::where('name', 'Samsung')->value('os_update_note'))->toBe('6–7 tahun')
        ->and(Brand::where('name', 'Xiaomi')->first()->pros)->toHaveCount(3);
});

it('seeds every catalogue phone with its slug and price intact', function (): void {
    expect(Phone::whereIn('slug', ['a56', 'note14pro', 'x7pro', 'r13pp', 'ip16', 's25'])->count())->toBe(6)
        ->and(Phone::where('slug', 'a56')->value('price'))->toBe(5_499_000)
        ->and(Phone::where('slug', 'note14pro')->value('tag'))->toBe('Terlaris');
});

it('gives every catalogue phone all nineteen specs', function (): void {
    $phone = Phone::where('slug', 'a56')->sole();

    expect($phone->specs()->count())->toBe(19);
});

it('ports spec display and comparable values verbatim', function (): void {
    $phone = Phone::where('slug', 'a56')->with('specs.definition')->sole();
    $berat = $phone->specs->first(fn ($spec): bool => $spec->definition->key === 'berat');
    $baterai = $phone->specs->first(fn ($spec): bool => $spec->definition->key === 'baterai');

    expect($berat->display_value)->toBe('198 g')
        ->and($berat->comparable_value)->toBe(-198)
        ->and($baterai->display_value)->toBe('5.000 mAh')
        ->and($baterai->comparable_value)->toBe(5000);
});

it('links each phone to its brand', function (): void {
    expect(Phone::where('slug', 'a56')->sole()->brand->name)->toBe('Samsung');
});

it('gives every catalogue phone four listings matching the old offsets', function (): void {
    $phone = Phone::where('slug', 'a56')->sole();
    $expected = SellerPricing::detailed(5_499_000);

    expect($phone->listings()->count())->toBe(4)
        ->and($phone->listings()->min('price'))->toBe(SellerPricing::lowestPrice(5_499_000));

    $distributor = $phone->listings()->whereRelation('store', 'name', 'Toko distributor')->sole();

    expect($distributor->warranty_tag)->toBe('Non-resmi')
        ->and($expected)->toHaveCount(4);
});

it('gives every catalogue phone three variants with one default', function (): void {
    $phone = Phone::where('slug', 'a56')->sole();

    expect($phone->variants)->toHaveCount(3)
        ->and($phone->defaultVariant()?->label)->toBe('8/256 GB')
        ->and($phone->defaultVariant()?->price)->toBe(5_499_000);
});

it('parses the release label into a sortable date', function (): void {
    expect(Phone::where('slug', 'a56')->sole()->released_at->format('Y-m'))->toBe('2025-03');
});

it('records a price snapshot for every seeded listing', function (): void {
    expect(PhoneListing::count())->toBe(count(PhoneCatalog::all()) * 4);

    $listing = PhoneListing::with('snapshots')->first();

    expect($listing->snapshots)->toHaveCount(1);
});

it('runs the phone seeder idempotently', function (): void {
    $this->seed(PhoneSeeder::class);

    expect(Phone::count())->toBe(count(PhoneCatalog::all()))
        ->and(PhoneListing::count())->toBe(count(PhoneCatalog::all()) * 4);
});

it('gives every brand four highlighted phones', function (): void {
    $this->seed(BrandHighlightSeeder::class);

    Brand::each(function (Brand $brand): void {
        expect($brand->highlightedPhones)->toHaveCount(4);
    });
});

it('creates phones for highlight models missing from the catalogue', function (): void {
    $this->seed(BrandHighlightSeeder::class);

    $phone = Phone::where('name', 'Redmi 14C')->sole();

    expect($phone->price)->toBe(1_399_000)
        ->and($phone->published_at)->not->toBeNull();
});
