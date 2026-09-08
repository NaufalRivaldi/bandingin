<?php

declare(strict_types=1);

use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;
use App\Money\Rupiah;
use Illuminate\Support\Facades\DB;

it('formats rupiah with dot separators', function (): void {
    expect(Rupiah::format(5_499_000))->toBe('Rp5.499.000')
        ->and(Rupiah::format(0))->toBe('Rp0');
});

it('reads a spec display and comparable value', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create(['key' => 'baterai']);
    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.000 mAh',
        'comparable_value' => 5000,
    ]);

    $phone->load('specs.definition');

    expect($phone->display('baterai'))->toBe('5.000 mAh')
        ->and($phone->comparable('baterai'))->toBe(5000);
});

it('falls back for a spec the phone does not have', function (): void {
    $phone = Phone::factory()->create()->load('specs.definition');

    expect($phone->display('tidakada'))->toBe('—')
        ->and($phone->comparable('tidakada'))->toBe(0);
});

it('exposes the five scores and their average', function (): void {
    $phone = Phone::factory()->create([
        'score_performa' => 72,
        'score_layar' => 80,
        'score_kamera' => 78,
        'score_baterai' => 74,
        'score_nilai' => 70,
    ]);

    expect($phone->scores())->toBe([
        'Performa' => 72,
        'Layar' => 80,
        'Kamera' => 78,
        'Baterai' => 74,
        'Nilai per Rupiah' => 70,
    ])->and($phone->averageScore())->toBe(75);
});

it('formats its own price', function (): void {
    expect(Phone::factory()->create(['price' => 5_499_000])->formattedPrice())
        ->toBe('Rp5.499.000');
});

it('reads specs without extra queries once loaded', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create(['key' => 'ram']);
    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '8 GB',
        'comparable_value' => 8,
    ]);

    $loaded = Phone::with('specs.definition')->find($phone->id);

    DB::enableQueryLog();
    $loaded->display('ram');
    $loaded->comparable('ram');
    $loaded->display('storage');

    expect(DB::getQueryLog())->toBeEmpty();
});
