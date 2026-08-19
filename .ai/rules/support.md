---
paths:
  - 'app/Support/**'
---

# Support

## Catalogue content is served from static PHP, and mirrored in the database
Phones, brands, articles and FAQs originally shipped with the design as static provider classes (App\Support\PhoneCatalog, BrandCatalog, ArticleRepository, FaqRepository) returning readonly DTOs from App\Data. Those classes still render every Livewire page, but as of the Part 1 schema work the same data also lives in Eloquent models — see .ai/rules/models.md.

Phone specs here are an array<string, SpecValue> where SpecValue::$comparable is always "higher is better", so inverted metrics are stored negated (weight is -198 for 198 g) and price is negated inside CompareDuel. The phone_specs table follows the identical convention. Add new comparable specs the same way in both places or the duel will pick the wrong winner.

## Support catalogues are legacy read models pending removal
App\Support\{PhoneCatalog, BrandCatalog, ArticleRepository, FaqRepository, SellerPricing} are the original hardcoded demo data. They still serve every Livewire page, but the same data now also lives in the database (see app/Models/**), and the seeders read these classes to populate it.

Do not add new content here — add it through the database. These classes get retired in Part 3 when the Livewire pages switch to Eloquent; before deleting them, inline their data into database/seeders/* so seeding keeps working.
