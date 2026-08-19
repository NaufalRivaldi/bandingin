<?php

declare(strict_types=1);

use App\Models\FeaturedMatchup;
use App\Models\Phone;

it('builds its label from both phone names', function (): void {
    $matchup = FeaturedMatchup::factory()->create([
        'left_phone_id' => Phone::factory()->create(['name' => 'Galaxy A56 5G'])->id,
        'right_phone_id' => Phone::factory()->create(['name' => 'Redmi Note 14 Pro 5G'])->id,
    ]);

    expect($matchup->label())->toBe('Galaxy A56 5G vs Redmi Note 14 Pro 5G');
});

it('deletes the matchup when either phone is deleted', function (): void {
    $left = Phone::factory()->create();
    FeaturedMatchup::factory()->create(['left_phone_id' => $left->id]);

    $left->delete();

    expect(FeaturedMatchup::count())->toBe(0);
});
