<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Phone;

beforeEach(function (): void {
    seedCatalog();
});

function assertEveryImageIsLabelled(string $html): void
{
    foreach (imageTags($html) as $tag) {
        expect($tag)->toMatch('/\balt="[^"]+"/')
            ->and($tag)->toMatch('/\btitle="[^"]+"/');
    }
}

it('labels every image on the listing pages', function (string $name): void {
    assertEveryImageIsLabelled($this->get(route($name))->assertOk()->content());
})->with(['home', 'katalog', 'bandingkan', 'merek', 'berita']);

it('labels every image on a phone page with photos', function (): void {
    $phone = Phone::factory()->create([
        'slug' => 'berfoto',
        'name' => 'HP Berfoto',
        'image_urls' => ['https://cdn.test/a.jpg', 'https://cdn.test/b.jpg'],
    ]);

    $content = $this->get(route('hp.show', $phone->slug))->assertOk()->content();

    expect(imageTags($content))->not->toBeEmpty();
    assertEveryImageIsLabelled($content);
});

it('labels the hero image on an article', function (): void {
    $article = Article::factory()->create(['image_url' => 'https://cdn.test/hero.jpg']);

    $content = $this->get(route('berita.show', $article->slug))->assertOk()->content();

    expect(imageTags($content))->not->toBeEmpty();
    assertEveryImageIsLabelled($content);
});
