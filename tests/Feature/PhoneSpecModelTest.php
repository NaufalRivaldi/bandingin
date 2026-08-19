<?php

declare(strict_types=1);

use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;
use Illuminate\Database\UniqueConstraintViolationException;

it('links a phone to a spec definition with both values', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create(['key' => 'baterai', 'label' => 'Kapasitas']);

    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.000 mAh',
        'comparable_value' => 5000,
    ]);

    $spec = $phone->specs()->with('definition')->sole();

    expect($spec->display_value)->toBe('5.000 mAh')
        ->and($spec->comparable_value)->toBe(5000)
        ->and($spec->definition->key)->toBe('baterai');
});

it('stores lower-is-better metrics negated', function (): void {
    $spec = PhoneSpec::factory()->create([
        'display_value' => '198 g',
        'comparable_value' => -198,
    ]);

    expect($spec->fresh()->comparable_value)->toBe(-198);
});

it('rejects the same spec twice on one phone', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create();

    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
    ]);

    expect(fn (): PhoneSpec => PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

it('rejects a duplicate spec definition key', function (): void {
    SpecDefinition::factory()->create(['key' => 'chipset']);

    expect(fn (): SpecDefinition => SpecDefinition::factory()->create(['key' => 'chipset']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('deletes specs when the phone is deleted', function (): void {
    $phone = Phone::factory()->create();
    PhoneSpec::factory()->create(['phone_id' => $phone->id]);

    $phone->delete();

    expect(PhoneSpec::count())->toBe(0);
});
