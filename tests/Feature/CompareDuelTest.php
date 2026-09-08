<?php

declare(strict_types=1);

use App\Actions\CompareDuel;
use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;

function duelPhone(string $name, int $price, array $specs): Phone
{
    $phone = Phone::factory()->create(['name' => $name, 'price' => $price]);

    foreach ($specs as $key => [$display, $comparable]) {
        $definition = SpecDefinition::firstOrCreate(
            ['key' => $key],
            ['label' => ucfirst($key), 'group' => 'Performa', 'sort' => 1],
        );

        PhoneSpec::factory()->create([
            'phone_id' => $phone->id,
            'spec_definition_id' => $definition->id,
            'display_value' => $display,
            'comparable_value' => $comparable,
        ]);
    }

    return $phone->load('specs.definition');
}

it('awards each row to the higher comparable value', function (): void {
    $left = duelPhone('Kiri', 4_000_000, ['baterai' => ['5.000 mAh', 5000]]);
    $right = duelPhone('Kanan', 4_000_000, ['baterai' => ['5.500 mAh', 5500]]);

    $duel = app(CompareDuel::class)->handle($left, $right, ['' => ['baterai' => 'Kapasitas']]);

    expect($duel->rightWins)->toBe(1)
        ->and($duel->leftWins)->toBe(0);
});

it('awards the price row to the cheaper phone', function (): void {
    $left = duelPhone('Murah', 3_000_000, []);
    $right = duelPhone('Mahal', 9_000_000, []);

    $duel = app(CompareDuel::class)->handle($left, $right, ['' => [CompareDuel::PRICE_KEY => 'Harga']]);

    expect($duel->leftWins)->toBe(1)
        ->and($duel->rightWins)->toBe(0);
});

it('treats identical values as a tie', function (): void {
    $left = duelPhone('Kiri', 4_000_000, ['ram' => ['8 GB', 8]]);
    $right = duelPhone('Kanan', 4_000_000, ['ram' => ['8 GB', 8]]);

    $duel = app(CompareDuel::class)->handle($left, $right, ['' => ['ram' => 'RAM']]);

    expect($duel->leftWins)->toBe(0)
        ->and($duel->rightWins)->toBe(0)
        ->and($duel->groups[0]->rows[0]->isTie())->toBeTrue();
});

it('hides tied rows when only differences are requested', function (): void {
    $left = duelPhone('Kiri', 4_000_000, ['ram' => ['8 GB', 8], 'baterai' => ['5.000 mAh', 5000]]);
    $right = duelPhone('Kanan', 4_000_000, ['ram' => ['8 GB', 8], 'baterai' => ['5.500 mAh', 5500]]);

    $rows = ['ram' => 'RAM', 'baterai' => 'Kapasitas'];

    $all = app(CompareDuel::class)->handle($left, $right, ['' => $rows], false);
    $differing = app(CompareDuel::class)->handle($left, $right, ['' => $rows], true);

    expect($all->groups[0]->rows)->toHaveCount(2)
        ->and($differing->groups[0]->rows)->toHaveCount(1);
});

it('keeps the negation convention so the lighter phone wins on weight', function (): void {
    $left = duelPhone('Ringan', 4_000_000, ['berat' => ['162 g', -162]]);
    $right = duelPhone('Berat', 4_000_000, ['berat' => ['201 g', -201]]);

    $duel = app(CompareDuel::class)->handle($left, $right, ['' => ['berat' => 'Berat']]);

    expect($duel->leftWins)->toBe(1);
});
