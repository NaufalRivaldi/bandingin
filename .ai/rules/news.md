---
paths:
  - 'app/News/**'
  - 'app/Actions/News/**'
  - 'app/Console/Commands/ImportTekNews.php'
---

# News

## External news aggregation
news_items rows are aggregated from tek.id and are LINK-OUT ONLY: no body is ever fetched, parsed, stored or rendered, and the rows deliberately have no slug so they can never resolve to a page on this site. Public cards must keep target="_blank" rel="noopener nofollow" — without nofollow every card passes SEO equity to a competitor. Keep them out of the /berita article grid, which renders x-article-card and links to route('berita.show').

Identity is external_id, the final hyphen-separated token of the tek.id URL ("...-b2qbp9uhh"), never the URL itself: tek.id URLs embed the title slug, so an edited headline would create a duplicate row.

The RSS pubDate is "Y-m-d H:i:s" in Asia/Jakarta with NO timezone, and the app runs in UTC. Always parse with CarbonImmutable::createFromFormat(..., 'Asia/Jakarta')->utc(). Carbon::parse() silently shifts every article by 7 hours.

The feed carries no images: its media:content url points at the article, not an image. Images come from the article page's og:image, fetched for NEW items only (ImportScrapedArticles::existingIds partitions them). A failed image fetch must never blank an image already stored.

Feeds return 10 items each; the per-channel sitemap has 50 and also carries news:keywords (tags), and POST /{channel}/more-category walks the deeper archive with a CSRF token scraped from the page. Both are unused, and recorded as the known options if backfill is ever wanted.

A cold-start import is ~77 sequential requests and takes ~4 minutes; steady state is 7. Adding a new Filament resource also requires adding it to listableResources() in tests/Feature/Filament/ResourcePagesTest.php, which asserts the registered list exhaustively.
