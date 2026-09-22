<?php

declare(strict_types=1);

use App\Actions\Phones\SyncPhoneRelations;
use App\Models\Phone;
use App\Models\PhoneListing;
use App\Models\PhoneSpec;
use App\Models\PhoneVariant;
use App\Models\SpecDefinition;
use App\Models\Store;

it('leaves every relation untouched when nothing is passed', function (): void {
    $phone = Phone::factory()->create();
    PhoneVariant::factory()->create(['phone_id' => $phone->id]);
    PhoneListing::factory()->create(['phone_id' => $phone->id]);
    PhoneSpec::factory()->create(['phone_id' => $phone->id]);

    app(SyncPhoneRelations::class)->handle($phone, null, null, null);

    expect($phone->refresh()->variants)->toHaveCount(1)
        ->and($phone->listings)->toHaveCount(1)
        ->and($phone->specs)->toHaveCount(1);
});

it('empties a relation when an empty list is passed', function (): void {
    $phone = Phone::factory()->create();
    PhoneVariant::factory()->create(['phone_id' => $phone->id]);

    app(SyncPhoneRelations::class)->handle($phone, null, [], null);

    expect($phone->refresh()->variants)->toHaveCount(0);
});

it('updates a spec in place rather than duplicating it', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create(['key' => 'baterai']);

    $specs = [(string) $definition->id => ['display' => '5.000 mAh', 'comparable' => 5000]];
    app(SyncPhoneRelations::class)->handle($phone, $specs, null, null);

    $specs[(string) $definition->id] = ['display' => '5.500 mAh', 'comparable' => 5500];
    app(SyncPhoneRelations::class)->handle($phone, $specs, null, null);

    expect($phone->refresh()->specs)->toHaveCount(1)
        ->and($phone->specs->first()->comparable_value)->toBe(5500);
});

it('keeps price history when a listing price changes and cascades it on delete', function (): void {
    $phone = Phone::factory()->create();
    $store = Store::factory()->create();

    $listing = ['store_id' => $store->id, 'price' => 5299000, 'product_url' => null, 'warranty_tag' => null, 'note' => null];
    app(SyncPhoneRelations::class)->handle($phone, null, null, [$listing]);

    app(SyncPhoneRelations::class)->handle($phone, null, null, [[...$listing, 'price' => 5199000]]);

    expect($phone->refresh()->listings->first()->snapshots)->toHaveCount(2);

    app(SyncPhoneRelations::class)->handle($phone, null, null, []);

    expect($phone->refresh()->listings)->toHaveCount(0)
        ->and(DB::table('price_snapshots')->count())->toBe(0);
});
