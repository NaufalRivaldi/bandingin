<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Phone;

beforeEach(function (): void {
    seedCatalog();
});

/** @return list<string> */
function indexableRoutes(): array
{
    return ['home', 'katalog', 'bandingkan', 'merek', 'berita', 'faq', 'lapor', 'legal', 'cara-menilai', 'sumber-harga'];
}

it('gives every page a title and a description', function (string $name): void {
    $response = $this->get(route($name))->assertOk();

    expect(title($response->content()))->not->toBe('')
        ->and(metaContent($response->content(), 'description'))->not->toBe('');
})->with(indexableRoutes());

it('suffixes every page title with the site name', function (string $name): void {
    expect(title($this->get(route($name))->content()))->toEndWith(config('seo.site_name'));
})->with(indexableRoutes());

it('gives each page its own description', function (): void {
    $descriptions = collect(indexableRoutes())
        ->map(fn (string $name): string => metaContent($this->get(route($name))->content(), 'description'));

    expect($descriptions->unique())->toHaveCount($descriptions->count());
});

it('self-canonicalises every page', function (string $name): void {
    $this->get(route($name))
        ->assertSee('<link rel="canonical" href="'.e(route($name)).'">', escape: false);
})->with(indexableRoutes());

it('emits open graph and twitter card tags', function (): void {
    $content = $this->get(route('home'))->content();

    expect($content)
        ->toContain('property="og:title"')
        ->toContain('property="og:description"')
        ->toContain('property="og:url"')
        ->toContain('property="og:site_name"')
        ->toContain('property="og:locale" content="id_ID"')
        ->toContain('name="twitter:card" content="summary_large_image"')
        ->toContain('name="twitter:title"')
        ->toContain('name="twitter:description"');
});

it('omits the social image while none is configured', function (): void {
    config()->set('seo.social.image', null);

    expect($this->get(route('home'))->content())
        ->not->toContain('property="og:image"')
        ->not->toContain('name="twitter:image"');
});

it('titles a phone page after the phone', function (): void {
    $phone = Phone::published()->firstOrFail();

    $content = $this->get(route('hp.show', $phone->slug))->content();

    expect(title($content))->toContain($phone->name)
        ->and(metaContent($content, 'description'))->toContain($phone->name);
});

it('describes an article with its excerpt', function (): void {
    $article = Article::factory()->create(['excerpt' => 'Ringkasan artikel untuk mesin pencari.']);

    $content = $this->get(route('berita.show', $article->slug))->content();

    expect(title($content))->toContain($article->title)
        ->and(metaContent($content, 'description'))->toBe('Ringkasan artikel untuk mesin pencari.');
});
