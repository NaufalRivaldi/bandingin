# tek.id News Aggregation — Design

**Date:** 2026-09-08
**Status:** Approved, ready for implementation planning

## Purpose

Ingest article metadata from tek.id's seven public channels and surface it on
bandingin.id as a link-out news feed: headline, excerpt, image and date, each
linking back to the original article on tek.id.

This is **aggregation, not republication**. Article bodies are never fetched,
never stored, and never rendered. No ingested item ever gets a bandingin.id
page of its own.

## Source reconnaissance

Findings from probing tek.id on 2026-09-08.

`robots.txt` is fully permissive (`User-Agent: *`, no `Disallow`) and advertises
`https://www.tek.id/sitemap.xml`.

Three ingestion surfaces exist:

| Source | URL | Yields | Cap |
| --- | --- | --- | --- |
| RSS per channel | `/{channel}/rss` | title, link, description, category, pubDate, image | 10 items, no body |
| News sitemap per channel | `/{channel}/sitemap.xml` | URL, lastmod, news metadata | 50 items |
| Category load-more | `POST /{channel}/more-category` | unbounded archive | needs CSRF token scraped from page HTML, plus `next` offset and `categorynext` id |

**Discovery is RSS only.** It carries every field this design needs except the
image, it is a documented public interface, and 7 requests per run is negligible
load. Article pages are fetched afterwards for new items only, and solely for
their `og:image` meta tag. The sitemap and load-more endpoints are recorded here
as known options should the scope later grow to backfill; they are explicitly out
of scope now.

### RSS item shape

```xml
<item>
  <title><![CDATA[TCL C7L Punya 1.352 Zona Dimming, ...]]></title>
  <link><![CDATA[https://www.tek.id/gadget/tcl-c7l-punya-1352-zona-dimming-...-b2qbb9ugd]]></link>
  <guid isPermaLink="false"><![CDATA[https://www.tek.id/gadget/...-b2qbb9ugd]]></guid>
  <description><![CDATA[TCL memperkuat C7L SQD-Mini LED di Indonesia ...]]></description>
  <media:content url="..." expression="full" type="image/jpeg"><![CDATA[title]]></media:content>
  <category><![CDATA[Gadget]]></category>
  <pubDate><![CDATA[2026-09-08 18:05:00]]></pubDate>
</item>
```

Two traps confirmed in the live feed:

1. **`pubDate` is not RFC-822 and carries no timezone.** It is `Y-m-d H:i:s` in
   Jakarta local time. `Carbon::parse()` would read it as app timezone and shift
   every timestamp. It must be parsed as
   `Carbon::createFromFormat('Y-m-d H:i:s', $raw, 'Asia/Jakarta')`.
2. **The feed carries no images.** A `media:content` element is present, but its
   `url` attribute points at the *article* URL rather than an image file — a bug
   on tek.id's side. There are no `<enclosure>` tags and no `static.tek.id`
   references anywhere in the feed. The per-channel sitemap has no images either.
   The only image source is the article page's `og:image` meta tag, which drives
   the `FetchArticleImage` step below.

The sitemap does carry `news:keywords` (per-article tags), which the RSS feed
does not. Not used in v1; recorded as a known future option.

The article page (`h1.artikel__title`, `.artikel__content`, `.tekdate`,
`.tagdetail`) was also mapped during reconnaissance. The article page **is**
fetched, but only to read the `og:image` meta tag — the body is never parsed or
stored. Body scraping was evaluated and deliberately declined; see Out of scope.

The article page's body also contains inline ad blocks and a `.bacajuga` related
-articles block mid-content, and its `<p>` tags are never closed. Recorded so a
future reader knows body extraction is non-trivial, not a simple selector grab.

## Architecture

Five units, each with one job and a clean seam between HTTP and persistence.

| Class | Responsibility | Depends on |
| --- | --- | --- |
| `App\News\TekChannel` (enum) | The 7 channels, their feed URLs, display labels | nothing |
| `App\News\ScrapedArticle` (readonly DTO) | One normalised feed item | nothing |
| `App\Actions\News\FetchTekChannel` | GET one RSS feed, parse XML, return `list<ScrapedArticle>`. **No database.** | HTTP client |
| `App\Actions\News\FetchArticleImage` | GET one article page, return its `og:image` URL or null. **No database, no body parsing.** | HTTP client |
| `App\Actions\News\ImportScrapedArticles` | Upsert a batch of DTOs into `news_items`. **No HTTP.** | `NewsItem` |
| `App\Console\Commands\ImportTekNews` | Loop channels, wire fetch to import, report, set exit code | both actions |

