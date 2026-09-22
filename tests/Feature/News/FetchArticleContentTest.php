<?php

declare(strict_types=1);

use App\Actions\News\FetchArticleContent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function fakeArticlePage(): void
{
    Http::fake(['*' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-article.html')), 200)]);
}

it('extracts the article body', function (): void {
    fakeArticlePage();

    $content = app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh');

    expect($content)->not->toBeNull()
        ->and($content->body)->toContain('<p>')
        ->and(strlen($content->body))->toBeGreaterThan(1000);
});

it('strips the ads and the related-articles widget', function (): void {
    fakeArticlePage();

    $body = app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh')->body;

    // RichContentRenderer unwraps unknown elements rather than dropping them,
    // so anything left here would surface as stray text in the published article.
    expect($body)->not->toContain('Baca Juga')
        ->and($body)->not->toContain('bacajuga')
        ->and($body)->not->toContain('flying_carpet')
        ->and($body)->not->toContain('div-gpt')
        ->and($body)->not->toContain('googletag')
        ->and($body)->not->toContain('<script')
        ->and($body)->not->toContain('<style');
});

it('normalises runaway whitespace', function (): void {
    fakeArticlePage();

    $body = app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh')->body;

    expect($body)->not->toMatch('/\s{10,}/');
});

it('reads the hero image', function (): void {
    fakeArticlePage();

    $content = app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh');

    expect($content->imageUrl)->toStartWith('https://static.tek.id/');
});

it('returns null when the page has no article body', function (): void {
    Http::fake(['*' => Http::response('<html><head></head><body><p>kosong</p></body></html>', 200)]);

    expect(app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('returns null when the extracted body is only junk', function (): void {
    Http::fake(['*' => Http::response(
        '<html><body><div class="artikel__content"><div class="bacajuga">Baca Juga</div></div></body></html>',
        200,
    )]);

    expect(app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('returns null on a 404', function (): void {
    Http::fake(['*' => Http::response('Not Found', 404)]);

    expect(app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('returns null when the connection fails', function (): void {
    Http::fake(fn () => throw new ConnectionException('timed out'));

    expect(app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('survives a page with no hero image', function (): void {
    Http::fake(['*' => Http::response(
        '<html><body><div class="artikel__content"><p>'.str_repeat('Isi artikel yang cukup panjang. ', 20).'</p></div></body></html>',
        200,
    )]);

    $content = app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh');

    expect($content)->not->toBeNull()
        ->and($content->imageUrl)->toBeNull();
});
