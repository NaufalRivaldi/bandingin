# Scraped Articles & Managed Sources Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Scrape full articles from admin-managed sources and publish them as first-class Articles with their own `/berita/{slug}` pages.

**Architecture:** A `scrape_sources` table replaces the hardcoded channel enum: each row is a feed URL plus the `ArticleCategory` its articles become. An hourly command loops the active sources, fetches each feed, and for genuinely new items fetches the article page and extracts a cleaned body with `DOMDocument`. Extracted articles are upserted into the existing `articles` table with provenance columns, and every importer query is scoped `whereNotNull('source')` so it can never touch a hand-written row.

**Tech Stack:** Laravel 13, PHP 8.3+, Livewire 4, Filament 5, Pest 5, SQLite. HTTP via the `Http` facade (Guzzle 7.15). XML via `SimpleXML`, HTML via `DOMDocument`/`DOMXPath` — all built in.

**Spec:** `docs/superpowers/specs/2026-09-22-scraped-articles-design.md`

## Global Constraints

- **No new Composer or npm dependencies.** `SimpleXML`, `DOMDocument` and Guzzle are already available.
- **No new base folders under `app/`.** `app/News/` and `app/Actions/News/` already exist.
- **`App\Support` and `App\Data` are forbidden** — `tests/Feature/StaticLayerRetiredTest.php` fails the build on any reference.
- **`declare(strict_types=1);` in every new PHP file.** Pint's preset does not add it.
- **Run `vendor/bin/pint --dirty --format agent` after editing any PHP file**, before every commit.
- **The full suite needs a raised memory limit.** `composer test` dies at the 128M default (pre-existing, unrelated to this work). Use `php -d memory_limit=2G vendor/bin/pest --compact` for full runs; `php artisan test --compact --filter=X` is fine for single files.
- **The importer only ever reads, creates or updates `articles` rows where `source` is NOT NULL.** A hand-written article must be untouchable by the scraper. This is the single most important invariant in the feature.
- **A scraped article with no extractable body is never stored.** Unlike a missing image, a missing body makes the article worthless; skipping it means the next run retries.
- **App timezone is UTC; tek.id timestamps are Asia/Jakarta with no offset.** Always `CarbonImmutable::createFromFormat('Y-m-d H:i:s', $raw, 'Asia/Jakarta')->utc()`.
- **Filament is v5.** Resources live in `app/Filament/Resources/<Plural>/` with `Pages/`, `Schemas/`, `Tables/`. Forms take and return `Filament\Schemas\Schema`. Actions come from `Filament\Actions\*`. Panel tests use `Livewire::test()`, never `Pest\Livewire\livewire()`.
- **`tests/Feature/Filament/ResourcePagesTest.php` asserts the registered resource list exhaustively.** Removing `NewsItemResource` and adding `ScrapeSourceResource` both require editing `creatableResources()` / `listableResources()` there, or the suite fails.
- **Admin code must never import from `App\Livewire`, and vice versa** — `AdminBoundaryTest` enforces both directions.
- **Blade:** use the `resources/css/app.css` theme tokens (`ink`, `muted`, `faint`, `line`, `brand`, `surface`, `.photo-slot`). Each Livewire view needs exactly one root element.
- **Indonesian UI copy** throughout.
- **Use `php artisan make:` where a generator exists**, with `--no-interaction`.

---

### Task 1: Retire the link-out layer

The previous feature rendered scraped items as cards linking off-site. That is being replaced wholesale, so it is removed first to leave a clean base — building both side by side would mean two pipelines writing overlapping data.

**Files:**
- Delete: `app/Models/NewsItem.php`, `database/factories/NewsItemFactory.php`
- Delete: `app/Livewire/NewsFeed.php`, `resources/views/livewire/news-feed.blade.php`, `resources/views/components/news-card.blade.php`
- Delete: `app/Filament/Resources/NewsItems/` (whole directory)
- Delete: `app/Console/Commands/ImportTekNews.php`
- Delete: `app/Actions/News/ImportScrapedArticles.php`, `app/Actions/News/FetchArticleImage.php`
- Delete: `tests/Feature/NewsItemModelTest.php`, `tests/Feature/NewsFeedTest.php`, `tests/Feature/Filament/NewsItemResourceTest.php`, `tests/Feature/News/ImportScrapedArticlesTest.php`, `tests/Feature/News/FetchArticleImageTest.php`, `tests/Feature/News/ImportTekNewsCommandTest.php`
- Create: `database/migrations/<timestamp>_drop_news_items_table.php`
- Modify: `resources/views/livewire/berita-page.blade.php` (remove `<livewire:news-feed />`)
- Modify: `routes/console.php` (remove the `ImportTekNews` schedule)
- Modify: `tests/Feature/Filament/ResourcePagesTest.php` (remove `NewsItemResource`)
- Modify: `tests/Feature/BeritaPageTest.php` (remove the four news-feed tests)

**Kept deliberately:** `app/News/ScrapedArticle.php`, `app/News/TekChannel.php`, `app/Actions/News/FetchTekChannel.php` and `tests/Feature/News/{TekChannelTest,FetchTekChannelTest}.php`. Task 4 converts these rather than rewriting them from scratch. `app/Models/Concerns/FormatsIndonesianDate.php` stays — `Article` uses it.

- [ ] **Step 1: Delete the files**

```bash
cd /Users/winar/Herd/bandingin
rm -f app/Models/NewsItem.php database/factories/NewsItemFactory.php
rm -f app/Livewire/NewsFeed.php resources/views/livewire/news-feed.blade.php resources/views/components/news-card.blade.php
rm -rf app/Filament/Resources/NewsItems
rm -f app/Console/Commands/ImportTekNews.php
rm -f app/Actions/News/ImportScrapedArticles.php app/Actions/News/FetchArticleImage.php
rm -f tests/Feature/NewsItemModelTest.php tests/Feature/NewsFeedTest.php
rm -f tests/Feature/Filament/NewsItemResourceTest.php
rm -f tests/Feature/News/ImportScrapedArticlesTest.php tests/Feature/News/FetchArticleImageTest.php tests/Feature/News/ImportTekNewsCommandTest.php
```

- [ ] **Step 2: Drop the table**

```bash
php artisan make:migration drop_news_items_table --no-interaction
```

Replace the generated file's body:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('news_items');
    }

    /**
     * Recreates the shape the link-out feature used. The rows are not
     * recoverable — the owner chose to start fresh rather than backfill.
     */
    public function down(): void
    {
        Schema::create('news_items', function (Blueprint $table): void {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('source')->default('tek.id');
            $table->string('channel');
            $table->string('title');
            $table->text('excerpt');
            $table->string('url')->unique();
            $table->string('image_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'published_at']);
            $table->index('published_at');
        });
    }
};
```

- [ ] **Step 3: Remove the feed from the Berita page**

In `resources/views/livewire/berita-page.blade.php`, delete these three lines from inside the `@if ($this->showsFeatured)` block (leaving the featured `</section>` and the `@endif`):

```blade

        <livewire:news-feed />
```

- [ ] **Step 4: Remove the schedule registration**

Replace `routes/console.php` with:

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
```

- [ ] **Step 5: Remove NewsItemResource from the resource guard**

In `tests/Feature/Filament/ResourcePagesTest.php`:

1. Delete the line `use App\Filament\Resources\NewsItems\NewsItemResource;`
2. Delete the line `        'news items' => [NewsItemResource::class],` from `listableResources()`
3. Restore that function's docblock to read:

```php
/**
 * Every resource, including the two fed by the public site rather than the
 * panel — reports arrive from /lapor and subscribers from the footer form.
 *
 * @return array<string, array{class-string}>
 */
```

- [ ] **Step 6: Remove the news-feed tests from BeritaPageTest**

In `tests/Feature/BeritaPageTest.php`, delete these four tests in full, plus the `articleTallyOnBeritaPage()` helper function they use:

- `it('shows aggregated news in its own section, not the article grid', ...)`
- `it('places aggregated news above the article grid', ...)`
- `it('hides aggregated news when a category filter is active', ...)`
- `it('keeps aggregated news out of the article count', ...)`

Also delete the now-unused `use App\Models\NewsItem;` import if present.

- [ ] **Step 7: Migrate and run the suite**

```bash
php artisan migrate --no-interaction
php -d memory_limit=2G vendor/bin/pest --compact
```

Expected: PASS. The count drops by roughly 37 tests. If anything still references `NewsItem` or `news-card`, the failure will name the file — fix it and re-run.

- [ ] **Step 8: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "refactor: retire the link-out news layer

Scraped content becomes first-class Articles instead, so the separate
news_items table, its feed component, card and admin resource are removed.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 2: Scrape sources table, model and seeder

**Files:**
- Create: `database/migrations/<timestamp>_create_scrape_sources_table.php`
- Create: `app/Models/ScrapeSource.php`
- Create: `database/factories/ScrapeSourceFactory.php`
- Create: `database/seeders/ScrapeSourceSeeder.php`
- Test: `tests/Feature/ScrapeSourceModelTest.php`