The fetch/import split is load-bearing: the parsers are tested against saved
fixtures with no network and no database; the importer is tested with hand-built
DTOs with no network. Neither test can be flaky.

`FetchArticleImage` reads `og:image` out of the response head with a targeted
regex rather than a DOM parse. The page is invalid HTML, a full parse would be
wasted work, and the meta tag is unambiguous — this is the one place where a
regex over markup is the right call rather than a shortcut.

### Namespace constraints

`App\Support` and `App\Data` are forbidden — `tests/Feature/StaticLayerRetiredTest.php`
asserts those directories stay deleted and that no source file references those
namespaces. The DTO and enum go in `App\News\`, following the existing
`App\Duel\` and `App\Money\` precedent for small domain namespaces.

No new base directories are created: `app/News/` follows that precedent, and
`app/Actions/`, `app/Console/Commands/`, `app/Models/` already exist or are
framework-standard.

### HTTP client

Requests go through Laravel's `Http` facade, which is Guzzle underneath
(guzzlehttp/guzzle 7.15.3 is already installed). This is the idiomatic Laravel 13
approach and gives `Http::fake()`, retries and timeouts for free. A raw Guzzle
client is not injected — it would lose all of that for no gain.

**No new Composer dependencies.** XML parsing uses PHP's built-in `SimpleXML`.
`symfony/dom-crawler` is not needed because no HTML is parsed.

## Data model

New table `news_items`, new model `App\Models\NewsItem`.

```
id
external_id      string, unique      the "b2qbb9ugd" token from the URL slug
source           string, default 'tek.id'
channel          string              Gadget | Review | Tek | Culture | Future | Insight | Tektalk
title            string
excerpt          text
url              string, unique      canonical link back to tek.id
image_url        string, nullable
published_at     timestamp
timestamps

unique (external_id)
unique (url)
index  (channel, published_at)
index  (published_at)
```

Deliberate choices:

- **`external_id` is the upsert key, not `url`.** tek.id URLs embed the title
  slug, so an editor fixing a headline typo changes the URL and would create a
  duplicate. The trailing token is stable. Trade-off accepted: if tek.id ever
  changes its ID scheme we get duplicates rather than silent misses — the louder
  and more recoverable of the two failures.
- **No `slug`, no `body`, no `read_minutes`.** These items never get a
  bandingin.id page. Storing a slug would invite one.
- **`channel` is a plain string backed by `TekChannel`, not `ArticleCategory`.**
  tek.id's channels do not map onto Rilis/Harga/Panduan/Ulasan. Forcing them
  would either lose information or corrupt the existing enum.
- **`image_url` comes from the article page's `og:image`, not the feed**, and is
  hotlinked from `static.tek.id` rather than re-hosted. Re-hosting is a heavier
  copyright question than linking and adds storage plus a failure mode for no
  benefit in link-out aggregation. The column stays nullable: a card whose image
  fetch failed still renders, without one.
- **An existing row's non-null `image_url` is never overwritten with null.** The
  importer refreshes title and excerpt on every run but only writes `image_url`
  when the incoming DTO actually carries one, so a later transient fetch failure
  cannot blank an image we already have.

`NewsItem` carries a `published` scope and a `dateShort()` presenter matching the
Indonesian date formatting already on `App\Models\Article`.

## Data flow

```
schedule (hourly, withoutOverlapping)
  └─ ImportTekNews
       └─ for each TekChannel:
            ├─ FetchTekChannel ──HTTP──> /{channel}/rss
            │     └─ SimpleXML → list<ScrapedArticle>   (skips malformed items)
            ├─ partition: which external_ids are already in news_items?
            ├─ for each NEW item only:
            │     └─ FetchArticleImage ──HTTP──> article URL
            │           └─ og:image → ScrapedArticle::withImage()
            └─ ImportScrapedArticles → upsert by external_id
       └─ report counts; exit non-zero if any channel failed
