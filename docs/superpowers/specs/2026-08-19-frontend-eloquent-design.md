# Bandingin — Frontend on Eloquent (Part 3 of 3)

**Date:** 2026-08-19
**Status:** Approved
**Scope:** Point every Livewire page at the database, delete the `App\Support` catalogues and `App\Data` DTOs, and rebuild the article detail view around rich-text bodies. Parts 1 (schema) and 2 (Filament admin) are merged.

## Context

Parts 1 and 2 built the database and the admin panel while deliberately leaving the public site untouched. The site still renders from hardcoded static classes, so nothing an editor changes in Filament reaches a visitor. This part closes that gap and retires the static layer.

The frontend's current contract with the static layer, measured across `app/Livewire` and `resources/views`:

- `App\Data\Phone` — `display()` (11 uses), `formattedPrice()` (8), `comparable()` (6), `averageScore()` (3), `spec()` (2), `matches()` (2), plus `id`, `name`, `brand`, `tag`, `scores`, `pros`, `cons`.
- `App\Data\Article` — `specs`, `title`, `category`, `slug`, `readMinutes`, `id`, `excerpt`, `tags`, `quote`, `meta()`, `dateLong`, `date`, `bodyRest`, `body`.
- `App\Actions\CompareDuel` takes two `App\Data\Phone` objects.

Three of those collide with the Eloquent models and force view edits:

| DTO | Meaning | Eloquent |
|---|---|---|
| `$phone->id` | slug string (`a56`) | `$phone->slug`; `id` is now an int PK |
| `$phone->brand` | brand name string | `$phone->brand->name` (a `Brand` model) |
| `$phone->scores` | `['Performa' => 72, …]` | five `score_*` columns |

`Article::$body`, `$quote`, `$bodyRest` and `$specs` have no Eloquent equivalent at all — Part 1 folded them into one rich-text `body`.

### Decisions made during brainstorming

1. **Full Eloquent.** Pages query models directly, DTOs are deleted, views move to Eloquent idioms, `CompareDuel` is retyped. No mapping layer and no DTO-mimicking accessors.
2. **Article bodies render through Filament's `RichContentRenderer`** so Part 2's comparison block expands from live phone data.
3. **`App\Support\*` data is inlined into the seeders**, then the namespace is deleted.
4. **Variant selection changes the official price only.** Seller listings show their real stored prices.
5. **Catalog filtering and sorting run in SQL** via query scopes.
6. **"Populer" sorts by `released_at` descending.**

## Query scopes

All catalog querying lives on `App\Models\Phone` as scopes, so the Livewire component stays a coordinator rather than a query builder.

| Scope | Behaviour |
|---|---|
| `published()` | exists already — `whereNotNull('published_at')` |
| `search(?string $term)` | no-op when blank; otherwise `where(name like %term%)` or brand name like |
| `inPriceBands(array $bandIds)` | no-op when empty; `where` grouped `orWhereBetween('price', [min, max])` per band, using the band table below |
| `ofBrands(array $brandNames)` | no-op when empty; `whereRelation('brand', 'name', 'in', …)` |
| `matchingSpecFilters(array $filterIds)` | one `whereHas('specs', …)` per filter, each matching `spec_definitions.key` and `comparable_value >= threshold` |
| `sortedBy(string $sort)` | `murah` → `price` asc, `mahal` → `price` desc, `populer` → `released_at` desc |

The price bands and spec thresholds move out of `KatalogPage` into the model layer so scopes and facet counts share one definition:

```
PRICE_BANDS  p1 1_000_000–2_000_000 · p2 2_000_000–4_000_000
             p3 4_000_000–7_000_000 · p4 7_000_000–PHP_INT_MAX
SPEC_FILTERS ram12 ram>=12 · hz120 refresh>=120 · bat5k baterai>=5000
             fast charge>=45 · nfc (boolean column) · ip68 (boolean column)
```

`nfc` and `ip68` are columns on `phones`, not spec rows, so `matchingSpecFilters()` routes those two to a plain `where` and the other four to `whereHas`.

Facet counts on the catalog sidebar become aggregate queries over the same scopes rather than counting a loaded collection.

## Page-by-page changes

**HomePage** — `PhoneCatalog::findOrFirst()` becomes `Phone::published()->firstWhere('slug', …)` with `specs.definition` eager-loaded; `MATCHUPS` reads `FeaturedMatchup::with('leftPhone', 'rightPhone')->orderBy('sort')`; featured and latest articles come from `Article::published()->latest('published_at')`.

