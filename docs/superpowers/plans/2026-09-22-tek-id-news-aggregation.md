# tek.id News Aggregation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ingest article metadata from tek.id's seven RSS channels into a `news_items` table and surface it as a link-out "Kabar Teknologi" feed on `/berita`.

**Architecture:** An hourly Artisan command loops the seven channels. For each channel, `FetchTekChannel` GETs the RSS feed and returns readonly `ScrapedArticle` DTOs; items already in the database are passed through untouched, while genuinely new ones get one extra page fetch through `FetchArticleImage` to read their `og:image` meta tag. `ImportScrapedArticles` then upserts the batch keyed on a stable ID token extracted from the URL. HTTP and persistence never touch each other, so every unit tests without network or without database.

**Tech Stack:** Laravel 13, PHP 8.3+, Livewire 4, Filament 5, Pest 5, SQLite. HTTP via Laravel's `Http` facade (Guzzle 7.15 underneath). XML via PHP's built-in `SimpleXML`.

**Spec:** `docs/superpowers/specs/2026-09-08-tek-id-news-aggregation-design.md`

## Global Constraints

- **No new Composer or npm dependencies.** `guzzlehttp/guzzle` 7.15.3 and `SimpleXML` are already available. CLAUDE.md forbids dependency changes without approval.
- **No new base folders under `app/`.** `app/News/` follows the existing `app/Duel/` and `app/Money/` precedent. `app/Models/Concerns/` is a subdirectory of an existing folder. Both are allowed; anything else is not.
- **`App\Support` and `App\Data` are forbidden.** `tests/Feature/StaticLayerRetiredTest.php` asserts those directories stay deleted and that no file under `app/`, `database/` or `resources/` references those namespaces. It will fail the build.
- **`declare(strict_types=1);` in every new PHP file.** Pint's Laravel preset does not add it.
- **Run `vendor/bin/pint --dirty --format agent` after editing any PHP file.** Required before every commit.
- **Article bodies are never fetched, parsed, stored, or rendered.** The article page is requested only for its `og:image` meta tag. This is the legal boundary of the whole feature.
- **Every ingested item links off-site** with `target="_blank" rel="noopener nofollow"`. The `nofollow` is required — without it every card passes SEO equity to a competitor.
- **App timezone is UTC** (`config/app.php`). tek.id timestamps are Jakarta local with no offset. This mismatch is the single most likely silent bug in the feature.
- **Create files with `php artisan make:` where a generator exists**, passing `--no-interaction`.
- **Filament is v5, not v4.** Resources live in `app/Filament/Resources/<Plural>/` with `Pages/`, `Schemas/`, `Tables/` subdirectories. Forms take and return `Filament\Schemas\Schema`. Table actions come from `Filament\Actions\*`. Panel tests use `Livewire::test()`, never `Pest\Livewire\livewire()` (that plugin is not installed).
- **Admin code must never import from `App\Livewire`, and vice versa.** `tests/Feature/Filament/AdminBoundaryTest.php` enforces both directions.
- **Blade:** use the `resources/css/app.css` theme tokens (`ink`, `muted`, `faint`, `line`, `brand`, `surface`, `.photo-slot`). Never hardcode hex values. Each Livewire view needs exactly one root element.
- **Indonesian UI copy.** Labels, headings and filter names are Indonesian, matching the rest of the site.

---

### Task 1: Channel enum, DTO, and config

**Files:**
- Modify: `config/services.php`
- Create: `app/News/TekChannel.php`
- Create: `app/News/ScrapedArticle.php`
- Test: `tests/Feature/News/TekChannelTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces:
  - `App\News\TekChannel` — a string-backed enum with cases `Tek`, `Gadget`, `Review`, `Culture`, `Future`, `Insight`, `Tektalk` (values `'Tek'`, `'Gadget'`, …). Methods: `path(): string`, `feedUrl(): string`.
  - `App\News\ScrapedArticle` — `final readonly` with promoted public properties `string $externalId`, `TekChannel $channel`, `string $title`, `string $excerpt`, `string $url`, `CarbonImmutable $publishedAt`, `?string $imageUrl = null`. Method: `withImage(?string $imageUrl): self`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/News/TekChannelTest.php`:

```php
<?php

declare(strict_types=1);

use App\News\ScrapedArticle;
use App\News\TekChannel;
use Carbon\CarbonImmutable;

it('covers all seven tek.id channels', function (): void {
    expect(TekChannel::cases())->toHaveCount(7);
});

it('builds a feed url from the configured base url', function (): void {
    config()->set('services.tek.base_url', 'https://www.tek.id');

    expect(TekChannel::Gadget->feedUrl())->toBe('https://www.tek.id/gadget/rss')
        ->and(TekChannel::Tektalk->feedUrl())->toBe('https://www.tek.id/tektalk/rss');
});

it('strips a trailing slash from the configured base url', function (): void {
    config()->set('services.tek.base_url', 'https://www.tek.id/');

    expect(TekChannel::Insight->feedUrl())->toBe('https://www.tek.id/insight/rss');
});

it('returns a new instance when an image is attached', function (): void {
    $article = new ScrapedArticle(
        externalId: 'b2qbp9uhh',
        channel: TekChannel::Gadget,
        title: 'realme 16 Pro Harry Potter Edition',
        excerpt: 'Meluncur di Indonesia.',
        url: 'https://www.tek.id/gadget/realme-b2qbp9uhh',
        publishedAt: CarbonImmutable::parse('2026-09-21 12:18:00'),
    );

    $withImage = $article->withImage('https://static.tek.id/a.jpeg');

    expect($article->imageUrl)->toBeNull()
        ->and($withImage->imageUrl)->toBe('https://static.tek.id/a.jpeg')
        ->and($withImage->externalId)->toBe('b2qbp9uhh')
        ->and($withImage->channel)->toBe(TekChannel::Gadget);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact --filter=TekChannelTest`
Expected: FAIL — `Class "App\News\TekChannel" not found`.

- [ ] **Step 3: Add the config entry**

Add this to the array in `config/services.php`, after the `'slack' => [...]` block:

```php
    'tek' => [
        'base_url' => env('TEK_BASE_URL', 'https://www.tek.id'),
        'user_agent' => env('TEK_USER_AGENT', 'BandinginBot/1.0 (+https://bandingin.id)'),
    ],
```

- [ ] **Step 4: Create the enum**

Create `app/News/TekChannel.php`:

```php
<?php

declare(strict_types=1);

namespace App\News;

/**
 * The seven public channels on tek.id. The case value is the display label and
 * the stored `news_items.channel` value; the lowercased name is the URL segment.
 */
enum TekChannel: string
{
    case Tek = 'Tek';
    case Gadget = 'Gadget';
    case Review = 'Review';
    case Culture = 'Culture';
    case Future = 'Future';
    case Insight = 'Insight';
    case Tektalk = 'Tektalk';

    public function path(): string
    {
        return mb_strtolower($this->value);
    }

    public function feedUrl(): string
    {
        return rtrim((string) config('services.tek.base_url'), '/').'/'.$this->path().'/rss';
    }
}
```

- [ ] **Step 5: Create the DTO**

Create `app/News/ScrapedArticle.php`:

