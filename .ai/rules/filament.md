---
paths:
  - 'app/Filament/**'
---

# Filament

## Filament v5 panel conventions
The admin panel is Filament v5, NOT v4 — v4 targets Livewire 3 and this app runs Livewire 4. Keep the constraint at ^5.0.

Resources live in app/Filament/Resources/<Plural>/ with Pages/, Schemas/ and Tables/ subdirectories. Edit the Schemas/*Form.php and Tables/*Table.php classes, not the resource shell. Generate new ones with `make:filament-resource <Model> --generate`, then adjust: the generator renders json columns as textareas (swap for Repeater::make()->simple()), guesses ->prefix('$') and ->money() without a currency (use 'Rp' and ->money('IDR') — prices are whole rupiah, so no divideBy).

Forms take Filament\Schemas\Schema and return Schema. Table actions live in Filament\Actions\* and are registered via ->recordActions()/->toolbarActions().

Navigation groups are Katalog, Konten, Operasional. Registration is disabled; create accounts with `php artisan make:filament-user`. User implements FilamentUser::canAccessPanel() returning true — Filament only waives that check in the local environment, so it is required for testing and production.

Phone specs are edited inline on the phone form because all 19 always exist; variants and listings are relation managers. Any field exposing comparable_value must keep its helper text about negated inverted metrics.

Every table showing a related value eager-loads it. Admin code must never import from App\Livewire — tests/Feature/Filament/AdminBoundaryTest.php enforces this in both directions.

Panel tests use Livewire::test(), not Pest\Livewire\livewire() (that plugin is not installed). Pest dataset rows must each be a single-element array or Pest spreads them as multiple arguments.
