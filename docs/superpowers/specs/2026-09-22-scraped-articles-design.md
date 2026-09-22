# Scraped Articles & Managed Sources — Design

**Date:** 2026-09-22
**Status:** Approved, ready for implementation planning
**Supersedes:** `2026-09-08-tek-id-news-aggregation-design.md`

## Purpose

Scrape full articles from a list of sources managed in the admin panel and
publish them as first-class Articles on bandingin.id, with their own
`/berita/{slug}` pages.

This **reverses** the link-out design of the previous spec. Where that design
deliberately stored no body and gave scraped items no route on this site, this
one stores the full body and publishes it.

## Recorded decision on republication

The previous design was link-out specifically to avoid republishing a commercial
publisher's text. The owner was told, in these terms, that publishing tek.id's
article text on bandingin.id without a licence is copyright infringement
regardless of attribution, and that the practical exposure is a takedown request
or a search-ranking penalty for duplicate content. They chose verbatim,
auto-published anyway. That is their decision to make, it is recorded here so no
future reader assumes it was an oversight, and it is not re-litigated below.

Two mitigations are in the design because they are cheap: a visible "Sumber"
credit linking to the original, and a `<link rel="canonical">` pointing at
tek.id, which is the standard signal that reduces the duplicate-content penalty.

## Source reconnaissance

Findings verified against live tek.id on 2026-09-22. The RSS findings from the
previous spec still hold and are not repeated: seven channels at
`/{channel}/rss`, 10 items each, `pubDate` is `Y-m-d H:i:s` in Asia/Jakarta with
no offset, and the feed carries no usable image.

### The article page body

The body is `div.artikel__content`. Two properties make naive extraction wrong:

1. **The `<p>` tags are never closed.** A strict parser nests them into a
   staircase. `DOMDocument` repairs this as a side effect of parsing, which is
   why it is the right tool here rather than a regex.
2. **Ads and a related-articles widget are injected inline, mid-body.** A
   `div.bacajuga` "Baca Juga" block and `flying_carpet` / `div-gpt-ad` ad
   containers sit between paragraphs.

A prototype confirmed that parsing with `DOMDocument`, then removing this XPath
set from within the content node, yields clean prose:

```
.//div[contains(@class,"bacajuga")] | .//div[contains(@class,"flying_carpet")]
| .//section[contains(@class,"flying_carpet")] | .//script | .//style
| .//iframe | .//div[contains(@id,"div-gpt-ad")]
| .//div[contains(@class,"sharethis")] | .//ins
```

On a real article this produced 23 paragraphs, 6.5 KB, with `Baca Juga`,
`googletag`, `bacajuga`, `flying_carpet`, `div-gpt` and `<script>` all absent.
The output needs whitespace normalising — the source is heavily indented.

The hero image is **outside** the body, in `.artikel__imagecontent img`, on
`static.tek.id`. Other page selectors: `h1.artikel__title`,
`.artikel__summary p` (excerpt, matches `og:description`), `.tekdate`,
`.tagdetail`.

### Rendering already sanitises

`/berita/{slug}` renders through `App\Actions\RenderArticleBody`, which wraps
Filament's `RichContentRenderer`. Tested against deliberately hostile input, it
strips `<script>`, `onclick` and `javascript:` hrefs. **Third-party HTML is
therefore safe to render without building a sanitiser.**

It does *not* remove unwanted content, though: `<div class="bacajuga">Baca
Juga</div>` came back as the bare text `Baca Juga`. Unknown elements are
unwrapped, not dropped. The junk must be stripped at extraction time or its text
leaks into the published article.

`articles.body` is a plain HTML string (verified against a seeded row), not
Tiptap JSON, so extracted HTML can be stored directly.

## Architecture

### Sources become data

New table `scrape_sources`, new model `App\Models\ScrapeSource`:

```
id
name          string   "tek.id — Gadget"
feed_url      string   unique
category      string   the ArticleCategory articles from this feed become
is_active     boolean  default true
last_run_at   timestamp nullable
last_error    text      nullable
timestamps
```

Seeded with seven rows for tek.id's channels. Adding another site is a row, not
a deploy. `is_active` pauses a source without losing its history.

`last_run_at` and `last_error` exist so the admin table shows whether ingestion
is healthy — without them a silently broken feed is indistinguishable from a
quiet one.

**`App\News\TekChannel` is deleted.** Its only job was knowing the seven feed
URLs, which is now the table's.

### Category mapping

`articles.category` is non-null and drives the public category chips. tek.id's
seven channels do not map onto the four `ArticleCategory` cases, so the mapping
is configuration, not code: each source row names the category its articles
become. No enum change.

### Articles gain provenance

Four nullable columns on `articles`:

| Column | Purpose |
| --- | --- |
| `source` | `'tek.id'`. **Null means a human wrote it.** |
| `source_url` | canonical link to the original; unique |
| `external_id` | the stable `b2qbp9uhh` URL token; the dedupe key |
| `image_url` | hero image; also usable by hand-written articles |

`image_url` is new capability, not just plumbing: `articles` has no image column
today, and both `x-article-card` and the detail page render `.photo-slot`
placeholders. Scraped articles arrive with real images, and hand-written ones
can now have them too.

**The safety rule:** every importer query is scoped `whereNotNull('source')`.
The importer can create and update scraped rows and can never modify or delete a
row a human wrote, even on a slug collision. This is asserted by test.

### The pipeline

| Class | Responsibility | Replaces |
| --- | --- | --- |
| `App\Actions\News\FetchFeed` | takes a `ScrapeSource`, returns `list<ScrapedArticle>` | `FetchTekChannel` |
| `App\Actions\News\FetchArticleContent` | GETs the article page, returns cleaned body HTML + hero image URL | `FetchArticleImage` |
| `App\Actions\News\ImportScrapedArticles` | upserts into `articles` | rewritten |
| `App\Console\Commands\ImportNews` | signature `news:import`; loops active sources | `ImportTekNews` (`news:import-tek`, deleted) |

`App\News\ScrapedArticle` gains `?string $body` alongside `?string $imageUrl`,
and `withImage()` becomes `withContent(?string $body, ?string $imageUrl)`.

Extraction selectors are hardcoded for tek.id. They belong on the source row
only when a second site with different markup is actually added; building that
configurability now is speculative.

### Derived fields

`ImportScrapedArticles` fills the columns the feed does not provide:

- **slug** — `Str::slug($title)`, with a numeric suffix on collision. Collisions
  are checked against the whole table, so a scraped article can never take a
  slug a human article already holds.
- **read_minutes** — word count of the body text divided by 200, minimum 1.
  `articles.read_minutes` is an unsigned tiny int, so the value is capped at 255.
- **tags** — `[]`. `articles.tags` is a non-null JSON column; tek.id's
  `.tagdetail` block is a possible future source and is out of scope.
- **excerpt** — the feed's `description`.
- **published_at** — the feed's `pubDate`, Jakarta to UTC.
- **category** — from the source row.

## Data flow

```
schedule (hourly, withoutOverlapping)
  └─ ImportNews
       └─ for each ScrapeSource where is_active:
            ├─ FetchFeed ──HTTP──> source.feed_url
            │     └─ SimpleXML → list<ScrapedArticle>   (skips malformed items)
            ├─ partition: which external_ids are already in articles?
            ├─ for each NEW item only:
            │     └─ FetchArticleContent ──HTTP──> article URL
            │           └─ DOMDocument → body + image → withContent()
            ├─ ImportScrapedArticles → upsert into articles
            └─ stamp last_run_at / last_error on the source row
       └─ report counts; exit non-zero if any source failed
```

