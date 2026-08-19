# Bandingin — Database Schema (Part 1 of 3)

**Date:** 2026-08-19
**Status:** Approved
**Scope:** Migrations, models, factories, seeders, and tests that move the app's data from hardcoded `app/Support/*` catalogs into the database. Part 2 (Filament admin) and Part 3 (wiring the Livewire frontend to Eloquent) are separate specs.

## Context

The frontend is complete but runs on dummy data: `PhoneCatalog` (12 phones), `BrandCatalog` (6 brands), `SellerPricing` (fake per-store price offsets), `ArticleRepository`, `FaqRepository`, and a hardcoded `HomePage::MATCHUPS` constant. Two features are already database-backed: `reports` and `newsletter_subscribers`.

Decisions made during brainstorming:

1. **Prices**: admin-entered, history-ready — real `stores` + `phone_listings` tables plus a `price_snapshots` table so a future price tracker plugs in without a schema change.
2. **Specs**: `spec_definitions` + `phone_specs` (definition/value pairs), not fixed columns or JSON. Adding a spec later is a row, not a migration.
3. **Articles**: single rich-text body (Filament RichEditor in Part 2). The berita-detail Blade view is rebuilt around rendered HTML in Part 3.
4. **Embedded comparison tables in articles**: Filament RichEditor custom block referencing two phone IDs, rendered from live phone data (built in Part 2; the schema only needs the rich body).
5. **Scores**: all five category scores admin-entered as columns. Computed scoring may come later as an Action.
6. **Variants**: real per-phone `phone_variants` table.
7. **Popular matchups**: curated `featured_matchups` table.

## Tables

Integer rupiah everywhere for money (`unsignedInteger` / `unsignedBigInteger`); no decimals. All tables get `timestamps()` unless noted.

### brands

| Column | Type | Notes |
|---|---|---|
| id | id | |
| name | string, unique | "Samsung" |
| slug | string, unique | "samsung" |
| blurb | text | |
| pros | json | list of strings, `array` cast |
| cons | json | list of strings, `array` cast |
| bestselling_range | string | editorial: "Rp4–7 jt" |
| os_update_note | string | editorial: "6–7 tahun" |

"Model aktif" (count) and "Harga terendah" are computed from the phones relation, not stored.

### phones

| Column | Type | Notes |
|---|---|---|
| id | id | |
| brand_id | foreignId → brands | cascade on delete restrict |
| slug | string, unique | keeps today's ids (`a56`, `note14pro`) so `/hp/{phone}` URLs don't break |
| name | string | "Galaxy A56 5G" |
| price | unsignedInteger | official price, rupiah |
| tag | string, nullable | badge: "Terlaris", "Hemat" |
| nfc | boolean | |
| ip68 | boolean | |
| dimensions | string | "162,2 x 77,5 x 7,4 mm" |
| network | string | "5G Dual SIM · Wi-Fi 6 · NFC" |
| released_label | string | display: "Maret 2025" |
| released_at | date | for sorting |
| pros | json | list of strings |
| cons | json | list of strings |
| score_performa | unsignedTinyInteger | 0–100 |
| score_layar | unsignedTinyInteger | |
| score_kamera | unsignedTinyInteger | |
| score_baterai | unsignedTinyInteger | |
| score_nilai | unsignedTinyInteger | "Nilai per Rupiah" |
| published_at | timestamp, nullable | unpublished phones hidden from the site |

### spec_definitions

