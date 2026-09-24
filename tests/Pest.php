<?php

use App\Settings\AdSettings;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\BrandHighlightSeeder;
use Database\Seeders\BrandSeeder;
use Database\Seeders\FaqSeeder;
use Database\Seeders\FeaturedMatchupSeeder;
use Database\Seeders\PhoneSeeder;
use Database\Seeders\SpecDefinitionSeeder;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Frontend pages read the database, so page tests need the catalogue present.
 * This runs the same seeders as production, which keeps assertions that name
 * real records (a56, "lists the whole catalogue") meaningful.
 */
function seedCatalog(): void
{
    test()->seed([
        SpecDefinitionSeeder::class,
        StoreSeeder::class,
        BrandSeeder::class,
        PhoneSeeder::class,
        BrandHighlightSeeder::class,
        ArticleSeeder::class,
        FaqSeeder::class,
        FeaturedMatchupSeeder::class,
    ]);
}

/** The contents of the rendered <title> tag. */
function title(string $html): string
{
    preg_match('/<title>(.*?)<\/title>/s', $html, $matches);

    return trim(html_entity_decode($matches[1] ?? ''));
}

/** The content attribute of a rendered <meta name="..."> tag. */
function metaContent(string $html, string $name): string
{
    preg_match('/<meta name="'.preg_quote($name, '/').'" content="(.*?)">/s', $html, $matches);

    return trim(html_entity_decode($matches[1] ?? ''));
}

/**
 * Every JSON-LD block on a page, keyed by its @type.
 *
 * @return array<string, array<string, mixed>>
 */
function jsonLdBlocks(string $html): array
{
    preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches);

    $blocks = [];

    foreach ($matches[1] as $json) {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $blocks[$decoded['@type']] = $decoded;
    }

    return $blocks;
}

/**
 * Heading levels in document order, e.g. [1, 2, 2, 3].
 *
 * @return list<int>
 */
function headingLevels(string $html): array
{
    preg_match_all('/<h([1-6])[\s>]/i', $html, $matches);

    return array_map(intval(...), $matches[1]);
}

/**
 * Every rendered <img> tag on the page.
 *
 * @return list<string>
 */
function imageTags(string $html): array
{
    preg_match_all('/<img\b[^>]*>/is', $html, $matches);

    return $matches[0];
}

/** Turns ads on for a test, with the knobs the assertion cares about. */
function enableAds(int $frequency = 5, int $cooldownMinutes = 30): void
{
    $settings = app(AdSettings::class);
    $settings->enabled = true;
    $settings->frequency = $frequency;
    $settings->cooldown_minutes = $cooldownMinutes;
    $settings->save();
}
