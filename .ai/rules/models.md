---
paths:
  - 'app/Models/**'
---

# Models

## Catalogue data lives in Eloquent models
Phones, brands, specs, prices, articles, FAQs and featured matchups are database-backed (App\Models\*), seeded from the legacy App\Support\* classes by database/seeders/*.

Spec values live in phone_specs as display_value + comparable_value pairs against spec_definitions rows. comparable_value is always "higher is better" — inverted metrics are stored negated (berat 198 g is -198) and price is negated inside CompareDuel. Adding a spec means adding a spec_definitions row, not a migration.

Money is integer rupiah everywhere. The cheapest listing ("Termurah") is computed from phone_listings, never stored. PhoneListingObserver appends a price_snapshots row on create and on every price change, so seeding must not run WithoutModelEvents.

## Catalogue querying lives in Phone query scopes
The public site reads the database directly — App\Support and App\Data no longer exist, and tests/Feature/StaticLayerRetiredTest.php asserts they stay gone.

Catalogue filtering belongs on App\Models\Phone as scopes: published, search, inPriceBands, ofBrands, matchingSpecFilters, sortedBy. PRICE_BANDS, SPEC_FILTERS and FLAG_FILTERS are constants on that model so the scopes and the sidebar facet counts share one definition — never duplicate them in a Livewire component. Price band upper bounds are exclusive so adjacent bands cannot both claim a price.

Phone::display($key) and comparable($key) read the LOADED specs relation and never query. Any page listing phones must eager-load specs.definition and brand, or a card grid fires hundreds of queries. There is a query-log test for this in PhoneAccessorsTest.

Money is integer rupiah, formatted only at the edge via App\Money\Rupiah::format() or the formattedPrice() accessors on Phone, PhoneVariant and PhoneListing.

Article bodies are rich HTML rendered through App\Actions\RenderArticleBody, the only class on the public side allowed to touch App\Filament.

Highlight-only phones (BrandSeeder::BRANDS models with no catalogue entry) are seeded as DRAFTS: they have no specs or scores, so publishing them would put half-empty cards in the catalogue. The Merek page reads the highlights relation, which does not filter on published.
