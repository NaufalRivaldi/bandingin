<?php

declare(strict_types=1);

use App\Actions\News\FetchFeed;
use App\Models\ScrapeSource;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

function gadgetSource(): ScrapeSource
{
    return ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);
}

function fakeGadgetFeed(): void
{
    Http::fake([
        'www.tek.id/gadget/rss' => Http::response(
            file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')),
            200,
            ['Content-Type' => 'application/rss+xml'],
        ),
    ]);
}

it('parses every well-formed item in the feed', function (): void {
    fakeGadgetFeed();

    expect(app(FetchFeed::class)->handle(gadgetSource()))->toHaveCount(3);
});

it('maps an item onto a scraped article', function (): void {
    fakeGadgetFeed();

    $article = app(FetchFeed::class)->handle(gadgetSource())[0];

    expect($article->externalId)->toBe('b2qbp9uhh')
        ->and($article->title)->toBe('realme 16 Pro Harry Potter Edition Resmi Meluncur, Ini Harga dan Spesifikasinya')
        ->and($article->excerpt)->toStartWith('realme 16 Pro Harry Potter Edition meluncur di Indonesia')
        ->and($article->url)->toBe('https://www.tek.id/gadget/realme-16-pro-harry-potter-edition-meluncur-ini-harganya-b2qbp9uhh')
        ->and($article->body)->toBeNull()
        ->and($article->imageUrl)->toBeNull();
});

it('reads the feed timestamp as Jakarta time and stores it as UTC', function (): void {
    fakeGadgetFeed();

    $article = app(FetchFeed::class)->handle(gadgetSource())[0];

    // Feed says 2026-09-21 19:18:00 with no offset; Jakarta is UTC+7.
    expect($article->publishedAt->toDateTimeString())->toBe('2026-09-21 12:18:00')
        ->and($article->publishedAt->timezoneName)->toBe('UTC');
});

it('decodes html entities in the excerpt', function (): void {
    fakeGadgetFeed();

    expect(app(FetchFeed::class)->handle(gadgetSource())[1]->excerpt)->toContain('Bang & Olufsen');
});

it('skips an item with no link rather than failing the feed', function (): void {
    fakeGadgetFeed();

    $ids = array_map(
        fn ($article): string => $article->externalId,
        app(FetchFeed::class)->handle(gadgetSource()),
    );

    expect($ids)->toBe(['b2qbp9uhh', 'b2qbb9ugd', 'b2qaY9ufR']);
});

it('uses the url from the source row', function (): void {
    Http::fake([
        'example.test/feed' => Http::response(
            file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')),
            200,
        ),
    ]);

    $source = ScrapeSource::factory()->create(['feed_url' => 'https://example.test/feed']);

    expect(app(FetchFeed::class)->handle($source))->toHaveCount(3);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://example.test/feed');
});

it('throws when the response body is not xml', function (): void {
    Http::fake(['www.tek.id/gadget/rss' => Http::response('{"error":"maintenance"}', 200)]);

    expect(fn () => app(FetchFeed::class)->handle(gadgetSource()))
        ->toThrow(RuntimeException::class);
});

it('throws when the feed responds with a server error', function (): void {
    Http::fake(['www.tek.id/gadget/rss' => Http::response('', 500)]);

    expect(fn () => app(FetchFeed::class)->handle(gadgetSource()))
        ->toThrow(RequestException::class);
});

it('returns an empty list for a feed with no items', function (): void {
    Http::fake([
        'www.tek.id/gadget/rss' => Http::response(
            '<?xml version="1.0"?><rss version="2.0"><channel><title>Kosong</title></channel></rss>',
            200,
        ),
    ]);

    expect(app(FetchFeed::class)->handle(gadgetSource()))->toBe([]);
});

it('sends an identifying user agent', function (): void {
    fakeGadgetFeed();

    app(FetchFeed::class)->handle(gadgetSource());

    Http::assertSent(fn ($request): bool => str_contains($request->header('User-Agent')[0], 'BandinginBot'));
});
