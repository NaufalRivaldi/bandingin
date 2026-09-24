<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Phone;

beforeEach(function (): void {
    seedCatalog();
});

it('serves the index as xml listing the three children', function (): void {
    $response = $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml');

    expect($response->content())
        ->toContain('<sitemapindex')
        ->toContain(route('sitemap.pages'))
        ->toContain(route('sitemap.phones'))
        ->toContain(route('sitemap.articles'));
});

it('renders valid xml for every sitemap', function (string $path): void {
    $xml = simplexml_load_string($this->get($path)->assertOk()->content());

    expect($xml)->not->toBeFalse();
})->with(['/sitemap.xml', '/sitemap-pages.xml', '/sitemap-phones.xml', '/sitemap-articles.xml']);

it('lists the static pages', function (): void {
    $content = $this->get('/sitemap-pages.xml')->content();

    expect($content)
        ->toContain('<loc>'.route('home').'</loc>')
        ->toContain('<loc>'.route('katalog').'</loc>')
        ->toContain('<loc>'.route('legal').'</loc>');
});

it('lists published phones and leaves drafts out', function (): void {
    $published = Phone::published()->firstOrFail();
    $draft = Phone::factory()->create(['published_at' => null, 'slug' => 'draft-hp']);

    $content = $this->get('/sitemap-phones.xml')->content();

    expect($content)
        ->toContain(route('hp.show', $published->slug))
        ->not->toContain(route('hp.show', $draft->slug));
});

it('lists every published article, scraped ones included', function (): void {
    $own = Article::factory()->create(['slug' => 'tulisan-kami']);
    $scraped = Article::factory()->scraped()->create(['slug' => 'tulisan-orang']);

    $content = $this->get('/sitemap-articles.xml')->content();

    expect($content)
        ->toContain(route('berita.show', $own->slug))
        ->toContain(route('berita.show', $scraped->slug));
});

it('lists the whole seeded article catalogue', function (): void {
    $content = $this->get('/sitemap-articles.xml')->content();

    expect(substr_count($content, '<loc>'))->toBe(Article::published()->count());
});

it('leaves unpublished articles out', function (): void {
    $draft = Article::factory()->create(['slug' => 'draf', 'published_at' => null]);

    expect($this->get('/sitemap-articles.xml')->content())
        ->not->toContain(route('berita.show', $draft->slug));
});

it('stamps each entry with a last modified date', function (): void {
    expect($this->get('/sitemap-phones.xml')->content())->toContain('<lastmod>');
});
