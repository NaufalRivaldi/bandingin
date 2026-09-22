<?php

declare(strict_types=1);

use App\Models\NewsItem;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;

/**
 * Fakes all seven feeds from the one saved fixture plus every article page.
 * Each channel therefore yields the same three articles, which is fine: the
 * external_id is unique per article, so the seven channels import 3 rows in
 * total, the last channel winning.
 */
function fakeAllTekChannels(string $ogImage = 'https://static.tek.id/2026/09/21/realme-7QTJZ.jpeg'): void
{
    Http::fake([
        'www.tek.id/*/rss' => Http::response(
            file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')),
            200,
        ),
        'www.tek.id/*' => Http::response(
            '<html><head><meta property="og:image" content="'.$ogImage.'" /></head><body></body></html>',
            200,
        ),
    ]);
}

it('imports articles from every channel', function (): void {
    fakeAllTekChannels();

    $this->artisan('news:import-tek')->assertExitCode(0);

    expect(NewsItem::count())->toBe(3);

    Http::assertSentCount(7 + 3);
});

it('attaches the og:image to imported items', function (): void {
    fakeAllTekChannels();

    $this->artisan('news:import-tek');

    expect(NewsItem::first()->image_url)->toBe('https://static.tek.id/2026/09/21/realme-7QTJZ.jpeg');
});

it('fetches no article pages on a second run', function (): void {
    fakeAllTekChannels();
    $this->artisan('news:import-tek');

    Http::fake([
        'www.tek.id/*/rss' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')), 200),
        'www.tek.id/*' => Http::response('<html><head></head><body></body></html>', 200),
    ]);

    $this->artisan('news:import-tek')->assertExitCode(0);

    // Seven feeds and nothing else: every article was already known.
    Http::assertSentCount(7);
    expect(NewsItem::count())->toBe(3);
});

it('keeps importing when one channel fails, and reports failure', function (): void {
    Http::fake([
        'www.tek.id/gadget/rss' => Http::response('', 500),
        'www.tek.id/*/rss' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')), 200),
        'www.tek.id/*' => Http::response('<html><head></head><body></body></html>', 200),
    ]);

    $this->artisan('news:import-tek')->assertExitCode(1);

    // The six healthy channels still imported their articles.
    expect(NewsItem::count())->toBe(3);
});

it('survives a feed that is not xml', function (): void {
    Http::fake(['www.tek.id/*/rss' => Http::response('{"error":"maintenance"}', 200)]);

    $this->artisan('news:import-tek')->assertExitCode(1);

    expect(NewsItem::count())->toBe(0);
});

it('imports items without an image when the page has no og:image', function (): void {
    Http::fake([
        'www.tek.id/*/rss' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')), 200),
        'www.tek.id/*' => Http::response('<html><head><title>Tanpa gambar</title></head><body></body></html>', 200),
    ]);

    $this->artisan('news:import-tek')->assertExitCode(0);

    expect(NewsItem::count())->toBe(3)
        ->and(NewsItem::whereNotNull('image_url')->count())->toBe(0);
});

it('is registered on the hourly schedule', function (): void {
    $events = app(Schedule::class)->events();

    $command = collect($events)->first(fn ($event): bool => str_contains($event->command ?? '', 'news:import-tek'));

    expect($command)->not->toBeNull()
        ->and($command->expression)->toBe('0 * * * *');
});