**KatalogPage** — filtering and sorting move to the scopes above. The sticky duel bar keeps working on slugs.

**PerbandinganPage** — resolves both sides by slug, eager-loads specs, and renders seller rows from real `phone_listings` instead of `SellerPricing::compact()`.

**PhoneDetailPage** — `mount()` resolves by slug and 404s on miss (unchanged behaviour); specs group by `spec_definitions.group`; `variants` come from `phone_variants`; seller rows from `phone_listings` ordered by price with the cheapest flagged "Termurah"; rivals are other published phones from the same brand, falling back to any published phone so the rail is never empty.

**MerekPage** — brands from `Brand::withCount('phones')` with `highlightedPhones` eager-loaded. The two computed stats the Part 1 spec deferred land here: "Model aktif" is `phones_count`, "Harga terendah" is `phones()->min('price')` formatted. "Rentang terlaris" and "Update OS" read their stored columns.

**BeritaPage / BeritaDetailPage** — categories from `ArticleCategory::values()`; listing filters in SQL; the featured article is the newest published one and is excluded from the grid when no category filter is active, matching today's rule. Detail renders one HTML body; related articles prefer the same category.

**FaqPage** — `Faq::orderBy('sort')`, filtered by topic; the three "steps" stay hardcoded prose.

## Article rendering

`App\Actions\RenderArticleBody` is the single sanctioned bridge between the public site and Filament:

```php
final class RenderArticleBody
{
    public function handle(Article $article): HtmlString
    // RichContentRenderer::make($article->body)
    //     ->customBlocks([PhoneComparisonBlock::class])
    //     ->toHtml()
}
```

`BeritaDetailPage` depends on this Action, never on Filament. The Part 2 arch test therefore still holds unchanged in both directions: `App\Livewire` does not use `App\Filament`, and `App\Filament` does not use `App\Livewire`. A new arch assertion pins the intent — only `App\Actions\RenderArticleBody` may reference the block class.

The rendered body is echoed unescaped, which is safe because the content is authored by authenticated admins through the RichEditor, and is the same trust model Filament itself applies. Seeded plain-HTML bodies pass through unchanged.

## Money formatting

`App\Data\Phone::formatRupiah()` disappears with the DTOs, but eight call sites still need rupiah formatting. It moves to `App\Money\Rupiah` — a small final class with one static method:

```php
Rupiah::format(5_499_000) // "Rp5.499.000"
```

`Phone`, `PhoneVariant` and `PhoneListing` each gain a `formattedPrice()` accessor delegating to it, which covers every view call site. The class lives in its own namespace rather than `App\Support`, which this part deletes.

## Testing

The 34 existing frontend tests are the regression net: rendered output must not change except on the article detail page and the variant-price correction.

Those tests currently pass against static data. With pages on Eloquent and `RefreshDatabase` active, each starts with an empty database, so the frontend suite needs seeded data. A Pest helper `seedCatalog()` runs the catalog and content seeders, invoked from a `beforeEach` in the frontend feature tests. This keeps assertions that name real records (`a56`, "lists the whole catalogue") meaningful rather than rewriting them around factories.

**Consequence worth stating plainly:** the seeders become load-bearing for the frontend suite. A broken seeder now fails page tests, not just seeder tests.

New tests to add:

- Each scope in isolation: price bands, brand filter, each spec threshold, all three sorts.
- The catalog page returns identical results to today's fixtures for a representative filter combination.
- An unpublished phone is absent from catalog, home picker and detail (404).
- `RenderArticleBody` expands a comparison block and passes plain HTML through untouched.
- The phone detail page shows real listing prices and does not shift them when a variant is selected.
- Arch: nothing references `App\Support` or `App\Data` after deletion.

## Retirement

Seeders currently read `App\Support\*` at runtime. Before deletion, each seeder inlines the data it needs as a private constant, verified by the existing seeder tests, which assert exact counts and values. Then `app/Support/`, `app/Data/`, and their tests (`tests/Unit/PhoneCatalogTest.php`) are deleted, and `.ai/rules/support.md` is replaced by a rule describing the query-scope layer.

`tests/Unit/CompareDuelTest.php` is retyped to build `Phone` models rather than DTOs; its assertions about winners and ties stay identical.

## Out of scope

- Any visual redesign. Rendered markup stays as-is apart from the article body and the variant-price fix.
- Caching, pagination, and full-text search.
- Moving listings onto variants — noted as a possible Part 4.
- Any change to the Filament panel beyond what `RenderArticleBody` consumes.
