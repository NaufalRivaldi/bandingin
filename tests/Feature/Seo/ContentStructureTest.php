<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\Phone;

beforeEach(function (): void {
    seedCatalog();
});

/** @return list<string> */
function pageUrls(): array
{
    return ['home', 'katalog', 'bandingkan', 'merek', 'berita', 'faq', 'lapor', 'legal', 'cara-menilai', 'sumber-harga'];
}

it('gives every page exactly one h1', function (string $name): void {
    $levels = headingLevels($this->get(route($name))->assertOk()->content());

    expect(array_count_values($levels)[1] ?? 0)->toBe(1);
})->with(pageUrls());

it('opens every page with the h1', function (string $name): void {
    expect(headingLevels($this->get(route($name))->content())[0] ?? null)->toBe(1);
})->with(pageUrls());

it('never skips a heading level', function (string $name): void {
    $levels = headingLevels($this->get(route($name))->content());

    expect($levels)->not->toBeEmpty();

    foreach ($levels as $index => $level) {
        if ($index === 0) {
            continue;
        }

        expect($level)->toBeLessThanOrEqual(max(array_slice($levels, 0, $index)) + 1);
    }
})->with(pageUrls());

it('gives a phone page one h1 naming the phone', function (): void {
    $phone = Phone::published()->firstOrFail();

    $content = $this->get(route('hp.show', $phone->slug))->content();

    expect(array_count_values(headingLevels($content))[1])->toBe(1)
        ->and($content)->toContain($phone->name);
});

it('gives an article page one h1 naming the article', function (): void {
    $article = Article::factory()->create(['title' => 'Judul artikel uji']);

    $content = $this->get(route('berita.show', $article->slug))->content();

    expect(array_count_values(headingLevels($content))[1])->toBe(1)
        ->and($content)->toContain('Judul artikel uji');
});
