---
paths:
  - 'resources/views/**'
---

# Views

## Build pages from the shared Blade components and theme tokens
The Bandingin design system lives in resources/css/app.css as Tailwind v4 @theme tokens (ink, muted, faint, line, line-soft, line-strong, surface, brand, brand-dark, win, win-ink, lose; font-display / font-sans / font-mono; the .page-wash, .hero-wash, .hero-grid, .photo-slot, .device-shell utilities; and the nav: breakpoint at 820px). Use those tokens — do not hardcode hex values or reintroduce the design's inline style="" attributes, except where a computed percentage width genuinely needs one.

Compose pages from resources/views/components/* (site-nav, site-footer, page-hero, section-heading, phone-card, article-card, chip, toggle-switch, win-badge, score-bar, stat-card, pros-cons, seller-row, affiliate-link, affiliate-note, marketplace-tags, device-mock, breadcrumb, eyebrow) rather than duplicating markup. Each Livewire view needs exactly one root element and ends with <x-site-footer />.