```php
<?php

declare(strict_types=1);

namespace App\News;

use Carbon\CarbonImmutable;

/**
 * One normalised item from a tek.id RSS feed. This is the boundary between
 * "XML from the internet" and anything that touches the database.
 *
 * $publishedAt is always UTC — the feed's Jakarta-local timestamp is converted
 * at the point of parsing, never here.
 */
final readonly class ScrapedArticle
{
    public function __construct(
        public string $externalId,
        public TekChannel $channel,
        public string $title,
        public string $excerpt,
        public string $url,
        public CarbonImmutable $publishedAt,
        public ?string $imageUrl = null,
    ) {}

    public function withImage(?string $imageUrl): self
    {
        return new self(
            externalId: $this->externalId,
            channel: $this->channel,
            title: $this->title,
            excerpt: $this->excerpt,
            url: $this->url,
            publishedAt: $this->publishedAt,
            imageUrl: $imageUrl,
        );
    }
}
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php artisan test --compact --filter=TekChannelTest`
Expected: PASS, 4 tests.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add config/services.php app/News tests/Feature/News
git commit -m "feat: add tek.id channel enum and scraped article DTO

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 2: Extract the Indonesian date trait

This is a prerequisite refactor, not new behaviour. `App\Models\Article` holds a private `MONTHS` constant plus `dateShort()` and `dateLong()`. `NewsItem` needs the same formatting in Task 3, and copying the month table would be a DRY violation. `tests/Feature/ArticleAccessorsTest.php` already pins all three behaviours, so it guards the move.

**Files:**
- Create: `app/Models/Concerns/FormatsIndonesianDate.php`
- Modify: `app/Models/Article.php` (remove `MONTHS`, `dateShort()`, `dateLong()`; add the trait)
- Test: `tests/Feature/ArticleAccessorsTest.php` (existing, unchanged)

**Interfaces:**
- Consumes: nothing.
- Produces: trait `App\Models\Concerns\FormatsIndonesianDate` with `dateShort(): string` and `dateLong(): string`, both reading `$this->published_at` and returning `''` when it is null.

- [ ] **Step 1: Run the existing tests to confirm the current green baseline**

Run: `php artisan test --compact --filter=ArticleAccessorsTest`
Expected: PASS. If this is red before you start, stop and report it — the refactor has no safety net otherwise.

- [ ] **Step 2: Create the trait**

Create `app/Models/Concerns/FormatsIndonesianDate.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Indonesian date presentation for any model with a nullable `published_at`.
 *
 * Shared by Article and NewsItem so the month table exists in exactly one place.
 */
trait FormatsIndonesianDate
{
    /** Indonesian month names, indexed by month number. */
    private const array MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /** "12 Agu 2026" — the compact form used on cards. */
    public function dateShort(): string
    {
        if ($this->published_at === null) {
            return '';
        }

        $month = self::MONTHS[(int) $this->published_at->format('n')];

        return $this->published_at->format('j').' '.mb_substr($month, 0, 3).' '.$this->published_at->format('Y');
    }

    /** "12 Agustus 2026" — the long form used on the article page. */
    public function dateLong(): string
    {
        if ($this->published_at === null) {
            return '';
        }

        $month = self::MONTHS[(int) $this->published_at->format('n')];

        return $this->published_at->format('j').' '.$month.' '.$this->published_at->format('Y');
    }
}
```

- [ ] **Step 3: Use the trait in Article**

In `app/Models/Article.php`:

1. Add the import `use App\Models\Concerns\FormatsIndonesianDate;`
2. Add `use FormatsIndonesianDate;` to the class body, next to `use HasFactory;`
3. Delete the `private const array MONTHS = [...]` block
4. Delete the `dateShort()` method and its docblock
5. Delete the `dateLong()` method and its docblock

Leave `meta()`, `scopePublished()`, `getRouteKeyName()` and `casts()` exactly as they are — `meta()` calls `dateShort()` and will now resolve it through the trait.

- [ ] **Step 4: Run the tests to verify nothing regressed**

Run: `php artisan test --compact --filter="ArticleAccessorsTest|ArticleModelTest|BeritaPageTest|HomePageTest"`
Expected: PASS. These cover `dateShort()`, `dateLong()` and `meta()` through both the model and the rendered pages.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Models
git commit -m "refactor: extract Indonesian date formatting into a shared trait

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 3: The news_items table and model

**Files:**
- Create: `database/migrations/<timestamp>_create_news_items_table.php` (via `make:migration`)
- Create: `app/Models/NewsItem.php` (via `make:model`)
- Create: `database/factories/NewsItemFactory.php` (via `make:model -f`)
- Test: `tests/Feature/NewsItemModelTest.php`

**Interfaces:**
- Consumes: `App\Models\Concerns\FormatsIndonesianDate` (Task 2).
- Produces: `App\Models\NewsItem` with columns `external_id`, `source`, `channel`, `title`, `excerpt`, `url`, `image_url`, `published_at`; a `scopePublished(Builder $query): void`; `dateShort()` and `dateLong()` from the trait; `published_at` cast to `datetime`. Factory `NewsItemFactory` with a `withoutImage()` state.

- [ ] **Step 1: Generate the scaffolding**

```bash
php artisan make:model NewsItem -mf --no-interaction
```

This creates the model, the migration and the factory in one go.

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/NewsItemModelTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\NewsItem;

it('defaults the source to tek.id', function (): void {
    $item = NewsItem::factory()->create();

    expect($item->fresh()->source)->toBe('tek.id');
});

