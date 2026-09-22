<?php

declare(strict_types=1);

use App\Actions\News\FetchArticleImage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function articlePage(string $head): string
{
    return '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">'.$head.'</head><body><p>isi</p></body></html>';
}

it('reads the og:image url', function (): void {
    Http::fake(['*' => Http::response(articlePage(
        '<meta property="og:image" content="https://static.tek.id/2026/09/21/realme-7QTJZ.jpeg" />'
    ))]);

    expect(app(FetchArticleImage::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))
        ->toBe('https://static.tek.id/2026/09/21/realme-7QTJZ.jpeg');
});

it('decodes html entities in the image url', function (): void {
    Http::fake(['*' => Http::response(articlePage(
        '<meta property="og:image" content="https://static.tek.id/a.jpg?w=600&amp;h=315" />'
    ))]);

    expect(app(FetchArticleImage::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))
        ->toBe('https://static.tek.id/a.jpg?w=600&h=315');
});

it('returns null when the page has no og:image', function (): void {
    Http::fake(['*' => Http::response(articlePage('<title>Tanpa gambar</title>'))]);

    expect(app(FetchArticleImage::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('returns null when the og:image is not an absolute url', function (): void {
    Http::fake(['*' => Http::response(articlePage('<meta property="og:image" content="/assets/fallback.png" />'))]);

    expect(app(FetchArticleImage::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('returns null on a 404', function (): void {
    Http::fake(['*' => Http::response('Not Found', 404)]);

    expect(app(FetchArticleImage::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('returns null when the connection fails', function (): void {
    Http::fake(fn () => throw new ConnectionException('timed out'));

    expect(app(FetchArticleImage::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});
