<?php

declare(strict_types=1);

use App\Models\Ad;

// These run against pages that render on an empty catalogue, so a failure here
// is always about ads and never about missing seed data.

it('renders the ad config block when ads are enabled', function (): void {
    enableAds(frequency: 4, cooldownMinutes: 20);
    Ad::factory()->create(['url' => 'https://sponsor.test/offer']);

    $this->get(route('katalog'))
        ->assertOk()
        ->assertSee('id="bdg-ad-config"', escape: false)
        ->assertSee('sponsor.test', escape: false)
        ->assertSee('"frequency":4', escape: false)
        ->assertSee('"cooldownMinutes":20', escape: false);
});

it('renders nothing when ads are disabled', function (): void {
    Ad::factory()->create(['url' => 'https://sponsor.test/offer']);

    $this->get(route('katalog'))
        ->assertOk()
        ->assertDontSee('bdg-ad-config')
        ->assertDontSee('sponsor.test');
});

it('renders nothing when every ad is inactive', function (): void {
    enableAds();
    Ad::factory()->inactive()->create(['url' => 'https://sponsor.test/offer']);

    $this->get(route('katalog'))
        ->assertOk()
        ->assertDontSee('bdg-ad-config')
        ->assertDontSee('sponsor.test');
});

it('renders the config block on every public page, not just one', function (): void {
    enableAds();
    Ad::factory()->create(['url' => 'https://sponsor.test/offer']);

    $this->get(route('faq'))
        ->assertOk()
        ->assertSee('id="bdg-ad-config"', escape: false);
});

// The SEO suite parses every <script type="application/ld+json"> block on the
// page, so the ad config must never claim that type.
it('does not emit the config as a json-ld block', function (): void {
    enableAds();
    Ad::factory()->create(['url' => 'https://sponsor.test/offer']);

    $html = $this->get(route('katalog'))->assertOk()->getContent();

    expect(jsonLdBlocks($html))->not->toHaveKey('sponsor.test')
        ->and($html)->toContain('<script type="application/json" id="bdg-ad-config">');
});
