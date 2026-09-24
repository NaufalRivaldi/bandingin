---
paths:
  - config/seo.php
---

# Config

## robots.txt and the sitemap are routes, not files in public/
public/robots.txt was deleted on purpose — the web server would serve it before Laravel and shadow the route. Bot allow/deny lists live in config/seo.php and render through resources/views/seo/robots.blade.php.

The robots directive is resolved once per request by SEOMeta and mirrored onto an X-Robots-Tag header by AddRobotsHeader middleware, so the tag and the header cannot disagree. Pages noindex themselves via SetsPageSeo::noindexWhenFiltered(), which only reacts to that page's own filter parameters — a utm_* tag must never deindex a clean URL.

Sitemaps are cached controller responses (/sitemap.xml index plus pages/phones/articles children), TTL from seo.sitemap.cache_ttl. sitemap-articles.xml lists every Article::published(), scraped ones included — the owner asked for that explicitly, after being told those URLs keep their rel=canonical to the publisher and so will not index while it stands. Do not quietly narrow it back to own().
