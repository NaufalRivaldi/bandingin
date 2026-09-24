---
paths:
  - 'resources/views/**'
---

# Views

## Build pages from the shared Blade components and theme tokens
The Bandingin design system lives in resources/css/app.css as Tailwind v4 @theme tokens (ink, muted, faint, line, line-soft, line-strong, surface, brand, brand-dark, win, win-ink, lose; font-display / font-sans / font-mono; the .page-wash, .hero-wash, .hero-grid, .photo-slot, .device-shell utilities; and the nav: breakpoint at 820px). Use those tokens — do not hardcode hex values or reintroduce the design's inline style="" attributes, except where a computed percentage width genuinely needs one.

Compose pages from resources/views/components/* (site-nav, site-footer, page-hero, section-heading, phone-card, article-card, chip, toggle-switch, win-badge, score-bar, stat-card, pros-cons, seller-row, affiliate-link, affiliate-note, marketplace-tags, device-mock, breadcrumb, eyebrow) rather than duplicating markup. Each Livewire view needs exactly one root element and ends with <x-site-footer />.

## Shared components rendered twice per page must not emit fixed DOM ids
NewsletterSubscribe renders twice on /berita (section + site-footer), so its input id is derived from $this->getId(). Any Livewire component or Blade component reused within one page must build ids the same way — a hardcoded id makes every <label for> resolve to the first instance and silently breaks click-to-focus.

The brand mark lives in components/site-logo.blade.php (inline SVG, fill-brand token) and is used by site-nav and site-footer. The matching favicons in public/ (favicon.svg, favicon.ico, apple-touch-icon.png) are generated from the same geometry — regenerate all of them together if the mark changes. The bare "VS" spans in home-page and perbandingan-page are duel separators, not the logo.

Disqus is config-driven: config('services.disqus.shortname') via DISQUS_SHORTNAME. components/disqus-comments.blade.php renders nothing until it is set, and pushes its loader onto the 'scripts' stack that layouts/app.blade.php outputs after @livewireScripts.

## Guard optional columns with filled(), never !== ''
Most optional string columns on the models here are nullable, so `@if ($model->column !== '')` is true for NULL and renders an empty element. This shipped an empty black tag pill on every untagged phone card in the catalogue.

Use `@if (filled($model->column))` for any nullable string you are deciding whether to render. `filled()` rejects null, '' and whitespace in one call.

When a badge or pill has no text of its own to assert on, test it at the component level with `Blade::render('<x-phone-card :phone="$phone" />', [...])` and assert on the marker class (`bg-ink` for the tag badge) — that class is ambiguous on a full page, where the nav hamburger bars also use it.
