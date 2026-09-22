<?php

declare(strict_types=1);

use App\Actions\News\FetchTekChannel;
use App\News\TekChannel;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

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

    $articles = app(FetchTekChannel::class)->handle(TekChannel::Gadget);

    expect($articles)->toHaveCount(3);
});

it('maps an item onto a scraped article', function (): void {
    fakeGadgetFeed();

    $article = app(FetchTekChannel::class)->handle(TekChannel::Gadget)[0];

    expect($article->externalId)->toBe('b2qbp9uhh')
        ->and($article->channel)->toBe(TekChannel::Gadget)
        ->and($article->title)->toBe('realme 16 Pro Harry Potter Edition Resmi Meluncur, Ini Harga dan Spesifikasinya')
        ->and($article->excerpt)->toStartWith('realme 16 Pro Harry Potter Edition meluncur di Indonesia')
        ->and($article->url)->toBe('https://www.tek.id/gadget/realme-16-pro-harry-potter-edition-meluncur-ini-harganya-b2qbp9uhh')
        ->and($article->imageUrl)->toBeNull();
});

it('reads the feed timestamp as Jakarta time and stores it as UTC', function (): void {
    fakeGadgetFeed();

    $article = app(FetchTekChannel::class)->handle(TekChannel::Gadget)[0];

    // Feed says 2026-09-21 19:18:00 with no offset; Jakarta is UTC+7.
    expect($article->publishedAt->toDateTimeString())->toBe('2026-09-21 12:18:00')
        ->and($article->publishedAt->timezoneName)->toBe('UTC');
});

it('decodes html entities in the excerpt', function (): void {
    fakeGadgetFeed();

    $article = app(FetchTekChannel::class)->handle(TekChannel::Gadget)[1];

    expect($article->excerpt)->toContain('Bang & Olufsen');
});

it('skips an item with no link rather than failing the channel', function (): void {
    fakeGadgetFeed();

    $ids = array_map(
        fn ($article): string => $article->externalId,
        app(FetchTekChannel::class)->handle(TekChannel::Gadget),
    );

    expect($ids)->toBe(['b2qbp9uhh', 'b2qbb9ugd', 'b2qaY9ufR']);
});

it('throws when the response body is not xml', function (): void {
    // Note: a body like "<html>maintenance</html>" would parse fine as XML.
    // Use something genuinely unparseable so the assertion means something.
    Http::fake(['www.tek.id/gadget/rss' => Http::response('{"error":"maintenance"}', 200)]);

    expect(fn () => app(FetchTekChannel::class)->handle(TekChannel::Gadget))
        ->toThrow(RuntimeException::class);
});

it('throws when the feed responds with a server error', function (): void {
    Http::fake(['www.tek.id/gadget/rss' => Http::response('', 500)]);

    expect(fn () => app(FetchTekChannel::class)->handle(TekChannel::Gadget))
        ->toThrow(RequestException::class);
});

it('returns an empty list for a feed with no items', function (): void {
    Http::fake([
        'www.tek.id/gadget/rss' => Http::response(
            '<?xml version="1.0"?><rss version="2.0"><channel><title>Kosong</title></channel></rss>',
            200,
        ),
    ]);

    expect(app(FetchTekChannel::class)->handle(TekChannel::Gadget))->toBe([]);
});

it('sends an identifying user agent', function (): void {
    fakeGadgetFeed();

    app(FetchTekChannel::class)->handle(TekChannel::Gadget);

    Http::assertSent(fn ($request): bool => str_contains($request->header('User-Agent')[0], 'BandinginBot'));
});
