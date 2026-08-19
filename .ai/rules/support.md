---
paths:
  - 'app/Support/**'
---

# Support

## Catalogue content is static PHP, not database rows
Phones, brands, articles and FAQs are demo content that ships with the design, so they live in static provider classes (App\Support\PhoneCatalog, BrandCatalog, ArticleRepository, FaqRepository) returning readonly DTOs from App\Data. There are no migrations for them and no Eloquent models — do not add any without agreeing to swap the layer over.

Only user-submitted data is persisted: newsletter_subscribers and reports.

Phone specs are an array<string, SpecValue> where SpecValue::$comparable is always "higher is better", so inverted metrics are stored negated (weight is -198 for 198 g) and price is negated inside CompareDuel. Add new comparable specs the same way or the duel will pick the wrong winner.
