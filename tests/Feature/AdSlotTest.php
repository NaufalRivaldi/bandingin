<?php

declare(strict_types=1);

use App\Ads\AdSlot;
use App\Models\Ad;
use Illuminate\Support\Facades\Cache;

it('serves nothing when ads are disabled', function (): void {
    Ad::factory()->create();

    expect(app(AdSlot::class)->payload())->toBeNull();
});

it('serves nothing when no ad is active', function (): void {
    enableAds();
    Ad::factory()->inactive()->create();

    expect(app(AdSlot::class)->payload())->toBeNull();
});

it('serves the only active ad alongside the configured knobs', function (): void {
    enableAds(frequency: 3, cooldownMinutes: 15);
    Ad::factory()->create(['url' => 'https://sponsor.test/offer']);

    expect(app(AdSlot::class)->payload())->toBe([
        'frequency' => 3,
        'cooldownMinutes' => 15,
        'url' => 'https://sponsor.test/offer',
    ]);
});

it('never serves an inactive ad', function (): void {
    enableAds();
    Ad::factory()->create(['url' => 'https://active.test']);
    Ad::factory()->inactive()->create(['url' => 'https://hidden.test']);

    $slot = app(AdSlot::class);
    $urls = collect(range(1, 50))->map(fn (): string => $slot->payload()['url']);

    expect($urls->unique()->values()->all())->toBe(['https://active.test']);
});

// The suite runs on the `array` cache store, which keeps values in memory and
// never serializes them. Production uses `database`, which does — and caching
// Eloquent models there returns __PHP_Incomplete_Class and breaks the slot.
it('caches primitives that survive a serializing cache store', function (): void {
    enableAds();
    Ad::factory()->create(['url' => 'https://sponsor.test/offer', 'weight' => 3]);

    app(AdSlot::class)->payload();

    $cached = Cache::get('ads.active');

    expect($cached)->toBeArray()
        ->and($cached)->toBe([['url' => 'https://sponsor.test/offer', 'weight' => 3]])
        ->and(unserialize(serialize($cached)))->toBe($cached);
});

it('still serves a payload when the cache is read back after serialization', function (): void {
    enableAds();
    Ad::factory()->create(['url' => 'https://sponsor.test/offer']);

    app(AdSlot::class)->payload();

    // Round-trip the cache entry exactly as a database/file/redis store would.
    Cache::put('ads.active', unserialize(serialize(Cache::get('ads.active'))));

    expect(app(AdSlot::class)->payload()['url'])->toBe('https://sponsor.test/offer');
});

it('picks ads in proportion to their weight', function (): void {
    enableAds();
    Ad::factory()->create(['url' => 'https://rare.test', 'weight' => 1]);
    Ad::factory()->create(['url' => 'https://common.test', 'weight' => 9]);

    $slot = app(AdSlot::class);
    $counts = collect(range(1, 200))
        ->map(fn (): string => $slot->payload()['url'])
        ->countBy();

    expect($counts['https://rare.test'] ?? 0)->toBeGreaterThan(0)
        ->and($counts['https://common.test'] ?? 0)->toBeGreaterThan($counts['https://rare.test'] ?? 0);
});
