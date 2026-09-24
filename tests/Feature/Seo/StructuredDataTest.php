<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Phone;

beforeEach(function (): void {
    seedCatalog();
});

it('describes the site on the home page', function (): void {
    $blocks = jsonLdBlocks($this->get(route('home'))->content());

    expect($blocks)->toHaveKeys(['WebSite', 'Organization'])
        ->and($blocks['WebSite']['potentialAction']['@type'])->toBe('SearchAction')
        ->and($blocks['WebSite']['potentialAction']['target']['urlTemplate'])->toContain('{search_term_string}')
        ->and($blocks['Organization']['name'])->toBe(config('seo.organization.name'))
        ->and($blocks['Organization'])->not->toHaveKey('description');
});

it('describes a phone as a product', function (): void {
    $phone = Phone::published()->with(['brand', 'listings'])->firstOrFail();

    $product = jsonLdBlocks($this->get(route('hp.show', $phone->slug))->content())['Product'];

    expect($product['name'])->toBe($phone->name)
        ->and($product['sku'])->toBe($phone->slug)
        ->and($product['brand']['name'])->toBe($phone->brand->name)
        ->and($product['offers']['priceCurrency'])->toBe('IDR')
        ->and($product['offers']['@type'])->toBe('AggregateOffer')
        ->and((int) $product['offers']['lowPrice'])->toBe($phone->listings->min('price'));
});

it('falls back to a single offer when a phone has no listings', function (): void {
    $phone = Phone::factory()->create(['slug' => 'tanpa-penjual', 'price' => 3_200_000]);

    $offers = jsonLdBlocks($this->get(route('hp.show', $phone->slug))->content())['Product']['offers'];

    expect($offers['@type'])->toBe('Offer')
        ->and($offers['price'])->toBe('3200000');
});

it('trails breadcrumbs from home to the phone', function (): void {
    $phone = Phone::published()->firstOrFail();

    $items = jsonLdBlocks($this->get(route('hp.show', $phone->slug))->content())['BreadcrumbList']['itemListElement'];

    expect($items)->toHaveCount(3)
        ->and($items[0]['name'])->toBe('Beranda')
        ->and($items[0]['position'])->toBe(1)
        ->and($items[2]['item'])->toBe(route('hp.show', $phone->slug));
});

it('carries published and modified dates on an article', function (): void {
    $article = Article::factory()->create([
        'published_at' => now()->subDays(3),
        'updated_at' => now()->subDay(),
    ]);

    $block = jsonLdBlocks($this->get(route('berita.show', $article->slug))->content())['NewsArticle'];

    expect($block['headline'])->toBe($article->title)
        ->and($block['datePublished'])->toBe($article->published_at->toIso8601String())
        ->and($block['dateModified'])->toBe($article->updated_at->toIso8601String())
        ->and($block['publisher']['name'])->toBe(config('seo.organization.name'))
        ->and($block['mainEntityOfPage']['@id'])->toBe(route('berita.show', $article->slug));
});

it('credits us on our own article', function (): void {
    $article = Article::factory()->create();

    $author = jsonLdBlocks($this->get(route('berita.show', $article->slug))->content())['NewsArticle']['author'];

    expect($author)->toBe(['@type' => 'Person', 'name' => config('seo.author')]);
});

it('credits the publisher on a scraped article', function (): void {
    $article = Article::factory()->scraped()->create();

    $author = jsonLdBlocks($this->get(route('berita.show', $article->slug))->content())['NewsArticle']['author'];

    expect($author)->toBe(['@type' => 'Organization', 'name' => $article->source]);
});

it('emits exactly one canonical per article', function (): void {
    $article = Article::factory()->scraped()->create();

    $html = $this->get(route('berita.show', $article->slug))->content();

    expect(substr_count($html, 'rel="canonical"'))->toBe(1)
        ->and($html)->toContain('<link rel="canonical" href="'.e($article->source_url).'">');
});

it('describes the help centre as an faq page', function (): void {
    $block = jsonLdBlocks($this->get(route('faq'))->content())['FAQPage'];

    expect($block['mainEntity'])->not->toBeEmpty()
        ->and($block['mainEntity'][0]['@type'])->toBe('Question')
        ->and($block['mainEntity'][0]['acceptedAnswer']['@type'])->toBe('Answer');
});

it('parses as valid json on every page', function (string $name): void {
    expect(jsonLdBlocks($this->get(route($name))->content()))->not->toBeEmpty();
})->with(['home', 'katalog', 'bandingkan', 'merek', 'berita', 'faq', 'lapor', 'legal', 'cara-menilai', 'sumber-harga']);