```

The "new items only" partition is what keeps the image fetch cheap. A steady
state run finds one or two new articles per channel and makes one or two extra
requests; only a cold start fetches all ten. `ScrapedArticle` is readonly, so
`withImage()` returns a new instance rather than mutating.

## Public surfacing

Ingested items appear immediately — no approval step.

**They must not enter the `/berita` article grid.** That grid renders
`x-article-card`, which links to `route('berita.show', $article->slug)`, a
bandingin.id page. Mixing external items in would 404 or imply bandingin hosts
tek.id's articles — the outcome this whole design exists to avoid.

Instead: a distinct **"Kabar Teknologi"** section on `/berita`, below the
existing grid.

- Rendered by a new `App\Livewire\NewsFeed` component.
- Latest 8 items, `published_at` descending, no channel filter in v1.
- Each card shows the source label "tek.id" and an external-link affordance.
- Links go straight to `$item->url` with `target="_blank" rel="noopener nofollow"`.
  `nofollow` is required — without it every card passes SEO equity to a competitor.
- No intermediate bandingin page and no redirect wrapper.

## Admin

`NewsItemResource` in Filament: table view with channel and date filters, plus
delete. **No create or edit form** — these rows are owned by the importer, and a
hand-edited row is silently overwritten on the next run. Making the resource
read-only-plus-delete expresses that in the UI rather than in a comment.

## Failure handling

The command runs unattended, so every failure mode has a defined behaviour.

- **Per-channel isolation.** One channel failing must not abort the other six.
  Each channel is caught, logged, and counted; the command continues and exits
  non-zero at the end if any channel failed, so the scheduler surfaces it.
- **Timeouts and retries:** `->timeout(15)->retry(2, 500)`. A hung request must
  not hold the schedule.
- **Malformed XML** (`simplexml_load_string()` returns `false`): treated as a
  channel failure, logged with the response body length. Never fatal.
- **Malformed item:** an item missing a title, link, or parseable `pubDate` is
  skipped individually and counted. One bad entry does not lose the other nine.
- **Idempotency.** Two consecutive runs produce no new rows and no duplicates.
  This is the property that makes the schedule safe, and it is pinned by test.
- **Image fetch failure is never fatal.** A timeout, non-200, or missing
  `og:image` yields null and the item imports without an image. One article's
  image must not cost us the article, nor the rest of the channel.
- **Politeness:** an identifying User-Agent; channels and image fetches issued
  sequentially, not concurrently. At most ~77 requests per hour on a cold start
  and a handful in steady state — no case for parallelism.

Schedule registered in `routes/console.php`, hourly, `withoutOverlapping()`.

## Testing

Pest feature tests, factories for data, per the project's test-enforcement rule.

| Test | Proves |
| --- | --- |
| `FetchTekChannelTest` | Saved real RSS fixture → correct DTOs: title, URL, excerpt, image, `external_id` extraction, and Jakarta→UTC conversion. `Http::fake()`, no DB. |
| `FetchTekChannelTest` (degradation) | Non-XML body, empty channel, and item missing a link each degrade as specified rather than throwing. |
| `FetchArticleImageTest` | Saved article-page fixture → the `og:image` URL. A 404, a timeout, and a page with no `og:image` each return null rather than throwing. |
| `ImportScrapedArticlesTest` | Insert creates rows; re-importing identical DTOs creates zero new rows and zero duplicates; a changed title updates in place; **a null incoming image does not blank an existing `image_url`**. |
| `ImportTekNewsCommandTest` (partition) | A second run over the same feed issues **no** article-page requests — proving the image fetch is confined to new items. |
| `ImportTekNewsCommandTest` | End-to-end with faked HTTP: all 7 channels fetched and rows land. One channel returning 500 → the other six still import and the command exits non-zero. |
| `BeritaPageTest` (extended) | External items render in their own section, link off-site with `rel="nofollow"`, and do **not** appear in the `/berita` article grid. Regression guard on the aggregation-not-republication decision. |

The RSS and article-page fixtures are saved from the live responses captured
during reconnaissance, so both parsers are tested against real tek.id markup.

## Out of scope

- Article body parsing, storage, or rendering. The article page is fetched for
  its `og:image` meta tag only.
- Per-article tags via the sitemap's `news:keywords`.
- Archive backfill via `POST /{channel}/more-category`.
- Image re-hosting.
- Channel filter chips on the public feed — revisit once real volume is visible.
- Any AI rewriting pipeline.