**Interfaces:**
- Consumes: `App\Enums\ArticleCategory` (existing: `Rilis`, `Harga`, `Panduan`, `Ulasan`).
- Produces: `App\Models\ScrapeSource` with `name`, `feed_url`, `category` (cast to `ArticleCategory`), `is_active` (bool), `last_run_at` (datetime, nullable), `last_error` (string, nullable); `scopeActive(Builder $query): void`; and `markRan(?string $error = null): void` which stamps `last_run_at = now()` and `last_error`.

- [ ] **Step 1: Generate the scaffolding**

```bash
php artisan make:model ScrapeSource -mf --no-interaction
php artisan make:seeder ScrapeSourceSeeder --no-interaction
```

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/ScrapeSourceModelTest.php`:

```php
<?php

declare(strict_types=1);

use App\Enums\ArticleCategory;
use App\Models\ScrapeSource;
use Database\Seeders\ScrapeSourceSeeder;

it('casts its category to the article category enum', function (): void {
    $source = ScrapeSource::factory()->create(['category' => ArticleCategory::Ulasan]);

    expect($source->fresh()->category)->toBe(ArticleCategory::Ulasan);
});

it('defaults to active', function (): void {
    expect(ScrapeSource::factory()->create()->is_active)->toBeTrue();
});

it('scopes to active sources', function (): void {
    ScrapeSource::factory()->count(2)->create();
    ScrapeSource::factory()->inactive()->create();

    expect(ScrapeSource::active()->count())->toBe(2);
});

it('rejects a duplicate feed url', function (): void {
    ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);

    expect(fn () => ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

it('stamps a successful run and clears the previous error', function (): void {
    $source = ScrapeSource::factory()->create(['last_error' => 'kesalahan lama']);

    $source->markRan();

    expect($source->fresh()->last_run_at)->not->toBeNull()
        ->and($source->fresh()->last_error)->toBeNull();
});

it('stamps a failed run with its reason', function (): void {
    $source = ScrapeSource::factory()->create();

    $source->markRan('feed tidak valid');

    expect($source->fresh()->last_error)->toBe('feed tidak valid')
        ->and($source->fresh()->last_run_at)->not->toBeNull();
});

it('seeds one source per tek.id channel', function (): void {
    (new ScrapeSourceSeeder)->run();

    expect(ScrapeSource::count())->toBe(7)
        ->and(ScrapeSource::where('feed_url', 'https://www.tek.id/review/rss')->first()->category)
        ->toBe(ArticleCategory::Ulasan);
});

it('seeds idempotently', function (): void {
    (new ScrapeSourceSeeder)->run();
    (new ScrapeSourceSeeder)->run();

    expect(ScrapeSource::count())->toBe(7);
});
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `php artisan test --compact --filter=ScrapeSourceModelTest`
Expected: FAIL — the `scrape_sources` table has no columns yet.

- [ ] **Step 4: Write the migration**

Replace the generated `..._create_scrape_sources_table.php` body:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrape_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('feed_url')->unique();
            $table->string('category');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrape_sources');
    }
};
```

- [ ] **Step 5: Write the model**

Replace `app/Models/ScrapeSource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleCategory;
use Database\Factories\ScrapeSourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One external feed to scrape, plus the category its articles become.
 *
 * This is what replaced the hardcoded channel enum: adding a site is a row,
 * not a deploy, and the feed-to-category mapping is the owner's decision
 * rather than a guess in code.
 */
