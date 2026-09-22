---
paths:
  - 'app/News/**'
  - 'app/Actions/News/**'
  - 'app/Console/Commands/ImportNews.php'
---

# News

## Scraped articles
Articles with a non-null `source` were scraped from an external site; null source means a human wrote it. Every importer query is scoped Article::scraped() / whereNotNull('source'). The importer must never create, update or delete a row a human wrote — on a slug collision the scraped article takes a numeric suffix instead. ImportScrapedArticlesTest pins this.

Publishing tek.id's text verbatim was the owner's explicit decision, made after being told it is copyright infringement and carries a duplicate-content ranking risk. Do not silently change it, and keep the two mitigations: the visible "Sumber" credit and the rel=canonical pointing at source_url. The detail page must never show our byline or "Foto: dokumentasi Bandingin.id" on a scraped article — both are false attribution. ScrapedArticlePresentationTest pins this.

Sources are rows in scrape_sources, not code: feed URL plus the ArticleCategory its articles become. Adding a site is a row. TekChannel was deleted.

RSS pubDate is "Y-m-d H:i:s" in Asia/Jakarta with NO timezone and the app runs in UTC — always CarbonImmutable::createFromFormat(..., 'Asia/Jakarta')->utc(). Carbon::parse() shifts every article by 7 hours.

Body extraction uses DOMDocument, which also repairs tek.id's never-closed <p> tags. The junk XPath in FetchArticleContent is load-bearing: RichContentRenderer UNWRAPS unknown elements rather than dropping them, so a surviving div.bacajuga renders as the stray text "Baca Juga" inside the article. RichContentRenderer does handle safety — it strips script, onclick and javascript: hrefs — so no separate sanitiser is needed.

A body-less article is never stored, so the next run retries it. Selectors are tek.id-specific; move them onto the source row when a second site is added.

Adding or removing a Filament resource requires editing creatableResources() / listableResources() in tests/Feature/Filament/ResourcePagesTest.php, which asserts the registered list exhaustively, and giving it a navigation icon no other resource uses.
