<?php

declare(strict_types=1);

use App\Models\Phone;
use App\Models\PhoneVariant;

it('returns variants in sort order', function (): void {
    $phone = Phone::factory()->create();
    PhoneVariant::factory()->create(['phone_id' => $phone->id, 'label' => '12/512 GB', 'sort' => 3]);
    PhoneVariant::factory()->create(['phone_id' => $phone->id, 'label' => '8/256 GB', 'sort' => 1]);
    PhoneVariant::factory()->create(['phone_id' => $phone->id, 'label' => '12/256 GB', 'sort' => 2]);

    expect($phone->variants->pluck('label')->all())
        ->toBe(['8/256 GB', '12/256 GB', '12/512 GB']);
});

it('exposes the default variant', function (): void {
    $phone = Phone::factory()->create();
    PhoneVariant::factory()->create(['phone_id' => $phone->id, 'label' => '12/256 GB', 'is_default' => false, 'sort' => 2]);
    PhoneVariant::factory()->create(['phone_id' => $phone->id, 'label' => '8/256 GB', 'is_default' => true, 'sort' => 1]);

    expect($phone->defaultVariant()?->label)->toBe('8/256 GB');
});

it('returns null when a phone has no variants', function (): void {
    expect(Phone::factory()->create()->defaultVariant())->toBeNull();
});

it('deletes variants when the phone is deleted', function (): void {
    $phone = Phone::factory()->create();
    PhoneVariant::factory()->create(['phone_id' => $phone->id]);

    $phone->delete();

    expect(PhoneVariant::count())->toBe(0);
});