| Column | Type | Notes |
|---|---|---|
| id | id | |
| key | string, unique | `chipset`, `antutu`, … `berat` (today's 19 keys) |
| label | string | "Chipset", "Berat" |
| group | string, nullable | detail-page grouping (Performa / Layar / Kamera / Baterai / Bodi) |
| sort | unsignedSmallInteger | |

### phone_specs

| Column | Type | Notes |
|---|---|---|
| id | id | |
| phone_id | foreignId → phones | cascade delete |
| spec_definition_id | foreignId → spec_definitions | cascade delete |
| display_value | string | "6,7\" AMOLED · 1.900 nits" |
| comparable_value | bigInteger | keeps the existing convention: higher wins; lower-is-better specs store negated values (berat = -198). `CompareDuel` ports unchanged. |

Unique on (phone_id, spec_definition_id). No timestamps needed but keep them for auditability.

### brand_highlights

| Column | Type | Notes |
|---|---|---|
| id | id | |
| brand_id | foreignId → brands | cascade delete |
| phone_id | foreignId → phones | cascade delete |
| sort | unsignedSmallInteger | |

Unique on (brand_id, phone_id). The four showcase models per brand on the Merek page. Today's `BrandCatalog` lists highlight models that have no entry in `PhoneCatalog` (e.g. Redmi 14C, iPhone 16 Pro) as name/price/spec strings only. Seeding creates a published `phones` row for each of those from the string data available, with an empty specs set — enough for the Merek page's card, and a Filament editor can fill in the rest later.

### stores

| Column | Type | Notes |
|---|---|---|
| id | id | |
| name | string, unique | "Shopee Mall" |
| slug | string, unique | |
| is_official | boolean | distributor rows are false |

### phone_listings

| Column | Type | Notes |
|---|---|---|
| id | id | |
| phone_id | foreignId → phones | cascade delete |
| store_id | foreignId → stores | cascade delete |
| price | unsignedInteger | |
| product_url | string, nullable | |
| note | string, nullable | "Cicilan 0% 12 bulan" |
| warranty_tag | string, nullable | "Non-resmi" |

Unique on (phone_id, store_id). "Termurah" is computed (lowest price among a phone's listings), never stored.

### price_snapshots

| Column | Type | Notes |
|---|---|---|
| id | id | |
| phone_listing_id | foreignId → phone_listings | cascade delete |
| price | unsignedInteger | |
| recorded_at | timestamp | |

No `timestamps()`. Written by a model observer on `PhoneListing` whenever `price` changes (including creation), so history accrues from day one and a future tracker needs no schema change.

### phone_variants

| Column | Type | Notes |
|---|---|---|
| id | id | |
| phone_id | foreignId → phones | cascade delete |
| label | string | "12/256 GB" |
| price | unsignedInteger | |
| is_default | boolean | the variant `phones.price` refers to |
| sort | unsignedSmallInteger | |

### articles

| Column | Type | Notes |
|---|---|---|
| id | id | |
| slug | string, unique | |
| title | string | |
| category | string | backed by `ArticleCategory` enum: Rilis, Harga, Panduan, Ulasan |
| excerpt | text | |
| body | longText | rich-text HTML (RichEditor output in Part 2); seeded articles get their body/quote/bodyRest concatenated into HTML |
| tags | json | list of strings |
| read_minutes | unsignedTinyInteger | |
| published_at | timestamp, nullable | featured article = newest published |

The old `ArticleSpecTable` structure is not migrated as a table — embedded comparisons become a RichEditor custom block in Part 2. Seeded articles that had one get a plain HTML table in the body as a stopgap.

### faqs

| Column | Type | Notes |
|---|---|---|
| id | id | |
| topic | string | backed by `FaqTopic` enum: Kartu Duel, Harga, Skor, Data |
| question | string | |
| answer | text | |
| sort | unsignedSmallInteger | |

The three "steps" on the FAQ page stay hardcoded (prose, not data).

### featured_matchups

| Column | Type | Notes |
|---|---|---|
| id | id | |
| left_phone_id | foreignId → phones | cascade delete |
| right_phone_id | foreignId → phones | cascade delete |
| sort | unsignedSmallInteger | |

Label ("Galaxy A56 vs Redmi Note 14 Pro") is derived from phone names.

### Untouched

`users` (becomes Filament auth in Part 2), `reports`, `newsletter_subscribers`, cache/jobs tables.

## Models & code

- One Eloquent model per table (except `brand_highlights`, which is a pivot on `Brand::highlightedPhones()` belongsToMany with `sort`): `Brand`, `Phone`, `SpecDefinition`, `PhoneSpec`, `Store`, `PhoneListing`, `PriceSnapshot`, `PhoneVariant`, `Article`, `Faq`, `FeaturedMatchup`.
- `declare(strict_types=1);`, deliberate `$fillable`, casts for json/enum/date/boolean columns.
- Enums: `App\Enums\ArticleCategory`, `App\Enums\FaqTopic` (string-backed, TitleCase cases).
- Relationships: `Brand hasMany Phone`; `Phone belongsTo Brand, hasMany PhoneSpec/PhoneListing/PhoneVariant`; `PhoneListing belongsTo Store, hasMany PriceSnapshot`; `FeaturedMatchup belongsTo left/right Phone`.
- `Phone` gets `getRouteKeyName(): 'slug'`.
- Scopes: `Phone::published()`, `Article::published()`.
- `PhoneListingObserver` (or model `saved` hook) writes a `price_snapshots` row when price changes or on create.
- **No Livewire page, Support class, or Blade view is modified in Part 1.** The Support catalogs keep serving the frontend until Part 3.

## Seeding

`DatabaseSeeder` calls, in order: `SpecDefinitionSeeder`, `StoreSeeder`, `BrandSeeder`, `PhoneSeeder` (12 phones + specs + variants + listings materialized from today's `SellerPricing` offsets), `BrandHighlightSeeder`, `ArticleSeeder`, `FaqSeeder`, `FeaturedMatchupSeeder`. Seeders read the existing Support classes' data (copied in, or referenced directly) so the seeded site is pixel-identical to today's dummy site. Seeders are idempotent (`updateOrCreate` keyed on slugs/keys).

## Testing

- Uncomment `RefreshDatabase` in `tests/Pest.php` (per CLAUDE.md, the moment tests touch the DB).
- Factories for every model, with realistic states (`Phone` factory has `unpublished()`; `Article` factory has `draft()`).
- Pest feature tests: relationships and casts round-trip; unique constraints hold; `PhoneListing` price change writes a snapshot (and creation writes the first one); `Phone` route-binds by slug; seeders run green and produce the expected counts (6 brands; 12 fully-specced phones plus the highlight-only phones, each of the 12 carrying 19 specs and 4 listings; every brand having 4 highlights).

## Out of scope for Part 1

Filament panel and resources (Part 2); any change to Livewire pages, Support classes, or Blade views (Part 3); price scraper/tracker jobs; visitor-facing auth; computed scoring.
