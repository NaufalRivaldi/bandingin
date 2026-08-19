<?php

declare(strict_types=1);

use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\PhoneComparisonBlock;
use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;

it('identifies itself for the editor', function (): void {
    expect(PhoneComparisonBlock::getId())->toBe('phone_comparison')
        ->and(PhoneComparisonBlock::getLabel())->not->toBe('');
});

it('renders a table from live phone data', function (): void {
    $definition = SpecDefinition::factory()->create(['key' => 'baterai', 'label' => 'Kapasitas']);

    $left = Phone::factory()->create(['name' => 'Galaxy A56 5G']);
    $right = Phone::factory()->create(['name' => 'Redmi Note 14 Pro 5G']);

    PhoneSpec::factory()->create([
        'phone_id' => $left->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.000 mAh',
        'comparable_value' => 5000,
    ]);
    PhoneSpec::factory()->create([
        'phone_id' => $right->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.500 mAh',
        'comparable_value' => 5500,
    ]);

    $html = PhoneComparisonBlock::toHtml([
        'leftPhoneId' => $left->id,
        'rightPhoneId' => $right->id,
        'specKeys' => ['baterai'],
    ], []);

    expect($html)->toContain('Galaxy A56 5G')
        ->and($html)->toContain('Redmi Note 14 Pro 5G')
        ->and($html)->toContain('5.000 mAh')
        ->and($html)->toContain('5.500 mAh')
        ->and($html)->toContain('Kapasitas');
});

it('reflects a later price or spec edit rather than a frozen copy', function (): void {
    $definition = SpecDefinition::factory()->create(['key' => 'baterai', 'label' => 'Kapasitas']);
    $left = Phone::factory()->create(['name' => 'Galaxy A56 5G']);
    $right = Phone::factory()->create(['name' => 'Redmi Note 14 Pro 5G']);

    $spec = PhoneSpec::factory()->create([
        'phone_id' => $left->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.000 mAh',
        'comparable_value' => 5000,
    ]);
    PhoneSpec::factory()->create([
        'phone_id' => $right->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.500 mAh',
        'comparable_value' => 5500,
    ]);

    $config = [
        'leftPhoneId' => $left->id,
        'rightPhoneId' => $right->id,
        'specKeys' => ['baterai'],
    ];

    $spec->update(['display_value' => '5.100 mAh', 'comparable_value' => 5100]);

    expect(PhoneComparisonBlock::toHtml($config, []))->toContain('5.100 mAh')
        ->and(PhoneComparisonBlock::toHtml($config, []))->not->toContain('5.000 mAh');
});

it('renders nothing when a referenced phone is gone', function (): void {
    $html = PhoneComparisonBlock::toHtml([
        'leftPhoneId' => 9999,
        'rightPhoneId' => 9998,
        'specKeys' => ['baterai'],
    ], []);

    expect($html)->toBe('');
});

it('names both phones in the editor preview', function (): void {
    $left = Phone::factory()->create(['name' => 'iPhone 16']);
    $right = Phone::factory()->create(['name' => 'Galaxy S25']);

    $preview = PhoneComparisonBlock::toPreviewHtml([
        'leftPhoneId' => $left->id,
        'rightPhoneId' => $right->id,
        'specKeys' => [],
    ]);

    expect($preview)->toContain('iPhone 16')
        ->and($preview)->toContain('Galaxy S25');
});
