---
paths:
  - 'app/Models/**'
---

# Models

## Catalogue data lives in Eloquent models
Phones, brands, specs, prices, articles, FAQs and featured matchups are database-backed (App\Models\*), seeded from the legacy App\Support\* classes by database/seeders/*.

Spec values live in phone_specs as display_value + comparable_value pairs against spec_definitions rows. comparable_value is always "higher is better" — inverted metrics are stored negated (berat 198 g is -198) and price is negated inside CompareDuel. Adding a spec means adding a spec_definitions row, not a migration.

Money is integer rupiah everywhere. The cheapest listing ("Termurah") is computed from phone_listings, never stored. PhoneListingObserver appends a price_snapshots row on create and on every price change, so seeding must not run WithoutModelEvents.
