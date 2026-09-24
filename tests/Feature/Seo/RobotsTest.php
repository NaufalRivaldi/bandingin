<?php

declare(strict_types=1);

beforeEach(function (): void {
    seedCatalog();
});

it('serves robots.txt as plain text', function (): void {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
});

it('welcomes the search and AI crawlers', function (string $bot): void {
    $this->get('/robots.txt')->assertSee('User-agent: '.$bot, escape: false);
})->with(['Googlebot', 'Bingbot', 'GPTBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended', 'CCBot']);

it('disallows the scraper bots', function (): void {
    $body = $this->get('/robots.txt')->content();

    foreach (config('seo.robots.bad_bots') as $bot) {
        expect($body)->toContain('User-agent: '.$bot);
    }

    expect($body)->toContain("User-agent: AhrefsBot\nUser-agent: SemrushBot");
});

it('keeps crawlers out of the private paths', function (): void {
    $body = $this->get('/robots.txt')->content();

    foreach (config('seo.robots.disallowed_paths') as $path) {
        expect($body)->toContain('Disallow: '.$path);
    }
});

it('advertises the sitemap index by absolute url', function (): void {
    $this->get('/robots.txt')->assertSee('Sitemap: '.route('sitemap.index'), escape: false);
});

it('marks a clean page as indexable in the tag and the header', function (): void {
    $this->get(route('katalog'))
        ->assertSee('<meta name="robots" content="index, follow">', escape: false)
        ->assertHeader('X-Robots-Tag', 'index, follow');
});

it('noindexes a filtered catalogue url', function (string $query): void {
    $this->get(route('katalog').'?'.$query)
        ->assertSee('<meta name="robots" content="noindex, follow">', escape: false)
        ->assertHeader('X-Robots-Tag', 'noindex, follow');
})->with(['q=samsung', 'merek[]=Samsung', 'harga[]=p1', 'spek[]=ram12', 'urut=murah']);

it('noindexes a specific comparison url', function (): void {
    $this->get(route('bandingkan').'?left=a56&right=note14pro')
        ->assertSee('<meta name="robots" content="noindex, follow">', escape: false);
});

it('keeps a campaign tag from deindexing a clean page', function (): void {
    $this->get(route('katalog').'?utm_source=newsletter')
        ->assertSee('<meta name="robots" content="index, follow">', escape: false);
});
