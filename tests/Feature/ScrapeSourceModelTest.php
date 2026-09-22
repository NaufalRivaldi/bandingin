<?php

declare(strict_types=1);

use App\Enums\ArticleCategory;
use App\Models\ScrapeSource;
use Database\Seeders\ScrapeSourceSeeder;
use Illuminate\Database\UniqueConstraintViolationException;

it('casts its category to the article category enum', function (): void {
    $source = ScrapeSource::factory()->create(['category' => ArticleCategory::Ulasan]);

    expect($source->fresh()->category)->toBe(ArticleCategory::Ulasan);
});

it('defaults to active', function (): void {
    expect(ScrapeSource::factory()->create()->is_active)->toBeTrue();
});

it('scopes to active sources', function (): void {
    ScrapeSource::factory()->count(2)->create();
    ScrapeSource::factory()->inactive()->create();

    expect(ScrapeSource::active()->count())->toBe(2);
});

it('rejects a duplicate feed url', function (): void {
    ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);

    expect(fn () => ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('stamps a successful run and clears the previous error', function (): void {
    $source = ScrapeSource::factory()->create(['last_error' => 'kesalahan lama']);

    $source->markRan();

    expect($source->fresh()->last_run_at)->not->toBeNull()
        ->and($source->fresh()->last_error)->toBeNull();
});

it('stamps a failed run with its reason', function (): void {
    $source = ScrapeSource::factory()->create();

    $source->markRan('feed tidak valid');

    expect($source->fresh()->last_error)->toBe('feed tidak valid')
        ->and($source->fresh()->last_run_at)->not->toBeNull();
});

it('seeds one source per tek.id channel', function (): void {
    (new ScrapeSourceSeeder)->run();

    expect(ScrapeSource::count())->toBe(7)
        ->and(ScrapeSource::where('feed_url', 'https://www.tek.id/review/rss')->first()->category)
        ->toBe(ArticleCategory::Ulasan);
});

it('seeds idempotently', function (): void {
    (new ScrapeSourceSeeder)->run();
    (new ScrapeSourceSeeder)->run();

    expect(ScrapeSource::count())->toBe(7);
});
