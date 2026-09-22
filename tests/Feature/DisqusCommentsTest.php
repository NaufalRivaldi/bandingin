<?php

declare(strict_types=1);

use App\Models\Article;

it('leaves comments off until a shortname is configured', function (): void {
    config()->set('services.disqus.shortname', null);

    $article = Article::factory()->create();

    $this->get(route('berita.show', $article->slug))
        ->assertOk()
        ->assertDontSee('disqus_thread')
        ->assertDontSee('disqus.com/embed.js');
});

it('embeds the thread for the configured forum', function (): void {
    config()->set('services.disqus.shortname', 'bandingin-id');

    $article = Article::factory()->create();

    $this->get(route('berita.show', $article->slug))
        ->assertOk()
        ->assertSee('Komentar')
        ->assertSee('disqus_thread')
        ->assertSee('https://bandingin-id.disqus.com/embed.js');
});

it('keys the thread to the article so a retitle keeps its comments', function (): void {
    config()->set('services.disqus.shortname', 'bandingin-id');

    $article = Article::factory()->create();

    $this->get(route('berita.show', $article->slug))
        ->assertSee(json_encode($article->slug), escape: false)
        ->assertSee(json_encode(route('berita.show', $article->slug)), escape: false);
});

it('embeds the thread on a scraped article too', function (): void {
    config()->set('services.disqus.shortname', 'bandingin-id');

    $article = Article::factory()->scraped()->create();

    $this->get(route('berita.show', $article->slug))
        ->assertOk()
        ->assertSee('disqus_thread');
});
