---
paths:
  - 'app/Livewire/**'
---

# Livewire

## Pages are class-based Livewire components routed with Route::livewire()
Every page is a class-based Livewire component (app/Livewire/*.php + resources/views/livewire/*.blade.php), not a controller and not a v4 single-file component. Register them with Route::livewire() in routes/web.php — plain Route::get() is not used here.

Derived data goes in #[Computed] methods (read in Blade as $this->name). Filter/tab state that should be shareable goes in #[Url] with a short Indonesian alias (q, merek, urut, jenis, dok, topik, kategori).

Trap: `self::CONST` does NOT resolve to the component inside a Blade view. Pass constants through the render() data array instead.

Trap: #[Title] is compile-time only. For a title that depends on loaded data (phone name, article title), return view(...)->title(...) from render() instead.