class ScrapeSource extends Model
{
    /** @use HasFactory<ScrapeSourceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'feed_url',
        'category',
        'is_active',
        'last_run_at',
        'last_error',
    ];

    /** @param  Builder<$this>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** Records the outcome of a run so the admin table shows feed health. */
    public function markRan(?string $error = null): void
    {
        $this->forceFill([
            'last_run_at' => now(),
            'last_error' => $error,
        ])->save();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => ArticleCategory::class,
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 6: Write the factory**

Replace `database/factories/ScrapeSourceFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ArticleCategory;
use App\Models\ScrapeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScrapeSource>
 */
class ScrapeSourceFactory extends Factory
{
    protected $model = ScrapeSource::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $slug = fake()->unique()->slug(1);

        return [
            'name' => 'tek.id — '.ucfirst($slug),
            'feed_url' => 'https://www.tek.id/'.$slug.'/rss',
            'category' => ArticleCategory::Rilis,
            'is_active' => true,
            'last_run_at' => null,
            'last_error' => null,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
```

- [ ] **Step 7: Write the seeder**

Replace `database/seeders/ScrapeSourceSeeder.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ArticleCategory;
use App\Models\ScrapeSource;
use Illuminate\Database\Seeder;

class ScrapeSourceSeeder extends Seeder
{
    /**
     * tek.id's seven channels mapped onto our four categories.
     *
     * The mapping is a starting point, not a rule — it is editable in the
     * admin panel, which is the whole point of sources being data.
     *
     * @var list<array{string, string, ArticleCategory}>
     */
    private const array SOURCES = [
        ['tek.id — Tek', 'tek', ArticleCategory::Rilis],
        ['tek.id — Gadget', 'gadget', ArticleCategory::Rilis],
        ['tek.id — Review', 'review', ArticleCategory::Ulasan],
        ['tek.id — Culture', 'culture', ArticleCategory::Panduan],
        ['tek.id — Future', 'future', ArticleCategory::Rilis],
        ['tek.id — Insight', 'insight', ArticleCategory::Panduan],
        ['tek.id — Tektalk', 'tektalk', ArticleCategory::Panduan],
    ];

    public function run(): void
    {
        foreach (self::SOURCES as [$name, $path, $category]) {
            ScrapeSource::updateOrCreate(
                ['feed_url' => 'https://www.tek.id/'.$path.'/rss'],
                ['name' => $name, 'category' => $category],
            );
        }
    }
}
```

`updateOrCreate` keyed on `feed_url` is what makes re-seeding idempotent and preserves any category the owner has since changed... except for `name` and `category`, which it resets. That is intentional: re-running a seeder is a reset to defaults.

- [ ] **Step 8: Register the seeder**

In `database/seeders/DatabaseSeeder.php`, add `ScrapeSourceSeeder::class` to the `$this->call([...])` list. If that file calls seeders individually, follow whichever pattern is already there.

- [ ] **Step 9: Migrate and run the tests**

```bash
php artisan migrate --no-interaction
php artisan test --compact --filter=ScrapeSourceModelTest
```

Expected: PASS, 7 tests.

- [ ] **Step 10: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add admin-managed scrape sources

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 3: Article provenance columns

**Files:**
- Create: `database/migrations/<timestamp>_add_source_columns_to_articles_table.php`
- Modify: `app/Models/Article.php`
- Modify: `database/factories/ArticleFactory.php`
- Test: `tests/Feature/ArticleProvenanceTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `articles` gains nullable `source`, `source_url` (unique), `external_id` (unique), `image_url`. `Article` gains them to `$fillable`, plus `isScraped(): bool` (true when `source` is not null) and `scopeScraped(Builder $query): void` / `scopeOwn(Builder $query): void`. `ArticleFactory` gains a `scraped()` state.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ArticleProvenanceTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Article;

it('treats an article with no source as our own', function (): void {
    $article = Article::factory()->create();

    expect($article->source)->toBeNull()
        ->and($article->isScraped())->toBeFalse();
});

it('treats an article with a source as scraped', function (): void {
    $article = Article::factory()->scraped()->create();

    expect($article->source)->toBe('tek.id')
        ->and($article->isScraped())->toBeTrue()
        ->and($article->source_url)->toStartWith('https://www.tek.id/');
});

it('scopes to scraped and to our own articles', function (): void {
    Article::factory()->count(2)->create();
    Article::factory()->scraped()->count(3)->create();

    expect(Article::own()->count())->toBe(2)
        ->and(Article::scraped()->count())->toBe(3);
});

it('rejects a duplicate external id', function (): void {
    Article::factory()->scraped()->create(['external_id' => 'b2qbp9uhh']);

    expect(fn () => Article::factory()->scraped()->create(['external_id' => 'b2qbp9uhh']))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

it('allows many of our own articles to share a null external id', function (): void {
    Article::factory()->count(3)->create();

    expect(Article::own()->count())->toBe(3);
});

it('stores a hero image url', function (): void {
    $article = Article::factory()->scraped()->create(['image_url' => 'https://static.tek.id/a.jpeg']);

    expect($article->fresh()->image_url)->toBe('https://static.tek.id/a.jpeg');
});
```

The fifth test matters: SQLite and MySQL both allow multiple NULLs in a unique column, which is exactly what lets every hand-written article leave `external_id` empty. If that assumption ever breaks, this test catches it.

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact --filter=ArticleProvenanceTest`
Expected: FAIL — `Call to undefined method App\Models\Article::isScraped()` or a missing-column error.

- [ ] **Step 3: Write the migration**

```bash
php artisan make:migration add_source_columns_to_articles_table --no-interaction
```

Replace its body:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            // Null source means a human wrote it. The importer is scoped to
            // rows where this is set and can never touch the others.
            $table->string('source')->nullable()->after('published_at');
            $table->string('source_url')->nullable()->unique()->after('source');
            $table->string('external_id')->nullable()->unique()->after('source_url');
            $table->string('image_url')->nullable()->after('excerpt');

            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropIndex(['source']);
            $table->dropUnique(['source_url']);
            $table->dropUnique(['external_id']);
            $table->dropColumn(['source', 'source_url', 'external_id', 'image_url']);
        });
    }
};
```

- [ ] **Step 4: Update the model**

In `app/Models/Article.php`:

1. Add to `$fillable`, after `'published_at'`: `'source'`, `'source_url'`, `'external_id'`, `'image_url'`
2. Add these three methods after `scopePublished()`:

```php
    /** @param  Builder<$this>  $query */
    public function scopeScraped(Builder $query): void
    {
        $query->whereNotNull('source');
    }

    /** @param  Builder<$this>  $query */
    public function scopeOwn(Builder $query): void
    {
        $query->whereNull('source');
    }

    /** Scraped articles carry a credit line and a canonical tag; ours do not. */
    public function isScraped(): bool
    {
        return $this->source !== null;
    }
```

- [ ] **Step 5: Add the factory state**

In `database/factories/ArticleFactory.php`, add after `draft()`:

```php
    public function scraped(): self
    {
        return $this->state(function (): array {
            $externalId = 'b2'.Str::random(7);

            return [
                'source' => 'tek.id',
                'source_url' => 'https://www.tek.id/gadget/'.fake()->slug(4).'-'.$externalId,
                'external_id' => $externalId,
                'image_url' => 'https://static.tek.id/2026/09/21/'.fake()->slug(2).'.jpeg',
            ];
        });
    }
```

Add `use Illuminate\Support\Str;` to the file's imports.

- [ ] **Step 6: Run the tests**

```bash
php artisan migrate --no-interaction
php artisan test --compact --filter="ArticleProvenanceTest|ArticleModelTest|ArticleAccessorsTest"
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add provenance and image columns to articles

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 4: FetchFeed — drive the parser from a source row

Converts the existing `FetchTekChannel` to take a `ScrapeSource` instead of the hardcoded enum, and drops `channel` from the DTO — the importer already knows which source it is iterating, so carrying it on every item was redundant.

**Files:**
- Create: `app/Actions/News/FetchFeed.php`
- Delete: `app/Actions/News/FetchTekChannel.php`, `app/News/TekChannel.php`, `tests/Feature/News/TekChannelTest.php`, `tests/Feature/News/FetchTekChannelTest.php`
- Modify: `app/News/ScrapedArticle.php`
- Create: `tests/Feature/News/FetchFeedTest.php`
- Create: `tests/Feature/News/ScrapedArticleTest.php`

**Interfaces:**
- Consumes: `App\Models\ScrapeSource` (Task 2).
- Produces:
  - `App\News\ScrapedArticle` — `final readonly`, promoted properties `string $externalId`, `string $title`, `string $excerpt`, `string $url`, `CarbonImmutable $publishedAt`, `?string $body = null`, `?string $imageUrl = null`. Method `withContent(?string $body, ?string $imageUrl): self`.
  - `App\Actions\News\FetchFeed` — `handle(ScrapeSource $source): array` returning `list<ScrapedArticle>`. Throws `RuntimeException` on unparseable XML; lets `RequestException` propagate on a non-2xx.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/News/ScrapedArticleTest.php`:

```php
<?php

declare(strict_types=1);

use App\News\ScrapedArticle;
use Carbon\CarbonImmutable;

it('returns a new instance when content is attached', function (): void {
    $article = new ScrapedArticle(
        externalId: 'b2qbp9uhh',
        title: 'realme 16 Pro Harry Potter Edition',
        excerpt: 'Meluncur di Indonesia.',
        url: 'https://www.tek.id/gadget/realme-b2qbp9uhh',
        publishedAt: CarbonImmutable::parse('2026-09-21 12:18:00'),
    );

    $filled = $article->withContent('<p>Isi artikel.</p>', 'https://static.tek.id/a.jpeg');

    expect($article->body)->toBeNull()
        ->and($article->imageUrl)->toBeNull()
        ->and($filled->body)->toBe('<p>Isi artikel.</p>')
        ->and($filled->imageUrl)->toBe('https://static.tek.id/a.jpeg')
        ->and($filled->externalId)->toBe('b2qbp9uhh')
        ->and($filled->title)->toBe('realme 16 Pro Harry Potter Edition');
});
```

Create `tests/Feature/News/FetchFeedTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\News\FetchFeed;
use App\Models\ScrapeSource;
use Illuminate\Support\Facades\Http;

function gadgetSource(): ScrapeSource
{
    return ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);
}

function fakeGadgetFeed(): void
{
    Http::fake([
        'www.tek.id/gadget/rss' => Http::response(
            file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')),
            200,
            ['Content-Type' => 'application/rss+xml'],
        ),
    ]);
}

it('parses every well-formed item in the feed', function (): void {
    fakeGadgetFeed();

    expect(app(FetchFeed::class)->handle(gadgetSource()))->toHaveCount(3);
});

it('maps an item onto a scraped article', function (): void {
    fakeGadgetFeed();

    $article = app(FetchFeed::class)->handle(gadgetSource())[0];

    expect($article->externalId)->toBe('b2qbp9uhh')
        ->and($article->title)->toBe('realme 16 Pro Harry Potter Edition Resmi Meluncur, Ini Harga dan Spesifikasinya')
        ->and($article->excerpt)->toStartWith('realme 16 Pro Harry Potter Edition meluncur di Indonesia')
        ->and($article->url)->toBe('https://www.tek.id/gadget/realme-16-pro-harry-potter-edition-meluncur-ini-harganya-b2qbp9uhh')
        ->and($article->body)->toBeNull()
        ->and($article->imageUrl)->toBeNull();
});

it('reads the feed timestamp as Jakarta time and stores it as UTC', function (): void {
    fakeGadgetFeed();

    $article = app(FetchFeed::class)->handle(gadgetSource())[0];

    // Feed says 2026-09-21 19:18:00 with no offset; Jakarta is UTC+7.
    expect($article->publishedAt->toDateTimeString())->toBe('2026-09-21 12:18:00')
        ->and($article->publishedAt->timezoneName)->toBe('UTC');
});

it('decodes html entities in the excerpt', function (): void {
    fakeGadgetFeed();

    expect(app(FetchFeed::class)->handle(gadgetSource())[1]->excerpt)->toContain('Bang & Olufsen');
});

it('skips an item with no link rather than failing the feed', function (): void {
    fakeGadgetFeed();

    $ids = array_map(
        fn ($article): string => $article->externalId,
        app(FetchFeed::class)->handle(gadgetSource()),
    );

    expect($ids)->toBe(['b2qbp9uhh', 'b2qbb9ugd', 'b2qaY9ufR']);
});

it('uses the url from the source row', function (): void {
    Http::fake([
        'example.test/feed' => Http::response(
            file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')),
            200,
        ),
    ]);

    $source = ScrapeSource::factory()->create(['feed_url' => 'https://example.test/feed']);

    expect(app(FetchFeed::class)->handle($source))->toHaveCount(3);

    Http::assertSent(fn ($request): bool => $request->url() === 'https://example.test/feed');
});

it('throws when the response body is not xml', function (): void {
    Http::fake(['www.tek.id/gadget/rss' => Http::response('{"error":"maintenance"}', 200)]);

    expect(fn () => app(FetchFeed::class)->handle(gadgetSource()))
        ->toThrow(RuntimeException::class);
});

it('throws when the feed responds with a server error', function (): void {
    Http::fake(['www.tek.id/gadget/rss' => Http::response('', 500)]);

    expect(fn () => app(FetchFeed::class)->handle(gadgetSource()))
        ->toThrow(Illuminate\Http\Client\RequestException::class);
});

it('returns an empty list for a feed with no items', function (): void {
    Http::fake([
        'www.tek.id/gadget/rss' => Http::response(
            '<?xml version="1.0"?><rss version="2.0"><channel><title>Kosong</title></channel></rss>',
            200,
        ),
    ]);

    expect(app(FetchFeed::class)->handle(gadgetSource()))->toBe([]);
});

it('sends an identifying user agent', function (): void {
    fakeGadgetFeed();

    app(FetchFeed::class)->handle(gadgetSource());

    Http::assertSent(fn ($request): bool => str_contains($request->header('User-Agent')[0], 'BandinginBot'));
});
```

- [ ] **Step 2: Delete the superseded files and run the tests to verify they fail**

```bash
rm -f app/Actions/News/FetchTekChannel.php app/News/TekChannel.php
rm -f tests/Feature/News/TekChannelTest.php tests/Feature/News/FetchTekChannelTest.php
php artisan test --compact --filter="FetchFeedTest|ScrapedArticleTest"
```

Expected: FAIL — `Target class [App\Actions\News\FetchFeed] does not exist.`

- [ ] **Step 3: Update the DTO**

Replace `app/News/ScrapedArticle.php`:

```php
<?php

declare(strict_types=1);

namespace App\News;

use Carbon\CarbonImmutable;

/**
 * One normalised item from a source feed, optionally carrying the article body
 * and hero image once the article page has been fetched.
 *
 * $publishedAt is always UTC — the feed's Jakarta-local timestamp is converted
 * at the point of parsing, never here.
 */
final readonly class ScrapedArticle
{
    public function __construct(
        public string $externalId,
        public string $title,
        public string $excerpt,
        public string $url,
        public CarbonImmutable $publishedAt,
        public ?string $body = null,
        public ?string $imageUrl = null,
    ) {}

    public function withContent(?string $body, ?string $imageUrl): self
    {
        return new self(
            externalId: $this->externalId,
            title: $this->title,
            excerpt: $this->excerpt,
            url: $this->url,
            publishedAt: $this->publishedAt,
            body: $body,
            imageUrl: $imageUrl,
        );
    }
}
```

- [ ] **Step 4: Write the action**

Create `app/Actions/News/FetchFeed.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\News;

use App\Models\ScrapeSource;
use App\News\ScrapedArticle;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;

/**
 * Fetches and parses one source's RSS feed.
 *
 * Touches no database beyond reading the source row, so it is testable with
 * Http::fake() alone. A whole-feed problem throws and lets the caller fail that
 * source; a single malformed item is skipped so one bad entry cannot cost us
 * the other nine.
 */
final class FetchFeed
{
    /** @return list<ScrapedArticle> */
    public function handle(ScrapeSource $source): array
    {
        $body = Http::withUserAgent((string) config('services.tek.user_agent'))
            ->timeout(15)
            ->retry(2, 500)
            ->get($source->feed_url)
            ->throw()
            ->body();

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            throw new RuntimeException(
                "Feed {$source->name} is not valid XML (".strlen($body).' bytes).'
            );
        }

        $articles = [];

        foreach ($xml->channel->item as $item) {
            $article = $this->toArticle($item);

            if ($article instanceof ScrapedArticle) {
                $articles[] = $article;
            }
        }

        return $articles;
    }

    private function toArticle(SimpleXMLElement $item): ?ScrapedArticle
    {
        $url = trim((string) $item->link);
        $title = trim((string) $item->title);
        $externalId = $this->externalId($url);
        $publishedAt = $this->publishedAt(trim((string) $item->pubDate));

        if ($title === '' || $externalId === null || ! $publishedAt instanceof CarbonImmutable) {
            return null;
        }

        return new ScrapedArticle(
            externalId: $externalId,
            title: html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            excerpt: html_entity_decode(trim((string) $item->description), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            url: $url,
            publishedAt: $publishedAt,
        );
    }

    /**
     * tek.id URLs end in a stable token ("...-b2qbp9uhh") that survives edits to
     * the title slug in front of it. That token, not the URL, is our identity.
     */
    private function externalId(string $url): ?string
    {
        if ($url === '' || ! str_contains($url, '-')) {
            return null;
        }

        $token = Str::afterLast($url, '-');

        return preg_match('/^[A-Za-z0-9]{6,}$/', $token) === 1 ? $token : null;
    }

    /** The feed emits Jakarta local time with no offset. Normalise to UTC here. */
    private function publishedAt(string $raw): ?CarbonImmutable
    {
        if ($raw === '') {
            return null;
        }

        try {
            $parsed = CarbonImmutable::createFromFormat('Y-m-d H:i:s', $raw, 'Asia/Jakarta');
        } catch (InvalidFormatException) {
            return null;
        }

        return $parsed instanceof CarbonImmutable ? $parsed->utc() : null;
    }
}
```

- [ ] **Step 5: Run the tests**

Run: `php artisan test --compact --filter="FetchFeedTest|ScrapedArticleTest"`
Expected: PASS, 11 tests.

- [ ] **Step 6: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "refactor: drive feed parsing from a source row

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 5: FetchArticleContent — extract a clean body

The riskiest unit in the feature. Two properties of tek.id's markup make naive extraction wrong, both verified against a live page:

1. **The `<p>` tags are never closed.** `DOMDocument` repairs this while parsing — which is the reason to use it over a regex.
2. **Ads and a related-articles widget sit inline, mid-body.** A `div.bacajuga` "Baca Juga" block and `flying_carpet` / `div-gpt-ad` containers appear between paragraphs.

Point 2 cannot be deferred to the renderer. `RichContentRenderer` unwraps unknown elements rather than dropping them, so `<div class="bacajuga">Baca Juga</div>` renders as the bare text `Baca Juga` inside the published article. It must be removed here.

The renderer *does* handle safety: it strips `<script>`, `onclick` and `javascript:` hrefs, so no sanitiser is needed in this action.

**Files:**
- Create: `tests/Fixtures/tek-article.html`
- Create: `app/News/ArticleContent.php`
- Create: `app/Actions/News/FetchArticleContent.php`
- Test: `tests/Feature/News/FetchArticleContentTest.php`

**Interfaces:**
- Consumes: nothing from earlier tasks.
- Produces:
  - `App\News\ArticleContent` — `final readonly`, promoted `string $body`, `?string $imageUrl`.
  - `App\Actions\News\FetchArticleContent` — `handle(string $url): ?ArticleContent`. Returns null when the page cannot be fetched or has no extractable body. Never throws.

- [ ] **Step 1: Save a real article fixture**

```bash
curl -sSL -A "Mozilla/5.0" \
  "https://www.tek.id/gadget/realme-16-pro-harry-potter-edition-meluncur-ini-harganya-b2qbp9uhh" \
  -o tests/Fixtures/tek-article.html
```

Verify it captured the right page — all four must print a non-zero count:

```bash
for s in 'artikel__content' 'bacajuga' 'og:image' 'artikel__imagecontent'; do
  printf '%-22s %s\n' "$s" "$(grep -c "$s" tests/Fixtures/tek-article.html)"
done
```

If any is `0`, the article has rotated or the page changed shape — pick another URL from `https://www.tek.id/gadget/rss` and retry. The fixture must contain a `bacajuga` block, or the junk-stripping test proves nothing.

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/News/FetchArticleContentTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\News\FetchArticleContent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function fakeArticlePage(): void
{
    Http::fake(['*' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-article.html')), 200)]);
}

it('extracts the article body', function (): void {
    fakeArticlePage();

    $content = app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh');

    expect($content)->not->toBeNull()
        ->and($content->body)->toContain('<p>')
        ->and(strlen($content->body))->toBeGreaterThan(1000);
});

it('strips the ads and the related-articles widget', function (): void {
    fakeArticlePage();

    $body = app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh')->body;

    // RichContentRenderer unwraps unknown elements rather than dropping them,
    // so anything left here would surface as stray text in the published article.
    expect($body)->not->toContain('Baca Juga')
        ->and($body)->not->toContain('bacajuga')
        ->and($body)->not->toContain('flying_carpet')
        ->and($body)->not->toContain('div-gpt')
        ->and($body)->not->toContain('googletag')
        ->and($body)->not->toContain('<script')
        ->and($body)->not->toContain('<style');
});

it('normalises runaway whitespace', function (): void {
    fakeArticlePage();

    $body = app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh')->body;

    expect($body)->not->toMatch('/\s{10,}/');
});

it('reads the hero image', function (): void {
    fakeArticlePage();

    $content = app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh');

    expect($content->imageUrl)->toStartWith('https://static.tek.id/');
});

it('returns null when the page has no article body', function (): void {
    Http::fake(['*' => Http::response('<html><head></head><body><p>kosong</p></body></html>', 200)]);

    expect(app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('returns null when the extracted body is only junk', function (): void {
    Http::fake(['*' => Http::response(
        '<html><body><div class="artikel__content"><div class="bacajuga">Baca Juga</div></div></body></html>',
        200,
    )]);

    expect(app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('returns null on a 404', function (): void {
    Http::fake(['*' => Http::response('Not Found', 404)]);

    expect(app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('returns null when the connection fails', function (): void {
    Http::fake(fn () => throw new ConnectionException('timed out'));

    expect(app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('survives a page with no hero image', function (): void {
    Http::fake(['*' => Http::response(
        '<html><body><div class="artikel__content"><p>'.str_repeat('Isi artikel yang cukup panjang. ', 20).'</p></div></body></html>',
        200,
    )]);

    $content = app(FetchArticleContent::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh');

    expect($content)->not->toBeNull()
        ->and($content->imageUrl)->toBeNull();
});
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `php artisan test --compact --filter=FetchArticleContentTest`
Expected: FAIL — `Target class [App\Actions\News\FetchArticleContent] does not exist.`

- [ ] **Step 4: Write the content DTO**

Create `app/News/ArticleContent.php`:

```php
<?php

declare(strict_types=1);

namespace App\News;

/** The publishable part of a scraped article page. */
final readonly class ArticleContent
{
    public function __construct(
        public string $body,
        public ?string $imageUrl = null,
    ) {}
}
```

- [ ] **Step 5: Write the action**

Create `app/Actions/News/FetchArticleContent.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\News;

use App\News\ArticleContent;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Extracts the publishable body and hero image from one tek.id article page.
 *
 * Selectors are tek.id-specific. They move onto the source row when a second
 * site with different markup is actually added, not before.
 *
 * Every failure yields null, and the importer refuses to store a body-less
 * article — so a failed extraction retries on the next run instead of
 * publishing an empty page.
 */
final class FetchArticleContent
{
    /** Shortest body worth publishing, in characters of visible text. */
    private const int MIN_TEXT_LENGTH = 200;

    /**
     * Ads, share widgets and the "Baca Juga" related-articles block, all of
     * which tek.id injects inline between paragraphs.
     */
    private const string JUNK_XPATH = './/div[contains(@class,"bacajuga")]'
        .' | .//div[contains(@class,"flying_carpet")]'
        .' | .//section[contains(@class,"flying_carpet")]'
        .' | .//div[contains(@id,"div-gpt-ad")]'
        .' | .//div[contains(@class,"sharethis")]'
        .' | .//script | .//style | .//iframe | .//ins | .//noscript';

    public function handle(string $url): ?ArticleContent
    {
        try {
            $response = Http::withUserAgent((string) config('services.tek.user_agent'))
                ->timeout(15)
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $xpath = $this->parse($response->body());

        if (! $xpath instanceof DOMXPath) {
            return null;
        }

        $body = $this->body($xpath);

        return $body === null ? null : new ArticleContent($body, $this->heroImage($xpath));
    }

    private function parse(string $html): ?DOMXPath
    {
        if (trim($html) === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument();
        // The meta hint forces UTF-8; tek.id's markup is not always declared.
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8">'.$html,
            LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? new DOMXPath($document) : null;
    }

    private function body(DOMXPath $xpath): ?string
    {
        $content = $xpath->query('//div[contains(@class,"artikel__content")]')->item(0);

        if (! $content instanceof DOMElement) {
            return null;
        }

        foreach (iterator_to_array($xpath->query(self::JUNK_XPATH, $content)) as $junk) {
            $junk->parentNode?->removeChild($junk);
        }

        $html = '';

        foreach ($content->childNodes as $child) {
            $html .= $content->ownerDocument->saveHTML($child);
        }

        $html = $this->normaliseWhitespace($html);

        $textLength = mb_strlen(trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));

        return $textLength >= self::MIN_TEXT_LENGTH ? $html : null;
    }

    /** tek.id's source is heavily indented; left alone it bloats every row. */
    private function normaliseWhitespace(string $html): string
    {
        $html = preg_replace('/\s+/u', ' ', $html) ?? $html;
        $html = str_replace('> <', '><', $html);

        return trim($html);
    }

    private function heroImage(DOMXPath $xpath): ?string
    {
        $image = $xpath->query('//div[contains(@class,"artikel__imagecontent")]//img/@src')->item(0);
        $source = $image?->nodeValue;

        if ($source === null || $source === '') {
            // Fall back to the social card, which every article page carries.
            $source = $xpath->query('//meta[@property="og:image"]/@content')->item(0)?->nodeValue;
        }

        return is_string($source) && str_starts_with($source, 'http') ? $source : null;
    }
}
```

- [ ] **Step 6: Run the tests**

Run: `php artisan test --compact --filter=FetchArticleContentTest`
Expected: PASS, 9 tests.

If "strips the ads" fails, print the extracted body and look at what survived — then widen `JUNK_XPATH` for that element and re-run. Do not weaken the assertion.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: extract clean article bodies from source pages

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 6: ImportScrapedArticles — write into articles

**Files:**
- Create: `app/Actions/News/ImportScrapedArticles.php`
- Test: `tests/Feature/News/ImportScrapedArticlesTest.php`

**Interfaces:**
- Consumes: `ScrapedArticle` (Task 4), `ScrapeSource` (Task 2), `Article` with provenance columns (Task 3).
- Produces: `App\Actions\News\ImportScrapedArticles` with
  - `handle(array $articles, ScrapeSource $source): int` — upserts `list<ScrapedArticle>` into `articles`, returns the count created. Articles whose `body` is null are skipped.
  - `existingIds(array $articles): array` — `list<string>` of `external_id`s already present among **scraped** rows.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/News/ImportScrapedArticlesTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\News\ImportScrapedArticles;
use App\Enums\ArticleCategory;
use App\Models\Article;
use App\Models\ScrapeSource;
use App\News\ScrapedArticle;
use Carbon\CarbonImmutable;

function feedItem(
    string $externalId = 'b2qbp9uhh',
    string $title = 'realme 16 Pro',
    ?string $body = '<p>Isi artikel yang panjang.</p>',
    ?string $imageUrl = null,
): ScrapedArticle {
    return new ScrapedArticle(
        externalId: $externalId,
        title: $title,
        excerpt: 'Meluncur di Indonesia.',
        url: 'https://www.tek.id/gadget/realme-'.$externalId,
        publishedAt: CarbonImmutable::parse('2026-09-21 12:18:00'),
        body: $body,
        imageUrl: $imageUrl,
    );
}

function sourceForImport(ArticleCategory $category = ArticleCategory::Rilis): ScrapeSource
{
    return ScrapeSource::factory()->create(['category' => $category]);
}

it('creates an article for each new item', function (): void {
    $created = app(ImportScrapedArticles::class)->handle(
        [feedItem('b2qbp9uhh'), feedItem('b2qbb9ugd')],
        sourceForImport(),
    );

    expect($created)->toBe(2)
        ->and(Article::scraped()->count())->toBe(2);
});

it('stores every field', function (): void {
    app(ImportScrapedArticles::class)->handle(
        [feedItem(imageUrl: 'https://static.tek.id/a.jpeg')],
        sourceForImport(ArticleCategory::Ulasan),
    );

    $article = Article::scraped()->sole();

    expect($article->title)->toBe('realme 16 Pro')
        ->and($article->slug)->toBe('realme-16-pro')
        ->and($article->category)->toBe(ArticleCategory::Ulasan)
        ->and($article->excerpt)->toBe('Meluncur di Indonesia.')
        ->and($article->body)->toBe('<p>Isi artikel yang panjang.</p>')
        ->and($article->image_url)->toBe('https://static.tek.id/a.jpeg')
        ->and($article->source)->toBe('tek.id')
        ->and($article->source_url)->toBe('https://www.tek.id/gadget/realme-b2qbp9uhh')
        ->and($article->external_id)->toBe('b2qbp9uhh')
        ->and($article->tags)->toBe([])
        ->and($article->published_at->toDateTimeString())->toBe('2026-09-21 12:18:00');
});

it('never stores an article with no body', function (): void {
    $created = app(ImportScrapedArticles::class)->handle(
        [feedItem('b2qbp9uhh', body: null), feedItem('b2qbb9ugd')],
        sourceForImport(),
    );

    expect($created)->toBe(1)
        ->and(Article::scraped()->count())->toBe(1)
        ->and(Article::scraped()->sole()->external_id)->toBe('b2qbb9ugd');
});

it('computes read minutes from the body length', function (): void {
    $body = '<p>'.str_repeat('kata ', 600).'</p>';

    app(ImportScrapedArticles::class)->handle([feedItem(body: $body)], sourceForImport());

    // 600 words at 200 words per minute.
    expect(Article::scraped()->sole()->read_minutes)->toBe(3);
});

it('gives a very short article at least one minute', function (): void {
    app(ImportScrapedArticles::class)->handle([feedItem(body: '<p>Singkat sekali.</p>')], sourceForImport());

    expect(Article::scraped()->sole()->read_minutes)->toBe(1);
});

it('is idempotent across repeated runs', function (): void {
    $items = [feedItem('b2qbp9uhh'), feedItem('b2qbb9ugd')];
    $source = sourceForImport();

    app(ImportScrapedArticles::class)->handle($items, $source);
    $second = app(ImportScrapedArticles::class)->handle($items, $source);

    expect($second)->toBe(0)
        ->and(Article::scraped()->count())->toBe(2);
});

it('updates a changed title in place', function (): void {
    $source = sourceForImport();

    app(ImportScrapedArticles::class)->handle([feedItem(title: 'Judul lama')], $source);
    app(ImportScrapedArticles::class)->handle([feedItem(title: 'Judul baru')], $source);

    expect(Article::scraped()->count())->toBe(1)
        ->and(Article::scraped()->sole()->title)->toBe('Judul baru');
});

it('suffixes a slug that collides with one of our own articles', function (): void {
    Article::factory()->create(['slug' => 'realme-16-pro', 'title' => 'Tulisan kami sendiri']);

    app(ImportScrapedArticles::class)->handle([feedItem()], sourceForImport());

    expect(Article::scraped()->sole()->slug)->toBe('realme-16-pro-2')
        ->and(Article::own()->sole()->title)->toBe('Tulisan kami sendiri');
});

it('never modifies an article we wrote', function (): void {
    $ours = Article::factory()->create([
        'title' => 'Tulisan kami',
        'body' => '<p>Isi kami.</p>',
        'external_id' => null,
    ]);

    app(ImportScrapedArticles::class)->handle([feedItem()], sourceForImport());

    expect($ours->fresh()->title)->toBe('Tulisan kami')
        ->and($ours->fresh()->body)->toBe('<p>Isi kami.</p>')
        ->and($ours->fresh()->source)->toBeNull();
});

it('reports which external ids it already has', function (): void {
    $source = sourceForImport();
    app(ImportScrapedArticles::class)->handle([feedItem('b2qbp9uhh')], $source);

    $known = app(ImportScrapedArticles::class)->existingIds([feedItem('b2qbp9uhh'), feedItem('b2qbb9ugd')]);

    expect($known)->toBe(['b2qbp9uhh']);
});

it('reports no known ids for an empty batch', function (): void {
    expect(app(ImportScrapedArticles::class)->existingIds([]))->toBe([]);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact --filter=ImportScrapedArticlesTest`
Expected: FAIL — `Target class [App\Actions\News\ImportScrapedArticles] does not exist.`

- [ ] **Step 3: Write the action**

Create `app/Actions/News/ImportScrapedArticles.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\News;

use App\Models\Article;
use App\Models\ScrapeSource;
use App\News\ScrapedArticle;
use Illuminate\Support\Str;

/**
 * Upserts scraped articles into the articles table.
 *
 * Every query here is scoped to rows where `source` is set. The importer can
 * create and update scraped articles and can never modify or overwrite one a
 * human wrote — including when a slug collides, where the scraped row takes a
 * suffix instead. That invariant is what makes sharing the table safe.
 */
final class ImportScrapedArticles
{
    /** Average reading speed, in words per minute. */
    private const int WORDS_PER_MINUTE = 200;

    /** articles.read_minutes is an unsigned tiny int. */
    private const int MAX_READ_MINUTES = 255;

    /**
     * @param  list<ScrapedArticle>  $articles
     * @return int  the number of articles created
     */
    public function handle(array $articles, ScrapeSource $source): int
    {
        $created = 0;

        foreach ($articles as $article) {
            // A missing body makes the article worthless. Skipping it without
            // storing anything means the next run retries it.
            if ($article->body === null || trim($article->body) === '') {
                continue;
            }

            $existing = Article::scraped()->where('external_id', $article->externalId)->first();

            $model = $existing ?? new Article(['external_id' => $article->externalId]);

            if ($existing === null) {
                $created++;
            }

            $model->fill([
                'slug' => $this->uniqueSlug($article->title, $existing?->getKey()),
                'title' => $article->title,
                'category' => $source->category,
                'excerpt' => $article->excerpt,
                'body' => $article->body,
                'tags' => [],
                'read_minutes' => $this->readMinutes($article->body),
                'published_at' => $article->publishedAt,
                'source' => 'tek.id',
                'source_url' => $article->url,
                'external_id' => $article->externalId,
            ]);

            if ($article->imageUrl !== null) {
                $model->image_url = $article->imageUrl;
            }

            $model->save();
        }

        return $created;
    }

    /**
     * @param  list<ScrapedArticle>  $articles
     * @return list<string>
     */
    public function existingIds(array $articles): array
    {
        if ($articles === []) {
            return [];
        }

        $ids = array_map(static fn (ScrapedArticle $article): string => $article->externalId, $articles);

        return Article::scraped()
            ->whereIn('external_id', $ids)
            ->pluck('external_id')
            ->all();
    }

    /** Checks the whole table, so a scraped article can never take our slug. */
    private function uniqueSlug(string $title, ?int $ignoreId): string
    {
        $base = Str::limit(Str::slug($title), 180, '');

        if ($base === '') {
            $base = 'artikel';
        }

        $slug = $base;
        $suffix = 1;

        while ($this->slugTaken($slug, $ignoreId)) {
            $suffix++;
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }

    private function slugTaken(string $slug, ?int $ignoreId): bool
    {
        return Article::where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    private function readMinutes(string $body): int
    {
        $text = trim(html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $words = $text === '' ? 0 : count(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return max(1, min(self::MAX_READ_MINUTES, (int) ceil($words / self::WORDS_PER_MINUTE)));
    }
}
```

- [ ] **Step 4: Run the tests**

Run: `php artisan test --compact --filter=ImportScrapedArticlesTest`
Expected: PASS, 11 tests.

- [ ] **Step 5: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: import scraped articles into the articles table

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 7: The import command and its schedule

**Files:**
- Create: `app/Console/Commands/ImportNews.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/News/ImportNewsCommandTest.php`

**Interfaces:**
- Consumes: `FetchFeed::handle()`, `FetchArticleContent::handle()`, `ImportScrapedArticles::handle()` / `::existingIds()`, `ScrapeSource::active()` / `::markRan()`.
- Produces: the `news:import` Artisan command. Exit `0` when every active source succeeded, `1` when any failed.

- [ ] **Step 1: Generate the command**

```bash
php artisan make:command ImportNews --no-interaction
```

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/News/ImportNewsCommandTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Article;
use App\Models\ScrapeSource;
use Illuminate\Support\Facades\Http;

function fakeFeedsAndPages(): void
{
    Http::fake([
        'www.tek.id/*/rss' => Http::response(
            file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')),
            200,
        ),
        'www.tek.id/*' => Http::response(
            file_get_contents(base_path('tests/Fixtures/tek-article.html')),
            200,
        ),
    ]);
}

it('imports articles from every active source', function (): void {
    fakeFeedsAndPages();
    ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);

    $this->artisan('news:import')->assertExitCode(0);

    expect(Article::scraped()->count())->toBe(3);

    // One feed request plus one article-page request per new article.
    Http::assertSentCount(1 + 3);
});

it('skips inactive sources without failing', function (): void {
    fakeFeedsAndPages();
    ScrapeSource::factory()->inactive()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);

    $this->artisan('news:import')->assertExitCode(0);

    expect(Article::scraped()->count())->toBe(0);
    Http::assertNothingSent();
});

it('fetches no article pages on a second run', function (): void {
    fakeFeedsAndPages();
    ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);
    $this->artisan('news:import');

    fakeFeedsAndPages();
    $this->artisan('news:import')->assertExitCode(0);

    // The feed only: every article was already known.
    Http::assertSentCount(1);
    expect(Article::scraped()->count())->toBe(3);
});

it('keeps importing when one source fails, and reports failure', function (): void {
    Http::fake([
        'www.tek.id/gadget/rss' => Http::response('', 500),
        'www.tek.id/*/rss' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')), 200),
        'www.tek.id/*' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-article.html')), 200),
    ]);

    $broken = ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);
    ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/review/rss']);

    $this->artisan('news:import')->assertExitCode(1);

    expect(Article::scraped()->count())->toBe(3)
        ->and($broken->fresh()->last_error)->not->toBeNull();
});

it('stamps a successful run on the source', function (): void {
    fakeFeedsAndPages();
    $source = ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss', 'last_error' => 'lama']);

    $this->artisan('news:import');

    expect($source->fresh()->last_run_at)->not->toBeNull()
        ->and($source->fresh()->last_error)->toBeNull();
});

it('skips articles whose body cannot be extracted', function (): void {
    Http::fake([
        'www.tek.id/*/rss' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')), 200),
        'www.tek.id/*' => Http::response('<html><body><p>tidak ada isi artikel</p></body></html>', 200),
    ]);

    ScrapeSource::factory()->create(['feed_url' => 'https://www.tek.id/gadget/rss']);

    $this->artisan('news:import')->assertExitCode(0);

    expect(Article::scraped()->count())->toBe(0);
});

it('is registered on the hourly schedule', function (): void {
    $events = app(Illuminate\Console\Scheduling\Schedule::class)->events();

    $command = collect($events)->first(fn ($event): bool => str_contains($event->command ?? '', 'news:import'));

    expect($command)->not->toBeNull()
        ->and($command->expression)->toBe('0 * * * *');
});
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `php artisan test --compact --filter=ImportNewsCommandTest`
Expected: FAIL — `The command "news:import" does not exist.`

- [ ] **Step 4: Write the command**

Replace `app/Console/Commands/ImportNews.php`:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\News\FetchArticleContent;
use App\Actions\News\FetchFeed;
use App\Actions\News\ImportScrapedArticles;
use App\Models\ScrapeSource;
use App\News\ScrapedArticle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportNews extends Command
{
    protected $signature = 'news:import';

    protected $description = 'Import articles from every active scrape source';

    public function handle(
        FetchFeed $fetchFeed,
        FetchArticleContent $fetchContent,
        ImportScrapedArticles $import,
    ): int {
        $created = 0;
        $failed = [];

        foreach (ScrapeSource::active()->get() as $source) {
            try {
                $articles = $fetchFeed->handle($source);
            } catch (Throwable $exception) {
                $failed[] = $source->name;
                $source->markRan($exception->getMessage());

                Log::warning('scrape source failed', [
                    'source' => $source->name,
                    'message' => $exception->getMessage(),
                ]);

                $this->components->error("{$source->name}: {$exception->getMessage()}");

                continue;
            }

            $created += $import->handle($this->withContent($articles, $import, $fetchContent), $source);
            $source->markRan();

            $this->components->info("{$source->name}: ".count($articles).' item diperiksa.');
        }

        $this->components->info("Selesai. {$created} artikel baru disimpan.");

        if ($failed !== []) {
            $this->components->error('Sumber gagal: '.implode(', ', $failed));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Only genuinely new articles get a page fetch. In steady state that is one
     * or two extra requests per source rather than ten.
     *
     * @param  list<ScrapedArticle>  $articles
     * @return list<ScrapedArticle>
     */
    private function withContent(array $articles, ImportScrapedArticles $import, FetchArticleContent $fetchContent): array
    {
        $known = $import->existingIds($articles);

        return array_map(
            function (ScrapedArticle $article) use ($known, $fetchContent): ScrapedArticle {
                if (in_array($article->externalId, $known, true)) {
                    return $article;
                }

                $content = $fetchContent->handle($article->url);

                return $article->withContent($content?->body, $content?->imageUrl);
            },
            $articles,
        );
    }
}
```

- [ ] **Step 5: Register the schedule**

Replace `routes/console.php`:

```php
<?php

declare(strict_types=1);

use App\Console\Commands\ImportNews;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ImportNews::class)->hourly()->withoutOverlapping();
```

- [ ] **Step 6: Run the tests**

Run: `php artisan test --compact --filter=ImportNewsCommandTest`
Expected: PASS, 7 tests.

- [ ] **Step 7: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add hourly news:import command driven by scrape sources

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 8: Filament resource for scrape sources

Unlike the retired news resource, this one is full CRUD — the rows are the owner's configuration, not importer output. Read `app/Filament/Resources/Stores/` first; it is the closest existing pattern (a simple form with a `Section` and a `Toggle`).

**Files:**
- Create: `app/Filament/Resources/ScrapeSources/ScrapeSourceResource.php`
- Create: `app/Filament/Resources/ScrapeSources/Pages/{ListScrapeSources,CreateScrapeSource,EditScrapeSource}.php`
- Create: `app/Filament/Resources/ScrapeSources/Schemas/ScrapeSourceForm.php`
- Create: `app/Filament/Resources/ScrapeSources/Tables/ScrapeSourcesTable.php`
- Modify: `tests/Feature/Filament/ResourcePagesTest.php`
- Test: `tests/Feature/Filament/ScrapeSourceResourceTest.php`

**Interfaces:**
- Consumes: `App\Models\ScrapeSource` (Task 2), `App\Enums\ArticleCategory`.
- Produces: a Filament resource with `index`, `create` and `edit` pages, under navigation group `Konten`.

- [ ] **Step 1: Generate the scaffolding**

```bash
php artisan make:filament-resource ScrapeSource --generate --no-interaction
```

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/Filament/ScrapeSourceResourceTest.php`:

```php
<?php

declare(strict_types=1);

use App\Enums\ArticleCategory;
use App\Filament\Resources\ScrapeSources\Pages\ListScrapeSources;
use App\Models\ScrapeSource;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('lists the configured sources', function (): void {
    $source = ScrapeSource::factory()->create(['name' => 'tek.id — Gadget']);

    Livewire::test(ListScrapeSources::class)->assertCanSeeTableRecords([$source]);
});

it('filters to active sources', function (): void {
    $active = ScrapeSource::factory()->create();
    $paused = ScrapeSource::factory()->inactive()->create();

    Livewire::test(ListScrapeSources::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$paused]);
});

it('shows which sources have failed', function (): void {
    $broken = ScrapeSource::factory()->create(['last_error' => 'feed tidak valid']);

    Livewire::test(ListScrapeSources::class)
        ->assertCanSeeTableRecords([$broken])
        ->assertSee('feed tidak valid');
});

it('creates a source from the panel', function (): void {
    $this->get(App\Filament\Resources\ScrapeSources\ScrapeSourceResource::getUrl('create'))
        ->assertSuccessful();
});

it('opens the edit page', function (): void {
    $source = ScrapeSource::factory()->create();

    $this->get(App\Filament\Resources\ScrapeSources\ScrapeSourceResource::getUrl('edit', ['record' => $source]))
        ->assertSuccessful();
});

it('offers every article category as a target', function (): void {
    $source = ScrapeSource::factory()->create(['category' => ArticleCategory::Panduan]);

    expect($source->fresh()->category)->toBe(ArticleCategory::Panduan);
});
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `php artisan test --compact --filter=ScrapeSourceResourceTest`
Expected: FAIL until the generated classes are adjusted below — the generator renders `category` as a plain text input and does not add the filters.

- [ ] **Step 4: Write the form**

Replace `app/Filament/Resources/ScrapeSources/Schemas/ScrapeSourceForm.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScrapeSources\Schemas;

use App\Enums\ArticleCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScrapeSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sumber')
                    ->columnSpanFull()
                    ->description('Feed yang diambil otomatis setiap jam oleh news:import.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->placeholder('tek.id — Gadget'),
                        TextInput::make('feed_url')
                            ->label('URL feed')
                            ->required()
                            ->url()
                            ->unique(ignoreRecord: true)
                            ->placeholder('https://www.tek.id/gadget/rss'),
                        Select::make('category')
                            ->label('Kategori artikel')
                            ->options(ArticleCategory::class)
                            ->required()
                            ->helperText('Artikel dari feed ini masuk ke kategori tersebut.'),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Matikan untuk menjeda tanpa menghapus sumbernya.'),
                    ]),
            ]);
    }
}
```

- [ ] **Step 5: Write the table**

Replace `app/Filament/Resources/ScrapeSources/Tables/ScrapeSourcesTable.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScrapeSources\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ScrapeSourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('last_run_at')
                    ->label('Terakhir jalan')
                    ->dateTime()
                    ->placeholder('Belum pernah')
                    ->sortable(),
                TextColumn::make('last_error')
                    ->label('Kesalahan terakhir')
                    ->wrap()
                    ->limit(60)
                    ->placeholder('—')
                    ->color('danger'),
                TextColumn::make('feed_url')
                    ->label('URL feed')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktif'),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->deferFilters()
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

- [ ] **Step 6: Set the resource's navigation**

In `app/Filament/Resources/ScrapeSources/ScrapeSourceResource.php`, set these properties (the generator leaves the icon on the default, which `ResourcePagesTest` rejects):

```php
    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Sumber Scraping';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?int $navigationSort = 4;
```

Add `use BackedEnum;`, `use UnitEnum;` and `use Filament\Support\Icons\Heroicon;` to the imports. If `OutlinedArrowDownTray` collides with another resource's icon, `ResourcePagesTest` will say so — pick another from `Heroicon` that is not already used.

- [ ] **Step 7: Register it in the resource guard**

In `tests/Feature/Filament/ResourcePagesTest.php`:

1. Add `use App\Filament\Resources\ScrapeSources\ScrapeSourceResource;` to the imports
2. Add `        'scrape sources' => [ScrapeSourceResource::class],` to `creatableResources()` — it has a create page, unlike the retired news resource

- [ ] **Step 8: Run the tests**

Run: `php artisan test --compact --filter="ScrapeSourceResourceTest|ResourcePagesTest|AdminBoundaryTest"`
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: manage scrape sources in the admin panel

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 9: Public presentation — images, credit and canonical

Three things change on the public site. Two are new capability; the third is a correctness fix.

**The correctness fix:** `resources/views/livewire/berita-detail-page.blade.php` hardcodes the byline **"Rizky Adhitama"** (in the meta row and again in the author box at the foot) and the photo credit **"Foto: dokumentasi Bandingin.id"**. On a scraped article both statements are false — they put a named person's byline on another outlet's writing and claim a third party's photograph as your own. For scraped articles these must show the source instead.

**Files:**
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/components/article-card.blade.php`
- Modify: `resources/views/livewire/berita-detail-page.blade.php`
- Modify: `resources/views/livewire/berita-page.blade.php` (featured hero image)
- Test: `tests/Feature/ScrapedArticlePresentationTest.php`

**Interfaces:**
- Consumes: `Article::isScraped()`, `Article->image_url`, `Article->source`, `Article->source_url` (Task 3).
- Produces: no new classes. `@stack('head')` becomes available in the layout for any page to push head tags into.

`@push('head')` from a Livewire full-page component's view does reach the layout's `@stack('head')` — this was verified against a rendered page, not assumed.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ScrapedArticlePresentationTest.php`:

```php
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
        ->assertDontSee('rel="canonical"', escape: false);
});

it('does not put our byline on someone elses article', function (): void {
    $article = Article::factory()->scraped()->create();

    $this->get(route('berita.show', $article->slug))
        ->assertDontSee('Rizky Adhitama');
});

it('does not claim someone elses photo as ours', function (): void {
    $article = Article::factory()->scraped()->create(['image_url' => 'https://static.tek.id/a.jpeg']);

    $this->get(route('berita.show', $article->slug))
        ->assertDontSee('dokumentasi Bandingin.id');
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
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact --filter=ScrapedArticlePresentationTest`
Expected: FAIL — no canonical tag, and the hardcoded byline still shows on scraped articles.

- [ ] **Step 3: Add the head stack to the layout**

In `resources/views/layouts/app.blade.php`, add `@stack('head')` after `@livewireStyles`:

```blade
        @livewireStyles

        @stack('head')
    </head>
```

- [ ] **Step 4: Show images on the article card**

In `resources/views/components/article-card.blade.php`, replace this line:

```blade
    <span class="photo-slot relative block aspect-16/10 rounded-2xl"></span>
```

with:

```blade
    @if ($article->image_url)
        <img
            src="{{ $article->image_url }}"
            alt="{{ $article->title }}"
            loading="lazy"
            referrerpolicy="no-referrer"
            class="aspect-16/10 w-full rounded-2xl object-cover"
        >
    @else
        <span class="photo-slot relative block aspect-16/10 rounded-2xl"></span>
    @endif
```

- [ ] **Step 4b: Show the image on the featured hero**

The featured article at the top of `/berita` renders its own placeholder, separate
from `x-article-card`. In `resources/views/livewire/berita-page.blade.php`, replace:

```blade
                    <span class="photo-slot relative block aspect-16/10 rounded-[22px]">
                        <span class="absolute inset-0 grid place-items-center font-mono text-[10.5px] tracking-[.14em] text-faint uppercase">Foto artikel</span>
                    </span>
```

with:

```blade
                    @if ($this->featured->image_url)
                        <img
                            src="{{ $this->featured->image_url }}"
                            alt="{{ $this->featured->title }}"
                            referrerpolicy="no-referrer"
                            class="aspect-16/10 w-full rounded-[22px] object-cover"
                        >
                    @else
                        <span class="photo-slot relative block aspect-16/10 rounded-[22px]">
                            <span class="absolute inset-0 grid place-items-center font-mono text-[10.5px] tracking-[.14em] text-faint uppercase">Foto artikel</span>
                        </span>
                    @endif
```

- [ ] **Step 5: Fix the detail page**

In `resources/views/livewire/berita-detail-page.blade.php`:

**5a.** After the opening `@php ... @endphp` block, add the canonical tag:

```blade
@if ($article->isScraped())
    @push('head')
        <link rel="canonical" href="{{ $article->source_url }}">
    @endpush
@endif
```

**5b.** Replace the byline row (the `<div class="mt-6 flex flex-wrap items-center gap-3.5 border-y border-line py-4">` block) so the author is source-aware. Replace the two lines:

```blade
            <span class="grid size-[34px] place-items-center rounded-full border border-line bg-surface font-mono text-[11px] font-semibold text-muted">RA</span>
            <span class="text-[13.5px] font-medium text-ink">Rizky Adhitama</span>
```

with:

```blade
            @if ($article->isScraped())
                <span class="grid size-[34px] place-items-center rounded-full border border-line bg-surface font-mono text-[11px] font-semibold text-muted">↗</span>
                <span class="text-[13.5px] font-medium text-ink">
                    Sumber:
                    <a href="{{ $article->source_url }}" target="_blank" rel="noopener nofollow" class="text-brand hover:underline">{{ $article->source }}</a>
                </span>
            @else
                <span class="grid size-[34px] place-items-center rounded-full border border-line bg-surface font-mono text-[11px] font-semibold text-muted">RA</span>
                <span class="text-[13.5px] font-medium text-ink">Rizky Adhitama</span>
            @endif
```

**5c.** Replace the hero block (the `<div class="photo-slot relative aspect-16/9 ...">` wrapper, its inner "Foto artikel" div, and the following "Foto: dokumentasi Bandingin.id" line) with:

```blade
        @if ($article->image_url)
            <img
                src="{{ $article->image_url }}"
                alt="{{ $article->title }}"
                referrerpolicy="no-referrer"
                class="aspect-16/9 w-full rounded-[clamp(16px,2vw,26px)] object-cover"
            >
        @else
            <div class="photo-slot relative aspect-16/9 rounded-[clamp(16px,2vw,26px)]">
                <div class="absolute inset-0 grid place-items-center font-mono text-[10.5px] tracking-[.14em] text-faint uppercase">Foto artikel</div>
            </div>
        @endif

        <div class="mt-2.5 text-[11.5px] text-muted">
            @if ($article->isScraped())
                Foto: {{ $article->source }}
            @else
                Foto: dokumentasi Bandingin.id
            @endif
        </div>
```

**5d.** The author box near the foot of the file also names `Rizky Adhitama` (around line 67). Wrap that whole box in `@if (! $article->isScraped())` ... `@endif` so it renders only on articles you wrote.

- [ ] **Step 6: Run the tests**

Run: `php artisan test --compact --filter="ScrapedArticlePresentationTest|BeritaPageTest"`
Expected: PASS, 11 new tests plus the existing Berita page tests.

- [ ] **Step 7: Build assets and check the page**

```bash
npm run build
```

Use the Boost `get-absolute-url` tool for the host rather than guessing it, then open `/berita`.

- [ ] **Step 8: Commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: credit sources and show hero images on articles

Scraped articles carry a source credit and a canonical link, and no
longer render our byline or claim their photos as our own documentation.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 10: Live verification and recorded rules

**Files:**
- Create: `.ai/rules/news.md` (rewritten via the Boost `record-rule` tool)
- Modify: `.ai/rules/index.md`

- [ ] **Step 1: Seed the sources and run the importer for real**

```bash
php artisan db:seed --class=ScrapeSourceSeeder --no-interaction
php artisan news:import
```

This hits the live site. A cold start is roughly 7 feed requests plus one page fetch per new article and takes several minutes — run it in the background if your tool has a foreground timeout.

Expected: `Selesai. N artikel baru disimpan.` and exit 0.

- [ ] **Step 2: Verify the imported data**

```bash
php artisan tinker --execute '
echo "scraped: ".App\Models\Article::scraped()->count()."\n";
echo "ours:    ".App\Models\Article::own()->count()."  (must still be 12)\n";
echo "with image: ".App\Models\Article::scraped()->whereNotNull("image_url")->count()."\n";
$a = App\Models\Article::scraped()->latest("published_at")->first();
echo "newest: ".$a->title."\n";
echo "slug:   ".$a->slug."\n";
echo "read:   ".$a->read_minutes." menit\n";
echo "body has Baca Juga: ".(str_contains($a->body, "Baca Juga") ? "YES - BUG" : "no")."\n";
echo "body has script:    ".(str_contains($a->body, "<script") ? "YES - BUG" : "no")."\n";
'
```

`ours` must still be 12. If it is not, the `whereNotNull('source')` guard has a hole — stop and fix it before going further.

- [ ] **Step 3: Confirm idempotency**

```bash
php artisan news:import
```

Expected: `0 artikel baru disimpan`, and far faster than the first run, since no article pages are fetched.

- [ ] **Step 4: Record the rules**

Use the Boost `record-rule` MCP tool — not a hand-written file, and not any native memory tool.

- `glob`: `app/News/**`
- `title`: `Scraped articles`
- `note`:

```
Articles with a non-null `source` were scraped from an external site; null
source means a human wrote it. Every importer query is scoped
Article::scraped() / whereNotNull('source'). The importer must never create,
update or delete a row a human wrote — on a slug collision the scraped article
takes a numeric suffix instead. ImportScrapedArticlesTest pins this.

Publishing tek.id's text verbatim was the owner's explicit decision, made after
being told it is copyright infringement and carries a duplicate-content
ranking risk. Do not silently change it, and keep the two mitigations: the
visible "Sumber" credit and the rel=canonical pointing at source_url. The
detail page must never show our byline or "Foto: dokumentasi Bandingin.id" on
a scraped article — both are false attribution.

Sources are rows in scrape_sources, not code: feed URL plus the ArticleCategory
its articles become. Adding a site is a row. TekChannel was deleted.

RSS pubDate is "Y-m-d H:i:s" in Asia/Jakarta with NO timezone and the app runs
in UTC — always CarbonImmutable::createFromFormat(..., 'Asia/Jakarta')->utc().
Carbon::parse() shifts every article by 7 hours.

Body extraction uses DOMDocument, which also repairs tek.id's never-closed <p>
tags. The junk XPath in FetchArticleContent is load-bearing: RichContentRenderer
UNWRAPS unknown elements rather than dropping them, so a surviving
div.bacajuga renders as the stray text "Baca Juga" inside the article.
RichContentRenderer does handle safety — it strips script, onclick and
javascript: hrefs — so no separate sanitiser is needed.

A body-less article is never stored, so the next run retries it. Selectors are
tek.id-specific; move them onto the source row when a second site is added.

Adding or removing a Filament resource requires editing creatableResources() /
listableResources() in tests/Feature/Filament/ResourcePagesTest.php, which
asserts the registered list exhaustively.
```

- [ ] **Step 5: Check the rules index**

Boost adds the index row itself. Open `.ai/rules/index.md` and confirm the row's glob covers the actions and command too, widening it if needed:

```
| app/News/**, app/Actions/News/**, app/Console/Commands/ImportNews.php | .ai/rules/news.md |
```

Match the `paths:` frontmatter in `.ai/rules/news.md` to the same globs.

- [ ] **Step 6: Commit**

```bash
git add .ai/rules
git commit -m "docs: record the scraped article rules

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Verification checklist

Run before declaring the feature done:

- [ ] `php -d memory_limit=2G vendor/bin/pest --compact` — full suite green
- [ ] `vendor/bin/pint --format agent` — clean
- [ ] `php artisan news:import` against the live site imports real articles
- [ ] A second `php artisan news:import` reports `0 artikel baru disimpan`
- [ ] `Article::own()->count()` is still 12 — the scraper never touched your writing
- [ ] No imported body contains `Baca Juga`, `googletag` or `<script`
- [ ] `/berita` shows scraped articles with images, in the grid, alongside your own
- [ ] A scraped article opens at `/berita/{slug}`, shows "Sumber: tek.id", has a `rel="canonical"` to the original, and shows neither your byline nor "dokumentasi Bandingin.id"
- [ ] One of your own articles still shows your byline and no source credit
- [ ] `php artisan schedule:list` shows `news:import` hourly
- [ ] The admin's Sumber Scraping resource lists, creates and edits sources, and shows `last_run_at`
