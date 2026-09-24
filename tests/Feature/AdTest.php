<?php

declare(strict_types=1);

use App\Models\Ad;

it('casts weight and is_active', function (): void {
    $ad = Ad::factory()->create(['weight' => '7']);

    expect($ad->refresh())
        ->weight->toBe(7)
        ->is_active->toBeTrue();
});

it('builds an inactive ad from the factory state', function (): void {
    expect(Ad::factory()->inactive()->create()->is_active)->toBeFalse();
});

it('scopes to active ads only', function (): void {
    $active = Ad::factory()->create();
    Ad::factory()->inactive()->create();

    expect(Ad::query()->active()->pluck('id')->all())->toBe([$active->id]);
});
