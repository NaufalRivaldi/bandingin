---
paths:
  - 'app/Ads/**'
  - 'app/Settings/**'
  - 'app/Filament/Pages/**'
  - 'resources/js/ads.js'
---

# Ads

## Ads: settings page is hand-rolled, AdSlot caches primitives only
The ad knobs (enabled, frequency, cooldown_minutes) are spatie/laravel-settings, but filament/spatie-laravel-settings-plugin is deliberately NOT installed: it pins filament/filament to an exact patch release and installing it would force the whole panel from v5.7.6 to v5.8.4. App\Filament\Pages\AdSettingsPage re-implements the plugin's fill/save contract on a plain Filament\Pages\Page instead. Do not "simplify" it by adding the plugin without redoing that upgrade.

App\Ads\AdSlot caches plain arrays, never Eloquent models. CACHE_STORE is database, which serializes — cached models come back as __PHP_Incomplete_Class and silently kill the slot. The `enabled` toggle is read uncached so switching ads off is immediate; ad edits take up to 5 minutes.

The slot lives in App\Ads, not App\Support — that namespace is deleted and StaticLayerRetiredTest fails if it reappears.

resources/views/components/ad-rotator.blade.php must stay type="application/json" (id bdg-ad-config). The SEO suite's jsonLdBlocks() helper parses every application/ld+json block and would choke on it.

Page views are counted per document load in resources/js/ads.js. There is no wire:navigate here, so nothing listens for livewire:navigated and Livewire component updates do not count as views.
