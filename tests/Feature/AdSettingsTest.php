<?php

declare(strict_types=1);

use App\Settings\AdSettings;

it('ships with ads switched off and sensible defaults', function (): void {
    $settings = app(AdSettings::class);

    expect($settings->enabled)->toBeFalse()
        ->and($settings->frequency)->toBe(5)
        ->and($settings->cooldown_minutes)->toBe(30);
});

it('persists changed values', function (): void {
    $settings = app(AdSettings::class);
    $settings->enabled = true;
    $settings->frequency = 3;
    $settings->cooldown_minutes = 15;
    $settings->save();

    $reloaded = app(AdSettings::class);

    expect($reloaded->enabled)->toBeTrue()
        ->and($reloaded->frequency)->toBe(3)
        ->and($reloaded->cooldown_minutes)->toBe(15);
});
