<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\ScrapeSource;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Http;

function fakeFeedsAndPages(): void
{
    Http::fake([
        'www.tek.id/*/rss' => Http::response(
            file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')),
            200,
        ),
        'www.tek.id/*' => Http::response(
            file_get_contents(base_path('tests/Fixtures/tek-article.html')),
            200,
        ),
    ]);
}

it('imports articles from every active source', function (): void {
    fakeFeedsAndPages();
    ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);

    $this->artisan('news:import')->assertExitCode(0);

    expect(Article::scraped()->count())->toBe(3);

    // One feed request plus one article-page request per new article.
    Http::assertSentCount(1 + 3);
});

it('skips inactive sources without failing', function (): void {
    fakeFeedsAndPages();
    ScrapeSource::factory()->inactive()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);

    $this->artisan('news:import')->assertExitCode(0);

    expect(Article::scraped()->count())->toBe(0);
    Http::assertNothingSent();
});

it('fetches no article pages on a second run', function (): void {
    fakeFeedsAndPages();
    ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);
    $this->artisan('news:import');

    fakeFeedsAndPages();
    $this->artisan('news:import')->assertExitCode(0);

    // The feed only: every article was already known.
    Http::assertSentCount(1);
    expect(Article::scraped()->count())->toBe(3);
});

it('keeps importing when one source fails, and reports failure', function (): void {
    Http::fake([
        'www.tek.id/gadget/rss' => Http::response('', 500),
        'www.tek.id/*/rss' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')), 200),
        'www.tek.id/*' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-article.html')), 200),
    ]);

    $broken = ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);
    ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/review/rss']);

    $this->artisan('news:import')->assertExitCode(1);

    expect(Article::scraped()->count())->toBe(3)
        ->and($broken->fresh()->last_error)->not->toBeNull();
});

it('stamps a successful run on the source', function (): void {
    fakeFeedsAndPages();
    $source = ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss', 'last_error' => 'lama']);

    $this->artisan('news:import');

    expect($source->fresh()->last_run_at)->not->toBeNull()
        ->and($source->fresh()->last_error)->toBeNull();
});

it('skips articles whose body cannot be extracted', function (): void {
    Http::fake([
        'www.tek.id/*/rss' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')), 200),
        'www.tek.id/*' => Http::response('<html><body><p>tidak ada isi artikel</p></body></html>', 200),
    ]);

    ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);

    $this->artisan('news:import')->assertExitCode(0);

    expect(Article::scraped()->count())->toBe(0);
});

it('is registered on the hourly schedule', function (): void {
    $events = app(Schedule::class)->events();

    $command = collect($events)->first(fn ($event): bool => str_contains($event->command ?? '', 'news:import'));

    expect($command)->not->toBeNull()
        ->and($command->expression)->toBe('0 * * * *');
});