Registered in `routes/console.php` as
`Schedule::command(ImportNews::class)->hourly()->withoutOverlapping();`,
replacing the `ImportTekNews` registration.

Only new articles are fetched, so a steady-state run costs one request per
source plus one per genuinely new article. This is unchanged from the previous
design; the page fetch now returns body as well as image.

## What is deleted

The link-out layer is retired in full: the `news_items` table and its 70 rows,
`App\Models\NewsItem`, `NewsItemFactory`, `App\Livewire\NewsFeed`,
`resources/views/livewire/news-feed.blade.php`,
`resources/views/components/news-card.blade.php`, the `NewsItems` Filament
resource, `App\News\TekChannel`, and their tests.

The owner chose to start fresh rather than backfill, accepting that articles
which have rotated out of the ten-item feeds are not recoverable.

`/berita` returns to a single grid. Scraped articles appear in it and in the
category chips like any other article.

`App\Models\Concerns\FormatsIndonesianDate` stays — it is used by `Article`.

`tests/Feature/Filament/ResourcePagesTest.php` asserts the registered resource
list exhaustively: removing `NewsItemResource` and adding `ScrapeSourceResource`
both require editing `listableResources()` there.

## Attribution on the public site

Scraped articles are visually indistinguishable from your own except for a
credit line. On `/berita/{slug}`, when `source` is not null:

- a "Sumber: tek.id" line linking to `source_url`, `rel="noopener"`
- `<link rel="canonical" href="{{ source_url }}">` in the page head

The canonical tag is the standard signal telling search engines the original is
authoritative, and is the cheapest available mitigation of the duplicate-content
penalty.

## Failure handling

- **Per-source isolation.** One source failing must not abort the others. Each
  is caught, its `last_error` stamped, and the command continues, exiting
  non-zero at the end if any failed.
- **A failed body extraction skips that article entirely.** Unlike the image in
  the previous design, the body is the point: an article with no body must not
  be published. It is counted and logged, and the next run retries it because it
  was never stored.
- **Timeouts and retries:** `->timeout(15)->retry(2, 500)`.
- **Malformed XML** and **malformed items** behave as in the previous design: a
  whole-feed problem fails that source; a single bad item is skipped.
- **Idempotency.** Two consecutive runs produce no new rows and no duplicates.
- **Inactive sources are skipped** without being counted as failures.
- **Politeness:** identifying User-Agent, sequential requests.

## Testing

| Test | Proves |
| --- | --- |
| `FetchArticleContentTest` | A saved real article-page fixture yields prose and **does not** contain `Baca Juga`, `googletag`, `bacajuga`, `flying_carpet` or `<script>`; the hero image is read; a page with no `.artikel__content` returns null rather than throwing. |
| `FetchFeedTest` | As the previous `FetchTekChannelTest`, but driven by a `ScrapeSource` row: parsing, `external_id` extraction, Jakarta→UTC, per-item skip, non-XML throw. |
| `ImportScrapedArticlesTest` | Creates Articles with slug, category from the source, computed `read_minutes`, body and image; idempotent; updates in place; **never touches a row where `source` is null**; a slug colliding with a human article gets a suffix instead of overwriting. |
| `ImportNewsCommandTest` | Active sources only; one failing source does not stop the others and stamps `last_error`; exit codes; no article-page fetches on a second run. |
| `ScrapeSourceResourceTest` | The admin resource lists, creates, edits and toggles sources. |
| `BeritaPageTest` / `BeritaDetailPageTest` | Scraped articles appear in the grid and open at `/berita/{slug}`; the detail page shows the Sumber credit and canonical tag for scraped articles and neither for hand-written ones. |

## Out of scope

- Any rewriting of the scraped text.
- Per-source extraction selectors — revisit when a second site is added.
- Tags from `.tagdetail`.
- Backfilling articles that have rotated out of the feeds.
- The archive endpoint `POST /{channel}/more-category`, still available if
  deeper history is ever wanted.
