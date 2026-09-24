---
paths:
  - 'app/Seo/**'
---

# Seo

## Call $this->seo() before any structured-data Action
Head tags come from artesaos/seotools, generated once in layouts/app.blade.php. Livewire renders the component before the layout, so a page sets its tags inside render() via the SetsPageSeo trait — #[Title] is no longer used anywhere.

Ordering trap: SEOTools::setTitle()/setDescription() write into the *current* JSON-LD block, not block 0. So $this->seo() must run BEFORE any App\Seo\*StructuredData call. Calling it after sends the page name and description into the last opened block (Organization, BreadcrumbList), leaving the main entity unnamed.

A second JSON-LD block inherits the config defaults, so always open one through WritesJsonLd::startBlock() — it clears the inherited name/description/url before the block describes its own thing.

Canonical is config-driven: seotools.meta.defaults.canonical is 'current', which drops the query string, so a filtered /katalog URL canonicalises to the clean path with no per-page code. Only ArticleStructuredData overrides it, pointing a scraped article at its publisher. Do not add <link rel="canonical"> in Blade — that yields two canonicals.
