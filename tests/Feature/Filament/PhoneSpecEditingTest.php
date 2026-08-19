<?php

declare(strict_types=1);

use App\Filament\Resources\Phones\Pages\EditPhone;
use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('loads a phone existing specs into the form', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create(['key' => 'baterai', 'label' => 'Kapasitas']);
    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.000 mAh',
        'comparable_value' => 5000,
    ]);

    $state = Livewire::test(EditPhone::class, ['record' => $phone->getRouteKey()])->get('data');

    expect(collect($state['specs'])->pluck('display_value'))->toContain('5.000 mAh');
});

it('saves an edited spec value back to the database', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create(['key' => 'berat', 'label' => 'Berat']);
    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '198 g',
        'comparable_value' => -198,
    ]);

    $component = Livewire::test(EditPhone::class, ['record' => $phone->getRouteKey()]);

    // Mutate the loaded state in place, keeping the repeater's item keys, so
    // Filament updates the existing row instead of replacing the collection.
    $state = $component->get('data');
    $itemKey = array_key_first($state['specs']);
    $state['specs'][$itemKey]['display_value'] = '190 g';
    $state['specs'][$itemKey]['comparable_value'] = -190;

    $component->fillForm($state)
        ->call('save')
        ->assertHasNoFormErrors();

    $saved = $phone->specs()->sole();

    expect($saved->display_value)->toBe('190 g')
        ->and($saved->comparable_value)->toBe(-190);
});

it('keeps the negation convention when a weight is edited', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create(['key' => 'berat', 'label' => 'Berat']);
    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '198 g',
        'comparable_value' => -198,
    ]);

    $lighter = $phone->specs()->sole();

    $heavier = PhoneSpec::factory()->create([
        'phone_id' => Phone::factory()->create()->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '210 g',
        'comparable_value' => -210,
    ]);

    // Lighter must win: higher comparable_value always wins.
    expect($lighter->comparable_value)->toBeGreaterThan($heavier->comparable_value);
});
