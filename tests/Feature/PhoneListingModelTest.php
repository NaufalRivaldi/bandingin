<?php

declare(strict_types=1);

use App\Models\Phone;
use App\Models\PhoneListing;
use App\Models\PriceSnapshot;
use App\Models\Store;
use Illuminate\Database\UniqueConstraintViolationException;

it('records a snapshot when a listing is created', function (): void {
    $listing = PhoneListing::factory()->create(['price' => 3_999_000]);

    expect($listing->snapshots)->toHaveCount(1)
        ->and($listing->snapshots->first()->price)->toBe(3_999_000);
});

it('records a new snapshot when the price changes', function (): void {
    $listing = PhoneListing::factory()->create(['price' => 3_999_000]);

    $listing->update(['price' => 3_799_000]);

    expect($listing->snapshots()->orderBy('id')->pluck('price')->all())
        ->toBe([3_999_000, 3_799_000]);
});

it('does not record a snapshot when a non-price field changes', function (): void {
    $listing = PhoneListing::factory()->create(['price' => 3_999_000]);

    $listing->update(['note' => 'Cicilan 0% 12 bulan']);

    expect($listing->snapshots()->count())->toBe(1);
});

it('rejects two listings for the same phone and store', function (): void {
    $phone = Phone::factory()->create();
    $store = Store::factory()->create();

    PhoneListing::factory()->create(['phone_id' => $phone->id, 'store_id' => $store->id]);

    expect(fn (): PhoneListing => PhoneListing::factory()->create([
        'phone_id' => $phone->id,
        'store_id' => $store->id,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('finds the cheapest listing for a phone', function (): void {
    $phone = Phone::factory()->create();
    PhoneListing::factory()->create(['phone_id' => $phone->id, 'price' => 5_499_000]);
    PhoneListing::factory()->create(['phone_id' => $phone->id, 'price' => 4_979_000]);

    expect($phone->listings()->min('price'))->toBe(4_979_000);
});

it('deletes snapshots when the listing is deleted', function (): void {
    $listing = PhoneListing::factory()->create();

    $listing->delete();

    expect(PriceSnapshot::count())->toBe(0);
});
