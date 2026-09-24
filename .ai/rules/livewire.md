---
paths:
  - 'app/Livewire/**'
---

# Livewire

## Pages are class-based Livewire components routed with Route::livewire()
Every page is a class-based Livewire component (app/Livewire/*.php + resources/views/livewire/*.blade.php), not a controller and not a v4 single-file component. Register them with Route::livewire() in routes/web.php — plain Route::get() is not used here.

Derived data goes in #[Computed] methods (read in Blade as $this->name). Filter/tab state that should be shareable goes in #[Url] with a short Indonesian alias (q, merek, urut, jenis, dok, topik, kategori).

Trap: `self::CONST` does NOT resolve to the component inside a Blade view. Pass constants through the render() data array instead.

Head tags do not use #[Title] or view(...)->title(). Every page uses the SetsPageSeo trait and calls $this->seo($title, $description) as the first statement in render(); artesaos/seotools renders the result in layouts/app.blade.php. Pass the title without the site name — seotools appends it. See .ai/rules/seo.md for the ordering trap around structured data.