it('rejects a duplicate external id', function (): void {
    NewsItem::factory()->create(['external_id' => 'b2qbp9uhh']);

    expect(fn () => NewsItem::factory()->create(['external_id' => 'b2qbp9uhh']))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

it('rejects a duplicate url', function (): void {
    NewsItem::factory()->create(['url' => 'https://www.tek.id/gadget/a-b2qbp9uhh']);

    expect(fn () => NewsItem::factory()->create(['url' => 'https://www.tek.id/gadget/a-b2qbp9uhh']))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

it('formats its published date the Indonesian way', function (): void {
    $item = NewsItem::factory()->create(['published_at' => '2026-08-12 09:00:00']);

    expect($item->dateShort())->toBe('12 Agu 2026')
        ->and($item->dateLong())->toBe('12 Agustus 2026');
});

it('scopes to items with a published date', function (): void {
    NewsItem::factory()->count(2)->create();
    NewsItem::factory()->create(['published_at' => null]);

    expect(NewsItem::published()->count())->toBe(2);
});

it('allows a null image url', function (): void {
    $item = NewsItem::factory()->withoutImage()->create();

    expect($item->image_url)->toBeNull();
});
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `php artisan test --compact --filter=NewsItemModelTest`
Expected: FAIL — the `news_items` table has no columns yet.

- [ ] **Step 4: Write the migration**

Replace the body of the generated `..._create_news_items_table.php`:

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

    public function down(): void
    {
        Schema::dropIfExists('news_items');
    }
};
```

- [ ] **Step 5: Write the model**

Replace `app/Models/NewsItem.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\FormatsIndonesianDate;
use Database\Factories\NewsItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An article headline aggregated from an external publisher.
 *
 * These rows are link-out only: they carry no body and deliberately have no
 * slug, because they must never resolve to a page on this site.
 */
class NewsItem extends Model
{
    /** @use HasFactory<NewsItemFactory> */
    use HasFactory;

    use FormatsIndonesianDate;

    protected $fillable = [
        'external_id',
        'source',
        'channel',
        'title',
        'excerpt',
        'url',
        'image_url',
        'published_at',
    ];

    /** @param  Builder<$this>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 6: Write the factory**

Replace `database/factories/NewsItemFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NewsItem;
use App\News\TekChannel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NewsItem>
 */
class NewsItemFactory extends Factory
{
    protected $model = NewsItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $externalId = 'b2'.Str::random(7);
        $channel = fake()->randomElement(TekChannel::cases());

        return [
            'external_id' => $externalId,
            'source' => 'tek.id',
            'channel' => $channel->value,
            'title' => fake()->sentence(),
            'excerpt' => fake()->paragraph(),
            'url' => 'https://www.tek.id/'.$channel->path().'/'.fake()->slug(4).'-'.$externalId,
            'image_url' => 'https://static.tek.id/2026/09/21/'.fake()->slug(2).'.jpeg',
            'published_at' => now()->subHours(fake()->numberBetween(1, 200)),
        ];
    }

    public function withoutImage(): self
    {
        return $this->state(fn (): array => ['image_url' => null]);
    }
}
```

- [ ] **Step 7: Run the migration and the test**

```bash
php artisan migrate
php artisan test --compact --filter=NewsItemModelTest
```

Expected: PASS, 6 tests.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations database/factories app/Models tests/Feature/NewsItemModelTest.php
git commit -m "feat: add news_items table and NewsItem model

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 4: FetchTekChannel — parse one RSS feed

**Files:**
- Create: `tests/Fixtures/tek-gadget-rss.xml`
- Create: `app/Actions/News/FetchTekChannel.php`
- Test: `tests/Feature/News/FetchTekChannelTest.php`

**Interfaces:**
- Consumes: `App\News\TekChannel`, `App\News\ScrapedArticle` (Task 1).
- Produces: `App\Actions\News\FetchTekChannel` with `handle(TekChannel $channel): array` returning `list<ScrapedArticle>`. Throws `RuntimeException` when the response body is not parseable XML. Lets `Illuminate\Http\Client\RequestException` propagate on a non-2xx response.

**Background — the three traps this task exists to handle:**

1. `pubDate` is `2026-09-21 19:18:00`: not RFC-822 and carrying **no timezone**. It is Jakarta local time. `Carbon::parse()` would read it as the app's UTC and shift every article 7 hours into the future.
2. The feed's `media:content` element has a `url` attribute, but it points at the **article**, not an image. Do not read it. Images come from Task 5.
3. The stable article ID is the final hyphen-separated token of the URL (`b2qbp9uhh`). All 70 IDs across the seven live channels match `^[A-Za-z0-9]{6,}$` and are unique. The title slug in front of it is not stable — an editor fixing a typo changes it.

- [ ] **Step 1: Create the fixture**

Create `tests/Fixtures/tek-gadget-rss.xml`. This is real tek.id markup, trimmed to four items — three valid, one deliberately broken (no `<link>`) to pin the per-item skip:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom" version="2.0">
    <channel>
        <generator>https://www.tek.id</generator>
        <title>Berita tek.id</title>
        <description>Berita - berita tek.id pada kanal Gadget</description>
        <link>https://www.tek.id/gadget</link>
        <language>id</language>
        <item>
            <title><![CDATA[realme 16 Pro Harry Potter Edition Resmi Meluncur, Ini Harga dan Spesifikasinya]]></title>
            <link><![CDATA[https://www.tek.id/gadget/realme-16-pro-harry-potter-edition-meluncur-ini-harganya-b2qbp9uhh]]></link>
            <guid isPermaLink="false"><![CDATA[https://www.tek.id/gadget/realme-16-pro-harry-potter-edition-meluncur-ini-harganya-b2qbp9uhh]]></guid>
            <description><![CDATA[realme 16 Pro Harry Potter Edition meluncur di Indonesia seharga Rp10,9 juta, membawa kamera 200 MP dan desain Hogwarts yang berubah warna.]]></description>
            <media:content url="https://www.tek.id/gadget/realme-16-pro-harry-potter-edition-meluncur-ini-harganya-b2qbp9uhh" expression="full" type="image/jpeg"><![CDATA[realme 16 Pro Harry Potter Edition]]></media:content >
            <category><![CDATA[Gadget]]></category>
            <pubDate><![CDATA[2026-09-21 19:18:00]]></pubDate>
        </item>
        <item>
            <title><![CDATA[TCL C7L Punya 1.352 Zona Dimming, Kecerahan Tembus HDR 3.000 Nits]]></title>
            <link><![CDATA[https://www.tek.id/gadget/tcl-c7l-punya-1352-zona-dimming-tembus-hdr-3000-nits-b2qbb9ugd]]></link>
            <guid isPermaLink="false"><![CDATA[https://www.tek.id/gadget/tcl-c7l-punya-1352-zona-dimming-tembus-hdr-3000-nits-b2qbb9ugd]]></guid>
            <description><![CDATA[TCL memperkuat C7L SQD-Mini LED di Indonesia dengan 1.352 dimming zone, HDR 3.000 nits, dan audio Bang &amp; Olufsen.]]></description>
            <category><![CDATA[Gadget]]></category>
            <pubDate><![CDATA[2026-09-07 09:30:00]]></pubDate>
        </item>
        <item>
            <title><![CDATA[Asus ROG Pamer Monitor OLED 720Hz di Gamescom 2026]]></title>
            <link><![CDATA[https://www.tek.id/gadget/asus-rog-pamer-monitor-oled-720hz-di-gamescom-2026-b2qaY9ufR]]></link>
            <description><![CDATA[Monitor OLED 720Hz pertama Asus dipamerkan di Gamescom 2026.]]></description>
            <category><![CDATA[Gadget]]></category>
            <pubDate><![CDATA[2026-08-20 14:05:00]]></pubDate>
        </item>
        <item>
            <title><![CDATA[Item rusak tanpa tautan]]></title>
            <description><![CDATA[Item ini tidak punya link dan harus dilewati.]]></description>
            <category><![CDATA[Gadget]]></category>
            <pubDate><![CDATA[2026-08-19 10:00:00]]></pubDate>
        </item>
    </channel>
</rss>
```

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/News/FetchTekChannelTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\News\FetchTekChannel;
use App\News\TekChannel;
use Illuminate\Support\Facades\Http;

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

    $articles = app(FetchTekChannel::class)->handle(TekChannel::Gadget);

    expect($articles)->toHaveCount(3);
});

it('maps an item onto a scraped article', function (): void {
    fakeGadgetFeed();

    $article = app(FetchTekChannel::class)->handle(TekChannel::Gadget)[0];

    expect($article->externalId)->toBe('b2qbp9uhh')
        ->and($article->channel)->toBe(TekChannel::Gadget)
        ->and($article->title)->toBe('realme 16 Pro Harry Potter Edition Resmi Meluncur, Ini Harga dan Spesifikasinya')
        ->and($article->excerpt)->toStartWith('realme 16 Pro Harry Potter Edition meluncur di Indonesia')
        ->and($article->url)->toBe('https://www.tek.id/gadget/realme-16-pro-harry-potter-edition-meluncur-ini-harganya-b2qbp9uhh')
        ->and($article->imageUrl)->toBeNull();
});

it('reads the feed timestamp as Jakarta time and stores it as UTC', function (): void {
    fakeGadgetFeed();

    $article = app(FetchTekChannel::class)->handle(TekChannel::Gadget)[0];

    // Feed says 2026-09-21 19:18:00 with no offset; Jakarta is UTC+7.
    expect($article->publishedAt->toDateTimeString())->toBe('2026-09-21 12:18:00')
        ->and($article->publishedAt->timezoneName)->toBe('UTC');
});

it('decodes html entities in the excerpt', function (): void {
    fakeGadgetFeed();

    $article = app(FetchTekChannel::class)->handle(TekChannel::Gadget)[1];

    expect($article->excerpt)->toContain('Bang & Olufsen');
});

it('skips an item with no link rather than failing the channel', function (): void {
    fakeGadgetFeed();

    $ids = array_map(
        fn ($article): string => $article->externalId,
        app(FetchTekChannel::class)->handle(TekChannel::Gadget),
    );

    expect($ids)->toBe(['b2qbp9uhh', 'b2qbb9ugd', 'b2qaY9ufR']);
});

it('throws when the response body is not xml', function (): void {
    // Note: a body like "<html>maintenance</html>" would parse fine as XML.
    // Use something genuinely unparseable so the assertion means something.
    Http::fake(['www.tek.id/gadget/rss' => Http::response('{"error":"maintenance"}', 200)]);

    expect(fn () => app(FetchTekChannel::class)->handle(TekChannel::Gadget))
        ->toThrow(RuntimeException::class);
});

it('throws when the feed responds with a server error', function (): void {
    Http::fake(['www.tek.id/gadget/rss' => Http::response('', 500)]);

    expect(fn () => app(FetchTekChannel::class)->handle(TekChannel::Gadget))
        ->toThrow(Illuminate\Http\Client\RequestException::class);
});

it('returns an empty list for a feed with no items', function (): void {
    Http::fake([
        'www.tek.id/gadget/rss' => Http::response(
            '<?xml version="1.0"?><rss version="2.0"><channel><title>Kosong</title></channel></rss>',
            200,
        ),
    ]);

    expect(app(FetchTekChannel::class)->handle(TekChannel::Gadget))->toBe([]);
});

it('sends an identifying user agent', function (): void {
    fakeGadgetFeed();

    app(FetchTekChannel::class)->handle(TekChannel::Gadget);

    Http::assertSent(fn ($request): bool => str_contains($request->header('User-Agent')[0], 'BandinginBot'));
});
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `php artisan test --compact --filter=FetchTekChannelTest`
Expected: FAIL — `Class "App\Actions\News\FetchTekChannel" not found`.

- [ ] **Step 4: Write the action**

Create `app/Actions/News/FetchTekChannel.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\News;

use App\News\ScrapedArticle;
use App\News\TekChannel;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;

/**
 * Fetches and parses one tek.id RSS channel.
 *
 * Touches no database, so it is testable with Http::fake() alone. A whole-feed
 * problem throws and lets the caller fail that channel; a single malformed item
 * is skipped so one bad entry cannot cost us the other nine.
 */
final class FetchTekChannel
{
    /** @return list<ScrapedArticle> */
    public function handle(TekChannel $channel): array
    {
        $body = Http::withUserAgent((string) config('services.tek.user_agent'))
            ->timeout(15)
            ->retry(2, 500)
            ->get($channel->feedUrl())
            ->throw()
            ->body();

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            throw new RuntimeException(
                "tek.id {$channel->value} feed is not valid XML (".strlen($body).' bytes).'
            );
        }

        $articles = [];

        foreach ($xml->channel->item as $item) {
            $article = $this->toArticle($channel, $item);

            if ($article instanceof ScrapedArticle) {
                $articles[] = $article;
            }
        }

        return $articles;
    }

    private function toArticle(TekChannel $channel, SimpleXMLElement $item): ?ScrapedArticle
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
            channel: $channel,
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

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --compact --filter=FetchTekChannelTest`
Expected: PASS, 9 tests.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/News tests/Fixtures tests/Feature/News
git commit -m "feat: parse tek.id RSS channels into scraped articles

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 5: FetchArticleImage — read one og:image

The RSS feed carries no usable image, so the hero image comes from the article page's `og:image` meta tag. On a live article that tag sits within the first 2 KB of the response, so a targeted regex is both sufficient and cheaper than a DOM parse — and the page is invalid HTML anyway (its `<p>` tags are never closed), so a strict parser would be the wrong tool. **No part of the article body is read or stored.**

**Files:**
- Create: `app/Actions/News/FetchArticleImage.php`
- Test: `tests/Feature/News/FetchArticleImageTest.php`

**Interfaces:**
- Consumes: nothing from earlier tasks.
- Produces: `App\Actions\News\FetchArticleImage` with `handle(string $url): ?string`. Never throws.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/News/FetchArticleImageTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\News\FetchArticleImage;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function articlePage(string $head): string
{
    return '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">'.$head.'</head><body><p>isi</p></body></html>';
}

it('reads the og:image url', function (): void {
    Http::fake(['*' => Http::response(articlePage(
        '<meta property="og:image" content="https://static.tek.id/2026/09/21/realme-7QTJZ.jpeg" />'
    ))]);

    expect(app(FetchArticleImage::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))
        ->toBe('https://static.tek.id/2026/09/21/realme-7QTJZ.jpeg');
});

it('decodes html entities in the image url', function (): void {
    Http::fake(['*' => Http::response(articlePage(
        '<meta property="og:image" content="https://static.tek.id/a.jpg?w=600&amp;h=315" />'
    ))]);

    expect(app(FetchArticleImage::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))
        ->toBe('https://static.tek.id/a.jpg?w=600&h=315');
});

it('returns null when the page has no og:image', function (): void {
    Http::fake(['*' => Http::response(articlePage('<title>Tanpa gambar</title>'))]);

    expect(app(FetchArticleImage::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('returns null when the og:image is not an absolute url', function (): void {
    Http::fake(['*' => Http::response(articlePage('<meta property="og:image" content="/assets/fallback.png" />'))]);

    expect(app(FetchArticleImage::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('returns null on a 404', function (): void {
    Http::fake(['*' => Http::response('Not Found', 404)]);

    expect(app(FetchArticleImage::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});

it('returns null when the connection fails', function (): void {
    Http::fake(fn () => throw new ConnectionException('timed out'));

    expect(app(FetchArticleImage::class)->handle('https://www.tek.id/gadget/a-b2qbp9uhh'))->toBeNull();
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact --filter=FetchArticleImageTest`
Expected: FAIL — `Class "App\Actions\News\FetchArticleImage" not found`.

- [ ] **Step 3: Write the action**

Create `app/Actions/News/FetchArticleImage.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\News;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Reads the og:image meta tag from one tek.id article page.
 *
 * This is the only reason an article page is ever requested — the RSS feed
 * carries no usable image. The body is never parsed or stored.
 *
 * Every failure yields null: one article's missing image must never cost us the
 * article itself, nor the rest of its channel.
 */
final class FetchArticleImage
{
    public function handle(string $url): ?string
    {
        try {
            $response = Http::withUserAgent((string) config('services.tek.user_agent'))
                ->timeout(15)
                ->get($url);
        } catch (ConnectionException|Throwable) {
            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $matched = preg_match(
            '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i',
            $response->body(),
            $matches,
        );

        if ($matched !== 1) {
            return null;
        }

        $image = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return str_starts_with($image, 'http') ? $image : null;
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --compact --filter=FetchArticleImageTest`
Expected: PASS, 6 tests.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/News tests/Feature/News
git commit -m "feat: read og:image from tek.id article pages

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 6: ImportScrapedArticles — upsert the batch

**Files:**
- Create: `app/Actions/News/ImportScrapedArticles.php`
- Test: `tests/Feature/News/ImportScrapedArticlesTest.php`

**Interfaces:**
- Consumes: `App\News\ScrapedArticle` (Task 1), `App\Models\NewsItem` (Task 3).
- Produces: `App\Actions\News\ImportScrapedArticles` with:
  - `handle(array $articles): int` — upserts `list<ScrapedArticle>` keyed on `external_id`, returns the count of **newly created** rows.
  - `existingIds(array $articles): array` — returns `list<string>` of the `external_id`s among the given articles that are already in the database.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/News/ImportScrapedArticlesTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\News\ImportScrapedArticles;
use App\Models\NewsItem;
use App\News\ScrapedArticle;
use App\News\TekChannel;
use Carbon\CarbonImmutable;

function scrapedArticle(string $externalId = 'b2qbp9uhh', ?string $imageUrl = null, string $title = 'realme 16 Pro'): ScrapedArticle
{
    return new ScrapedArticle(
        externalId: $externalId,
        channel: TekChannel::Gadget,
        title: $title,
        excerpt: 'Meluncur di Indonesia.',
        url: 'https://www.tek.id/gadget/realme-'.$externalId,
        publishedAt: CarbonImmutable::parse('2026-09-21 12:18:00'),
        imageUrl: $imageUrl,
    );
}

it('creates a row for each new article', function (): void {
    $created = app(ImportScrapedArticles::class)->handle([
        scrapedArticle('b2qbp9uhh'),
        scrapedArticle('b2qbb9ugd'),
    ]);

    expect($created)->toBe(2)
        ->and(NewsItem::count())->toBe(2);
});

it('stores every field from the dto', function (): void {
    app(ImportScrapedArticles::class)->handle([scrapedArticle(imageUrl: 'https://static.tek.id/a.jpeg')]);

    $item = NewsItem::sole();

    expect($item->external_id)->toBe('b2qbp9uhh')
        ->and($item->source)->toBe('tek.id')
        ->and($item->channel)->toBe('Gadget')
        ->and($item->title)->toBe('realme 16 Pro')
        ->and($item->excerpt)->toBe('Meluncur di Indonesia.')
        ->and($item->url)->toBe('https://www.tek.id/gadget/realme-b2qbp9uhh')
        ->and($item->image_url)->toBe('https://static.tek.id/a.jpeg')
        ->and($item->published_at->toDateTimeString())->toBe('2026-09-21 12:18:00');
});

it('is idempotent across repeated runs', function (): void {
    $articles = [scrapedArticle('b2qbp9uhh'), scrapedArticle('b2qbb9ugd')];

    app(ImportScrapedArticles::class)->handle($articles);
    $secondRun = app(ImportScrapedArticles::class)->handle($articles);

    expect($secondRun)->toBe(0)
        ->and(NewsItem::count())->toBe(2);
});

it('updates a changed title in place', function (): void {
    app(ImportScrapedArticles::class)->handle([scrapedArticle(title: 'Judul lama')]);
    app(ImportScrapedArticles::class)->handle([scrapedArticle(title: 'Judul baru')]);

    expect(NewsItem::count())->toBe(1)
        ->and(NewsItem::sole()->title)->toBe('Judul baru');
});

it('does not blank an existing image when the new dto has none', function (): void {
    app(ImportScrapedArticles::class)->handle([scrapedArticle(imageUrl: 'https://static.tek.id/a.jpeg')]);
    app(ImportScrapedArticles::class)->handle([scrapedArticle(imageUrl: null)]);

    expect(NewsItem::sole()->image_url)->toBe('https://static.tek.id/a.jpeg');
});

it('reports which external ids it already has', function (): void {
    app(ImportScrapedArticles::class)->handle([scrapedArticle('b2qbp9uhh')]);

    $known = app(ImportScrapedArticles::class)->existingIds([
        scrapedArticle('b2qbp9uhh'),
        scrapedArticle('b2qbb9ugd'),
    ]);

    expect($known)->toBe(['b2qbp9uhh']);
});

it('reports no known ids for an empty batch', function (): void {
    expect(app(ImportScrapedArticles::class)->existingIds([]))->toBe([]);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact --filter=ImportScrapedArticlesTest`
Expected: FAIL — `Class "App\Actions\News\ImportScrapedArticles" not found`.

- [ ] **Step 3: Write the action**

Create `app/Actions/News/ImportScrapedArticles.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions\News;

use App\Models\NewsItem;
use App\News\ScrapedArticle;

/**
 * Upserts scraped articles into news_items.
 *
 * Makes no HTTP requests, so it tests against hand-built DTOs alone. Identity is
 * the external_id token, never the URL: tek.id URLs embed the title slug, so an
 * edited headline would otherwise create a duplicate row.
 */
final class ImportScrapedArticles
{
    /**
     * @param  list<ScrapedArticle>  $articles
     * @return int  the number of rows created
     */
    public function handle(array $articles): int
    {
        $created = 0;

        foreach ($articles as $article) {
            $item = NewsItem::firstOrNew(['external_id' => $article->externalId]);

            if (! $item->exists) {
                $created++;
            }

            $item->fill([
                'source' => 'tek.id',
                'channel' => $article->channel->value,
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'url' => $article->url,
                'published_at' => $article->publishedAt,
            ]);

            // A later fetch failure must not blank an image we already have.
            if ($article->imageUrl !== null) {
                $item->image_url = $article->imageUrl;
            }

            $item->save();
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

        return NewsItem::whereIn('external_id', $ids)
            ->pluck('external_id')
            ->all();
    }
}
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --compact --filter=ImportScrapedArticlesTest`
Expected: PASS, 7 tests.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Actions/News tests/Feature/News
git commit -m "feat: upsert scraped tek.id articles into news_items

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 7: The import command and its schedule

**Files:**
- Create: `app/Console/Commands/ImportTekNews.php` (via `make:command`)
- Modify: `routes/console.php`
- Test: `tests/Feature/News/ImportTekNewsCommandTest.php`

**Interfaces:**
- Consumes: `FetchTekChannel::handle()`, `FetchArticleImage::handle()`, `ImportScrapedArticles::handle()` and `::existingIds()` (Tasks 4–6), `TekChannel::cases()`.
- Produces: the `news:import-tek` Artisan command. Exit code `0` when every channel succeeded, `1` when any channel failed.

**Background:** the "already known" partition is what keeps the image fetch cheap. A steady-state run finds one or two new articles per channel and makes one or two extra requests; only a cold start fetches all ten. Without it, every hourly run would re-request seventy article pages.

- [ ] **Step 1: Generate the command**

```bash
php artisan make:command ImportTekNews --no-interaction
```

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/News/ImportTekNewsCommandTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\NewsItem;
use Illuminate\Support\Facades\Http;

/**
 * Fakes all seven feeds from the one saved fixture plus every article page.
 * Each channel therefore yields the same three articles, which is fine: the
 * external_id is unique per article, so the seven channels import 3 rows in
 * total, the last channel winning. Tests that care about per-channel counts
 * fake the channels individually instead.
 */
function fakeAllTekChannels(string $ogImage = 'https://static.tek.id/2026/09/21/realme-7QTJZ.jpeg'): void
{
    Http::fake([
        'www.tek.id/*/rss' => Http::response(
            file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')),
            200,
        ),
        'www.tek.id/*' => Http::response(
            '<html><head><meta property="og:image" content="'.$ogImage.'" /></head><body></body></html>',
            200,
        ),
    ]);
}

it('imports articles from every channel', function (): void {
    fakeAllTekChannels();

    $this->artisan('news:import-tek')->assertExitCode(0);

    expect(NewsItem::count())->toBe(3);

    Http::assertSentCount(7 + 3);
});

it('attaches the og:image to imported items', function (): void {
    fakeAllTekChannels();

    $this->artisan('news:import-tek');

    expect(NewsItem::first()->image_url)->toBe('https://static.tek.id/2026/09/21/realme-7QTJZ.jpeg');
});

it('fetches no article pages on a second run', function (): void {
    fakeAllTekChannels();
    $this->artisan('news:import-tek');

    Http::fake([
        'www.tek.id/*/rss' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')), 200),
        'www.tek.id/*' => Http::response('<html><head></head><body></body></html>', 200),
    ]);

    $this->artisan('news:import-tek')->assertExitCode(0);

    // Seven feeds and nothing else: every article was already known.
    Http::assertSentCount(7);
    expect(NewsItem::count())->toBe(3);
});

it('keeps importing when one channel fails, and reports failure', function (): void {
    Http::fake([
        'www.tek.id/gadget/rss' => Http::response('', 500),
        'www.tek.id/*/rss' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')), 200),
        'www.tek.id/*' => Http::response('<html><head></head><body></body></html>', 200),
    ]);

    $this->artisan('news:import-tek')->assertExitCode(1);

    // The six healthy channels still imported their articles.
    expect(NewsItem::count())->toBe(3);
});

it('survives a feed that is not xml', function (): void {
    Http::fake(['www.tek.id/*/rss' => Http::response('{"error":"maintenance"}', 200)]);

    $this->artisan('news:import-tek')->assertExitCode(1);

    expect(NewsItem::count())->toBe(0);
});

it('imports items without an image when the page has no og:image', function (): void {
    Http::fake([
        'www.tek.id/*/rss' => Http::response(file_get_contents(base_path('tests/Fixtures/tek-gadget-rss.xml')), 200),
        'www.tek.id/*' => Http::response('<html><head><title>Tanpa gambar</title></head><body></body></html>', 200),
    ]);

    $this->artisan('news:import-tek')->assertExitCode(0);

    expect(NewsItem::count())->toBe(3)
        ->and(NewsItem::whereNotNull('image_url')->count())->toBe(0);
});

it('is registered on the hourly schedule', function (): void {
    $events = app(Illuminate\Console\Scheduling\Schedule::class)->events();

    $command = collect($events)->first(fn ($event): bool => str_contains($event->command ?? '', 'news:import-tek'));

    expect($command)->not->toBeNull()
        ->and($command->expression)->toBe('0 * * * *');
});
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `php artisan test --compact --filter=ImportTekNewsCommandTest`
Expected: FAIL — the `news:import-tek` command does not exist.

- [ ] **Step 4: Write the command**

Replace `app/Console/Commands/ImportTekNews.php`:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\News\FetchArticleImage;
use App\Actions\News\FetchTekChannel;
use App\Actions\News\ImportScrapedArticles;
use App\News\ScrapedArticle;
use App\News\TekChannel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportTekNews extends Command
{
    protected $signature = 'news:import-tek';

    protected $description = 'Import article metadata from the tek.id RSS channels';

    public function handle(
        FetchTekChannel $fetchChannel,
        FetchArticleImage $fetchImage,
        ImportScrapedArticles $import,
    ): int {
        $created = 0;
        $failed = [];

        foreach (TekChannel::cases() as $channel) {
            try {
                $articles = $fetchChannel->handle($channel);
            } catch (Throwable $exception) {
                $failed[] = $channel->value;

                Log::warning('tek.id channel import failed', [
                    'channel' => $channel->value,
                    'message' => $exception->getMessage(),
                ]);

                $this->components->error("{$channel->value}: {$exception->getMessage()}");

                continue;
            }

            $created += $import->handle($this->withImages($articles, $import, $fetchImage));

            $this->components->info("{$channel->value}: ".count($articles).' item diperiksa.');
        }

        $this->components->info("Selesai. {$created} item baru disimpan.");

        if ($failed !== []) {
            $this->components->error('Kanal gagal: '.implode(', ', $failed));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Only genuinely new articles get an image fetch. In steady state this is one
     * or two extra requests per channel rather than ten.
     *
     * @param  list<ScrapedArticle>  $articles
     * @return list<ScrapedArticle>
     */
    private function withImages(array $articles, ImportScrapedArticles $import, FetchArticleImage $fetchImage): array
    {
        $known = $import->existingIds($articles);

        return array_map(
            fn (ScrapedArticle $article): ScrapedArticle => in_array($article->externalId, $known, true)
                ? $article
                : $article->withImage($fetchImage->handle($article->url)),
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

use App\Console\Commands\ImportTekNews;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ImportTekNews::class)->hourly()->withoutOverlapping();
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php artisan test --compact --filter=ImportTekNewsCommandTest`
Expected: PASS, 7 tests.

If `Http::assertSentCount(7 + 3)` is off, print the actual requests with `Http::recorded()` and reconcile — the expected figure is seven feed requests plus one article-page request per distinct new article (three, since all seven channels serve the same fixture).

- [ ] **Step 7: Run the whole suite**

Run: `composer test`
Expected: PASS. Nothing so far touches existing behaviour except the Task 2 trait extraction.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Console routes/console.php tests/Feature/News
git commit -m "feat: add hourly news:import-tek command

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 8: Filament resource

The rows are owned by the importer, so the resource is list-and-delete only. A hand-edited row would be silently overwritten on the next hourly run; disabling create and edit says that in the UI rather than in a comment. `NewsletterSubscriberResource` is the existing precedent for this shape — read it before starting.

**Files:**
- Create: `app/Filament/Resources/NewsItems/NewsItemResource.php`
- Create: `app/Filament/Resources/NewsItems/Pages/ListNewsItems.php`
- Create: `app/Filament/Resources/NewsItems/Tables/NewsItemsTable.php`
- Test: `tests/Feature/Filament/NewsItemResourceTest.php`

**Interfaces:**
- Consumes: `App\Models\NewsItem` (Task 3), `App\News\TekChannel` (Task 1).
- Produces: a Filament resource under navigation group `Konten`, route `index` only.

- [ ] **Step 1: Read the precedent**

Read `app/Filament/Resources/NewsletterSubscribers/` in full — the resource, its table, and its single List page. Match its structure exactly. Note especially `canCreate(): bool { return false; }`.

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/Filament/NewsItemResourceTest.php`:

```php
<?php

declare(strict_types=1);

use App\Filament\Resources\NewsItems\NewsItemResource;
use App\Filament\Resources\NewsItems\Pages\ListNewsItems;
use App\Models\NewsItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('lists ingested news items', function (): void {
    $item = NewsItem::factory()->create(['title' => 'realme 16 Pro Harry Potter Edition']);

    Livewire::test(ListNewsItems::class)->assertCanSeeTableRecords([$item]);
});

it('filters by channel', function (): void {
    $gadget = NewsItem::factory()->create(['channel' => 'Gadget']);
    $culture = NewsItem::factory()->create(['channel' => 'Culture']);

    Livewire::test(ListNewsItems::class)
        ->filterTable('channel', 'Gadget')
        ->assertCanSeeTableRecords([$gadget])
        ->assertCanNotSeeTableRecords([$culture]);
});

it('sorts newest first by default', function (): void {
    $older = NewsItem::factory()->create(['published_at' => now()->subWeek()]);
    $newer = NewsItem::factory()->create(['published_at' => now()]);

    Livewire::test(ListNewsItems::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

it('cannot be created by hand', function (): void {
    expect(NewsItemResource::canCreate())->toBeFalse();
});

it('exposes only an index page', function (): void {
    expect(array_keys(NewsItemResource::getPages()))->toBe(['index']);
});

it('opens the index page', function (): void {
    $this->get(NewsItemResource::getUrl('index'))->assertSuccessful();
});
```

- [ ] **Step 3: Run the test to verify it fails**

Run: `php artisan test --compact --filter=NewsItemResourceTest`
Expected: FAIL — `Class "App\Filament\Resources\NewsItems\NewsItemResource" not found`.

- [ ] **Step 4: Write the resource**

Create `app/Filament/Resources/NewsItems/NewsItemResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsItems;

use App\Filament\Resources\NewsItems\Pages\ListNewsItems;
use App\Filament\Resources\NewsItems\Tables\NewsItemsTable;
use App\Models\NewsItem;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class NewsItemResource extends Resource
{
    protected static ?string $model = NewsItem::class;

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Kabar Teknologi';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRss;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return NewsItemsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /** Rows are owned by news:import-tek; a hand-made row would be overwritten. */
    public static function canCreate(): bool
    {
        return false;
    }

    /** Likewise for edits — the importer refreshes title and excerpt every run. */
    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNewsItems::route('/'),
        ];
    }
}
```

If `Heroicon::OutlinedRss` does not exist in this Filament version, use `Heroicon::OutlinedNewspaper`.

- [ ] **Step 5: Write the table**

Create `app/Filament/Resources/NewsItems/Tables/NewsItemsTable.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsItems\Tables;

use App\News\TekChannel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewsItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Gambar')
                    ->square()
                    ->toggleable(),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->wrap()
                    ->limit(80)
                    ->url(fn ($record): string => $record->url, shouldOpenInNewTab: true),
                TextColumn::make('channel')
                    ->label('Kanal')
                    ->badge()
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->label('Terbit')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Diambil')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label('Kanal')
                    ->options(array_combine(
                        array_column(TekChannel::cases(), 'value'),
                        array_column(TekChannel::cases(), 'value'),
                    )),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->deferFilters()
            ->defaultSort('published_at', 'desc')
            ->recordActions([
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

- [ ] **Step 6: Write the list page**

Create `app/Filament/Resources/NewsItems/Pages/ListNewsItems.php`:

```php
<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsItems\Pages;

use App\Filament\Resources\NewsItems\NewsItemResource;
use Filament\Resources\Pages\ListRecords;

class ListNewsItems extends ListRecords
{
    protected static string $resource = NewsItemResource::class;

    /** No CreateAction: these rows come from news:import-tek, not from a human. */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
```

- [ ] **Step 7: Run the tests to verify they pass**

Run: `php artisan test --compact --filter="NewsItemResourceTest|AdminBoundaryTest|ResourcePagesTest"`
Expected: PASS. `AdminBoundaryTest` confirms the new resource does not import from `App\Livewire`.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Filament/Resources/NewsItems tests/Feature/Filament/NewsItemResourceTest.php
git commit -m "feat: add read-only Filament resource for aggregated news

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 9: The public "Kabar Teknologi" feed

**Files:**
- Create: `app/Livewire/NewsFeed.php`
- Create: `resources/views/livewire/news-feed.blade.php`
- Create: `resources/views/components/news-card.blade.php`
- Modify: `resources/views/livewire/berita-page.blade.php`
- Test: `tests/Feature/NewsFeedTest.php`
- Test: `tests/Feature/BeritaPageTest.php` (append)

**Interfaces:**
- Consumes: `App\Models\NewsItem` (Task 3).
- Produces: `App\Livewire\NewsFeed` with a `#[Computed] items(): Collection<int, NewsItem>` returning the latest 8 published items, and the `<x-news-card :item="$item" />` Blade component.

**The constraint this task must not break:** these items must never enter the `/berita` article grid. That grid renders `<x-article-card>`, which links to `route('berita.show', $article->slug)` — a page on this site. An external item there would 404 or imply bandingin hosts tek.id's articles, which is the outcome the whole feature is shaped to avoid. Keep the two sections separate and keep the link `target="_blank" rel="noopener nofollow"`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/NewsFeedTest.php`:

```php
<?php

declare(strict_types=1);

use App\Livewire\NewsFeed;
use App\Models\NewsItem;
use Livewire\Livewire;

it('lists the newest published items', function (): void {
    $newest = NewsItem::factory()->create([
        'title' => 'realme 16 Pro Harry Potter Edition',
        'published_at' => now(),
    ]);

    Livewire::test(NewsFeed::class)->assertSee($newest->title);
});

it('hides items with no published date', function (): void {
    NewsItem::factory()->create(['title' => 'Belum terbit', 'published_at' => null]);

    Livewire::test(NewsFeed::class)->assertDontSee('Belum terbit');
});

it('caps the list at eight items', function (): void {
    NewsItem::factory()->count(12)->create();

    $component = Livewire::test(NewsFeed::class)->instance();

    expect($component->items())->toHaveCount(8);
});

it('orders newest first', function (): void {
    $older = NewsItem::factory()->create(['published_at' => now()->subWeek()]);
    $newer = NewsItem::factory()->create(['published_at' => now()]);

    $component = Livewire::test(NewsFeed::class)->instance();

    expect($component->items()->first()->id)->toBe($newer->id)
        ->and($component->items()->last()->id)->toBe($older->id);
});

it('links off-site without passing seo equity', function (): void {
    $item = NewsItem::factory()->create(['url' => 'https://www.tek.id/gadget/realme-b2qbp9uhh']);

    Livewire::test(NewsFeed::class)
        ->assertSee('https://www.tek.id/gadget/realme-b2qbp9uhh')
        ->assertSee('rel="noopener nofollow"', escape: false)
        ->assertSee('target="_blank"', escape: false);
});

it('credits the source', function (): void {
    NewsItem::factory()->create();

    Livewire::test(NewsFeed::class)->assertSee('tek.id');
});

it('renders an item that has no image', function (): void {
    $item = NewsItem::factory()->withoutImage()->create(['title' => 'Tanpa gambar']);

    Livewire::test(NewsFeed::class)->assertSee($item->title);
});

it('renders nothing when there are no items', function (): void {
    Livewire::test(NewsFeed::class)->assertDontSee('Kabar Teknologi');
});
```

Append to `tests/Feature/BeritaPageTest.php`:

```php
it('shows aggregated news in its own section, not the article grid', function (): void {
    $item = App\Models\NewsItem::factory()->create([
        'title' => 'Kabar dari luar',
        'url' => 'https://www.tek.id/gadget/kabar-b2qbp9uhh',
    ]);

    $response = $this->get(route('berita'));

    $response->assertSuccessful()
        ->assertSee('Kabar Teknologi')
        ->assertSee($item->url);

    // The item must not have acquired a page on this site.
    $this->get('/berita/kabar-b2qbp9uhh')->assertNotFound();
});

it('keeps aggregated news out of the article count', function (): void {
    App\Models\NewsItem::factory()->count(5)->create();

    $before = App\Models\Article::published()->count();

    Livewire::test(BeritaPage::class)->assertSee($before.' artikel');
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --compact --filter="NewsFeedTest"`
Expected: FAIL — `Class "App\Livewire\NewsFeed" not found`.

- [ ] **Step 3: Write the component**

Create `app/Livewire/NewsFeed.php`:

```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\NewsItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Aggregated headlines from external publishers.
 *
 * Every card links off-site. These items deliberately have no route on this
 * site — see docs/superpowers/specs/2026-09-08-tek-id-news-aggregation-design.md.
 */
class NewsFeed extends Component
{
    public const int LIMIT = 8;

    /** @return Collection<int, NewsItem> */
    #[Computed]
    public function items(): Collection
    {
        return NewsItem::published()
            ->latest('published_at')
            ->limit(self::LIMIT)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.news-feed');
    }
}
```

- [ ] **Step 4: Write the card component**

Create `resources/views/components/news-card.blade.php`:

```blade
@props(['item'])

@php
    /** @var \App\Models\NewsItem $item */
@endphp

<a
    href="{{ $item->url }}"
    target="_blank"
    rel="noopener nofollow"
    {{ $attributes->merge(['class' => 'block w-full text-left text-ink transition-transform duration-200 hover:-translate-y-[3px] hover:text-ink']) }}
>
    @if ($item->image_url)
        <img
            src="{{ $item->image_url }}"
            alt="{{ $item->title }}"
            loading="lazy"
            referrerpolicy="no-referrer"
            class="aspect-16/10 w-full rounded-2xl object-cover"
        >
    @else
        <span class="photo-slot relative block aspect-16/10 rounded-2xl"></span>
    @endif

    <span class="mt-3.5 flex items-center gap-[9px] font-mono text-[10.5px] tracking-[.13em] uppercase">
        <span class="text-brand">{{ $item->channel }}</span>
        <span class="text-[#c3cee3]">·</span>
        <span class="text-muted">{{ $item->dateShort() }}</span>
    </span>

    <span class="mt-[9px] block font-display text-[17.5px] leading-[1.28] font-extrabold tracking-[-.02em] text-ink">{{ $item->title }}</span>

    <span class="mt-[9px] block text-sm leading-relaxed text-muted">{{ $item->excerpt }}</span>

    <span class="mt-3 flex items-center gap-1.5 font-mono text-[11px] text-muted">
        <span>{{ $item->source }}</span>
        <span aria-hidden="true">↗</span>
    </span>
</a>
```

The `text-[#c3cee3]` separator is copied verbatim from `article-card.blade.php` so the two card types line up; it is the one hardcoded value already established in that component.

- [ ] **Step 5: Write the component view**

Create `resources/views/livewire/news-feed.blade.php`:

```blade
<div>
    @if ($this->items->isNotEmpty())
        <section class="mx-auto mt-[clamp(56px,7vw,96px)] max-w-[1140px] px-[clamp(16px,2.4vw,24px)]">
            <div class="flex flex-wrap items-baseline justify-between gap-3.5 border-b border-line pb-[18px]">
                <h2 class="font-display text-[clamp(20px,2.2vw,26px)] font-extrabold tracking-[-.026em]">Kabar Teknologi</h2>
                <span class="font-mono text-[11.5px] text-muted">Dari tek.id</span>
            </div>

            <p class="mt-3.5 max-w-[560px] text-sm leading-[1.65] text-muted">
                Berita teknologi pilihan dari media lain. Klik untuk membaca di situs aslinya.
            </p>

            <div class="mt-[clamp(24px,3vw,36px)] grid grid-cols-[repeat(auto-fit,minmax(min(100%,280px),1fr))] gap-[clamp(18px,2.2vw,28px)]">
                @foreach ($this->items as $item)
                    <x-news-card wire:key="news-{{ $item->id }}" :item="$item" />
                @endforeach
            </div>
        </section>
    @endif
</div>
```

- [ ] **Step 6: Mount it on the Berita page**

In `resources/views/livewire/berita-page.blade.php`, insert `<livewire:news-feed />` on its own line immediately **before** the newsletter `<section class="mx-auto mt-[clamp(56px,7vw,96px)] ...">` block near the end of the file — so the order is: article grid, then Kabar Teknologi, then the newsletter box, then `<x-site-footer />`.

Do not touch the `@foreach ($this->articles as $article)` grid above it.

- [ ] **Step 7: Run the tests to verify they pass**

Run: `php artisan test --compact --filter="NewsFeedTest|BeritaPageTest"`
Expected: PASS.

- [ ] **Step 8: Build assets and check the page renders**

```bash
npm run build
```

Then confirm `/berita` renders. Use the Boost `get-absolute-url` tool for the correct host rather than guessing it.

- [ ] **Step 9: Run the whole suite**

Run: `composer test`
Expected: PASS, including `StaticLayerRetiredTest` (no `App\Support` or `App\Data` references) and `AdminBoundaryTest`.

- [ ] **Step 10: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Livewire resources/views tests/Feature
git commit -m "feat: add Kabar Teknologi link-out feed to the berita page

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Task 10: Record the conventions

The next agent should inherit the traps rather than rediscover them.

**Files:**
- Create: `.ai/rules/news.md` (via the Boost `record-rule` MCP tool)
- Modify: `.ai/rules/index.md`

- [ ] **Step 1: Record the rule**

Use the Boost `record-rule` MCP tool — not a hand-written file, and not any native memory tool.

- `glob`: `app/News/**,app/Actions/News/**,app/Console/Commands/ImportTekNews.php`
- `title`: `External news aggregation`
- `note`:

```
news_items rows are aggregated from tek.id and are LINK-OUT ONLY: no body is
ever fetched, parsed, stored or rendered, and the rows deliberately have no
slug so they can never resolve to a page on this site. Public cards must keep
target="_blank" rel="noopener nofollow" — without nofollow every card passes
SEO equity to a competitor. Keep them out of the /berita article grid, which
renders x-article-card and links to route('berita.show').

Identity is external_id, the final hyphen-separated token of the tek.id URL
("...-b2qbp9uhh"), never the URL itself: tek.id URLs embed the title slug, so
an edited headline would create a duplicate row.

The RSS pubDate is "Y-m-d H:i:s" in Asia/Jakarta with NO timezone, and the app
runs in UTC. Always parse with CarbonImmutable::createFromFormat(..., 'Asia/
Jakarta')->utc(). Carbon::parse() silently shifts every article by 7 hours.

The feed carries no images: its media:content url points at the article, not an
image. Images come from the article page's og:image, fetched for NEW items only
(ImportScrapedArticles::existingIds partitions them). A failed image fetch must
never blank an image already stored.

Feeds return 10 items each; the per-channel sitemap has 50 and also carries
news:keywords (tags), and POST /{channel}/more-category walks the deeper archive
with a CSRF token scraped from the page. Both are unused, and recorded as the
known options if backfill is ever wanted.
```

- [ ] **Step 2: Add the row to the rules index**

In `.ai/rules/index.md`, add this row to the table:

```
| app/News/**, app/Actions/News/** | .ai/rules/news.md |
```

- [ ] **Step 3: Commit**

```bash
git add .ai/rules
git commit -m "docs: record the external news aggregation rules

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

## Verification checklist

Run before declaring the feature done:

- [ ] `composer test` — full suite green
- [ ] `vendor/bin/pint --dirty --format agent` — clean
- [ ] `php artisan news:import-tek` against the **live** site imports real rows (this is the only step that hits the network; everything else is faked)
- [ ] `php artisan news:import-tek` a second time reports `0 item baru disimpan`
- [ ] `/berita` shows the Kabar Teknologi section, its cards open tek.id in a new tab
- [ ] `php artisan schedule:list` shows `news:import-tek` hourly
- [ ] The admin's Kabar Teknologi resource lists rows and offers no Create button
