<?php

declare(strict_types=1);

use App\Models\Article;

it('shows the source credit on a scraped article', function (): void {
    $article = Article::factory()->scraped()->create();

    $this->get(route('berita.show', $article->slug))
        ->assertSuccessful()
        ->assertSee('tek.id')
        ->assertSee($article->source_url);
});

it('points the canonical tag at the original', function (): void {
    $article = Article::factory()->scraped()->create();

    $this->get(route('berita.show', $article->slug))
        ->assertSee('<link rel="canonical" href="'.e($article->source_url).'">', escape: false);
});

it('does not credit a source on our own article', function (): void {
    $article = Article::factory()->create(['title' => 'Tulisan kami sendiri']);

    $this->get(route('berita.show', $article->slug))
        ->assertSuccessful()
        ->assertDontSee('Sumber:')
        ->assertSee('<link rel="canonical" href="'.e(route('berita.show', $article->slug)).'">', escape: false);
});

it('does not put our byline on someone elses article', function (): void {
    $article = Article::factory()->scraped()->create();

    $this->get(route('berita.show', $article->slug))->assertDontSee('Rizky Adhitama');
});

it('does not claim someone elses photo as ours', function (): void {
    $article = Article::factory()->scraped()->create(['image_url' => 'https://static.tek.id/a.jpeg']);

    $this->get(route('berita.show', $article->slug))->assertDontSee('dokumentasi Bandingin.id');
});

it('keeps our byline on our own article', function (): void {
    $article = Article::factory()->create();

    $this->get(route('berita.show', $article->slug))->assertSee('Rizky Adhitama');
});

it('renders the hero image when there is one', function (): void {
    $article = Article::factory()->scraped()->create(['image_url' => 'https://static.tek.id/a.jpeg']);

    $this->get(route('berita.show', $article->slug))->assertSee('https://static.tek.id/a.jpeg');
});

it('falls back to the placeholder when there is no image', function (): void {
    $article = Article::factory()->create(['image_url' => null]);

    $this->get(route('berita.show', $article->slug))->assertSee('photo-slot');
});

it('shows scraped article images on the berita grid', function (): void {
    // Two articles: the newest becomes the featured hero, so the second is what
    // lands in the grid and exercises x-article-card.
    Article::factory()->scraped()->create([
        'image_url' => 'https://static.tek.id/utama.jpeg',
        'published_at' => now(),
    ]);
    Article::factory()->scraped()->create([
        'image_url' => 'https://static.tek.id/kartu.jpeg',
        'published_at' => now()->subDay(),
    ]);

    $this->get(route('berita'))
        ->assertSee('https://static.tek.id/kartu.jpeg')
        ->assertSee('https://static.tek.id/utama.jpeg');
});

it('lists scraped articles alongside our own', function (): void {
    $ours = Article::factory()->create(['title' => 'Tulisan kami']);
    $theirs = Article::factory()->scraped()->create(['title' => 'Artikel hasil scraping']);

    $this->get(route('berita'))
        ->assertSee($ours->title)
        ->assertSee($theirs->title);
});

it('opens a scraped article at its own url', function (): void {
    $article = Article::factory()->scraped()->create();

    $this->get(route('berita.show', $article->slug))
        ->assertSuccessful()
        ->assertSee($article->title);
});
