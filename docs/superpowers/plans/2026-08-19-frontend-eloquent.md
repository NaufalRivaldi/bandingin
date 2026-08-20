# Bandingin Frontend on Eloquent (Part 3) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make every public page render from the database, so what an editor changes in Filament reaches visitors, and delete the static catalogue layer.

**Architecture:** Catalog querying moves onto `App\Models\Phone` as query scopes; the Livewire pages become coordinators that call scopes and pass models to Blade. The `App\Data` DTOs and `App\Support` catalogues are deleted once every consumer is migrated. Article bodies render through one Action that owns the only Filament dependency on the public side.

**Tech Stack:** Laravel 13.25, Livewire 4.4, Filament 5.7 (renderer only), PHP 8.4, Pest 5.

## Global Constraints

- Every new PHP file starts with `declare(strict_types=1);` — not in the Pint preset, so write it by hand.
- Run `vendor/bin/pint --dirty --format agent` after editing any PHP file, before every commit.
- **Rendered output must not change** except on the article detail page and the variant-price correction in Task 8. The 34 existing frontend tests are the regression net; if one fails, the change is wrong unless the plan says otherwise.
- **N+1 is a bug.** Any page loading phones for display must eager-load `specs.definition`; any page showing a brand name must eager-load `brand`. `Phone::display()` reads the loaded relation and never queries.
- `comparable_value` is always "higher is better" — inverted metrics are negated (198 g is `-198`). `CompareDuel` keeps that convention.
- Money is integer rupiah. Format only at the edge, via `App\Money\Rupiah::format()`.
- Phone slugs are the public identity (`/hp/{slug}`); `Phone::getRouteKeyName()` already returns `slug`.
- Do not touch `app/Filament/**` except to read `PhoneComparisonBlock::class` from the one Action in Task 10.
- Frontend feature tests seed the catalogue with `seedCatalog()` (Task 1). Never assert against an empty database.

---

### Task 1: Money helper, model accessors, and the test seeding helper

**Files:**
- Create: `app/Money/Rupiah.php`
- Modify: `app/Models/Phone.php` — add spec and score accessors
- Modify: `app/Models/PhoneVariant.php`, `app/Models/PhoneListing.php` — add `formattedPrice()`
- Modify: `tests/Pest.php` — add `seedCatalog()`
- Test: `tests/Feature/PhoneAccessorsTest.php`

**Interfaces:**
- Consumes: `App\Models\Phone` and its `specs()` relation, `App\Models\SpecDefinition`.
- Produces:
  - `App\Money\Rupiah::format(int $amount): string` → `"Rp5.499.000"`.
  - `Phone::display(string $key): string` — the spec's display value, or `'—'` when absent.
  - `Phone::comparable(string $key): int` — the spec's comparable value, or `0` when absent.
  - `Phone::scores(): array` — `['Performa' => int, 'Layar' => int, 'Kamera' => int, 'Baterai' => int, 'Nilai per Rupiah' => int]`.
  - `Phone::averageScore(): int` — rounded mean of those five.
  - `Phone::formattedPrice(): string`, `PhoneVariant::formattedPrice(): string`, `PhoneListing::formattedPrice(): string`.
  - `seedCatalog(): void` — a Pest global that runs the catalogue and content seeders.

**Why accessors on the model:** `display()` and `comparable()` read the model's own spec rows. They replace `App\Data\Phone`'s methods so Blade and `CompareDuel` keep a single call shape. They must read the **loaded** `specs` relation, never issue a query, or every card in a 24-phone grid fires 19 queries.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PhoneAccessorsTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;
use App\Money\Rupiah;

it('formats rupiah with dot separators', function (): void {
    expect(Rupiah::format(5_499_000))->toBe('Rp5.499.000')
        ->and(Rupiah::format(0))->toBe('Rp0');
});

it('reads a spec display and comparable value', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create(['key' => 'baterai']);
    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.000 mAh',
        'comparable_value' => 5000,
    ]);

    $phone->load('specs.definition');

    expect($phone->display('baterai'))->toBe('5.000 mAh')
        ->and($phone->comparable('baterai'))->toBe(5000);
});

it('falls back for a spec the phone does not have', function (): void {
    $phone = Phone::factory()->create()->load('specs.definition');

    expect($phone->display('tidakada'))->toBe('—')
        ->and($phone->comparable('tidakada'))->toBe(0);
});

it('exposes the five scores and their average', function (): void {
    $phone = Phone::factory()->create([
        'score_performa' => 72,
        'score_layar' => 80,
        'score_kamera' => 78,
        'score_baterai' => 74,
        'score_nilai' => 70,
    ]);

    expect($phone->scores())->toBe([
        'Performa' => 72,
        'Layar' => 80,
        'Kamera' => 78,
        'Baterai' => 74,
        'Nilai per Rupiah' => 70,
    ])->and($phone->averageScore())->toBe(75);
});

it('formats its own price', function (): void {
    expect(Phone::factory()->create(['price' => 5_499_000])->formattedPrice())
        ->toBe('Rp5.499.000');
});

it('reads specs without extra queries once loaded', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create(['key' => 'ram']);
    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '8 GB',
        'comparable_value' => 8,
    ]);

    $loaded = Phone::with('specs.definition')->find($phone->id);

    DB::enableQueryLog();
    $loaded->display('ram');
    $loaded->comparable('ram');
    $loaded->display('storage');

    expect(DB::getQueryLog())->toBeEmpty();
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PhoneAccessorsTest
```

Expected: FAIL — `Class "App\Money\Rupiah" not found`.

- [ ] **Step 3: Write the money helper**

Create `app/Money/Rupiah.php`:

```php
<?php

declare(strict_types=1);

namespace App\Money;

/**
 * Rupiah has no minor unit, so amounts are whole-integer rupiah everywhere
 * and only formatted at the view edge.
 */
final class Rupiah
{
    public static function format(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
```

- [ ] **Step 4: Add the accessors to Phone**

In `app/Models/Phone.php`, add `use App\Money\Rupiah;` and these methods after `defaultVariant()`:

```php
    /** The five category scores, keyed by the labels the design prints. */
    /** @return array<string, int> */
    public function scores(): array
    {
        return [
            'Performa' => $this->score_performa,
            'Layar' => $this->score_layar,
            'Kamera' => $this->score_kamera,
            'Baterai' => $this->score_baterai,
            'Nilai per Rupiah' => $this->score_nilai,
        ];
    }

    public function averageScore(): int
    {
        $scores = $this->scores();

        return (int) round(array_sum($scores) / count($scores));
    }

    public function formattedPrice(): string
    {
        return Rupiah::format($this->price);
    }

    /** Reads the loaded specs relation — eager-load specs.definition before calling. */
    public function display(string $key): string
    {
        return $this->specFor($key)?->display_value ?? '—';
    }

    /** Reads the loaded specs relation — eager-load specs.definition before calling. */
    public function comparable(string $key): int
    {
        return $this->specFor($key)?->comparable_value ?? 0;
    }

    private function specFor(string $key): ?PhoneSpec
    {
        return $this->specs->first(
            static fn (PhoneSpec $spec): bool => $spec->definition?->key === $key,
        );
    }
```

- [ ] **Step 5: Add formattedPrice to the two price-bearing models**

In `app/Models/PhoneVariant.php` and `app/Models/PhoneListing.php`, add `use App\Money\Rupiah;` and:

```php
    public function formattedPrice(): string
    {
        return Rupiah::format($this->price);
    }
```

- [ ] **Step 6: Add the seeding helper**

In `tests/Pest.php`, add above the closing helpers section:

```php
/**
 * Frontend pages read the database, so page tests need the catalogue present.
 * This runs the same seeders as production, which keeps assertions that name
 * real records (a56, "lists the whole catalogue") meaningful.
 */
function seedCatalog(): void
{
    test()->seed([
        Database\Seeders\SpecDefinitionSeeder::class,
        Database\Seeders\StoreSeeder::class,
        Database\Seeders\BrandSeeder::class,
        Database\Seeders\PhoneSeeder::class,
        Database\Seeders\BrandHighlightSeeder::class,
        Database\Seeders\ArticleSeeder::class,
        Database\Seeders\FaqSeeder::class,
        Database\Seeders\FeaturedMatchupSeeder::class,
    ]);
}
```

- [ ] **Step 7: Run the tests**

```bash
php artisan test --compact --filter=PhoneAccessorsTest
```

Expected: PASS, 6 tests. The query-log test is the one that matters most — it proves `display()` does not lazy-load.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add rupiah helper and phone spec accessors"
```

---

### Task 2: Catalog query scopes

**Files:**
- Modify: `app/Models/Phone.php` — add band/filter constants and five scopes
- Test: `tests/Feature/PhoneScopesTest.php`

**Interfaces:**
- Consumes: `App\Models\Phone`, its `brand` and `specs` relations.
- Produces, all on `Phone` and all no-ops when given an empty argument:
  - `const PRICE_BANDS` — `array<string, array{label: string, min: int, max: int}>` keyed `p1`–`p4`.
  - `const SPEC_FILTERS` — `array<string, array{label: string, key: string, min: int}>` keyed `ram12`, `hz120`, `bat5k`, `fast`; plus `nfc` and `ip68` handled as boolean columns.
  - `scopeSearch(Builder $q, string $term): void`
  - `scopeInPriceBands(Builder $q, array $bandIds): void`
  - `scopeOfBrands(Builder $q, array $brandNames): void`
  - `scopeMatchingSpecFilters(Builder $q, array $filterIds): void`
  - `scopeSortedBy(Builder $q, string $sort): void` — `murah` price asc, `mahal` price desc, `populer` (and anything else) `released_at` desc.

**Why constants move here:** `KatalogPage` currently owns the band and filter tables, but the scopes and the sidebar facet counts both need them. One definition on the model keeps them in sync.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PhoneScopesTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;

function phoneWithSpec(string $key, int $comparable, array $attributes = []): Phone
{
    $phone = Phone::factory()->create($attributes);
    $definition = SpecDefinition::firstOrCreate(
        ['key' => $key],
        ['label' => ucfirst($key), 'group' => 'Performa', 'sort' => 1],
    );

    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
        'display_value' => (string) $comparable,
        'comparable_value' => $comparable,
    ]);

    return $phone;
}

it('searches by phone name', function (): void {
    Phone::factory()->create(['name' => 'Galaxy A56 5G']);
    Phone::factory()->create(['name' => 'Redmi Note 14']);

    expect(Phone::search('galaxy')->pluck('name')->all())->toBe(['Galaxy A56 5G']);
});

it('searches by brand name', function (): void {
    $samsung = Brand::factory()->create(['name' => 'Samsung']);
    Phone::factory()->for($samsung)->create(['name' => 'Galaxy A56 5G']);
    Phone::factory()->create(['name' => 'Redmi Note 14']);

    expect(Phone::search('samsung')->pluck('name')->all())->toBe(['Galaxy A56 5G']);
});

it('ignores a blank search term', function (): void {
    Phone::factory()->count(3)->create();

    expect(Phone::search('')->count())->toBe(3);
});

it('filters by one price band', function (): void {
    Phone::factory()->create(['price' => 1_500_000]);
    Phone::factory()->create(['price' => 5_000_000]);

    expect(Phone::inPriceBands(['p1'])->pluck('price')->all())->toBe([1_500_000]);
});

it('treats several price bands as alternatives', function (): void {
    Phone::factory()->create(['price' => 1_500_000]);
    Phone::factory()->create(['price' => 5_000_000]);
    Phone::factory()->create(['price' => 9_000_000]);

    expect(Phone::inPriceBands(['p1', 'p4'])->count())->toBe(2);
});

it('filters by brand names', function (): void {
    $samsung = Brand::factory()->create(['name' => 'Samsung']);
    Phone::factory()->for($samsung)->create();
    Phone::factory()->create();

    expect(Phone::ofBrands(['Samsung'])->count())->toBe(1);
});

it('filters by a spec threshold', function (): void {
    phoneWithSpec('ram', 12, ['name' => 'Besar']);
    phoneWithSpec('ram', 8, ['name' => 'Kecil']);

    expect(Phone::matchingSpecFilters(['ram12'])->pluck('name')->all())->toBe(['Besar']);
});

it('combines several spec thresholds as AND', function (): void {
    $both = phoneWithSpec('ram', 12, ['name' => 'Dua-duanya']);
    $definition = SpecDefinition::firstOrCreate(
        ['key' => 'baterai'],
        ['label' => 'Kapasitas', 'group' => 'Baterai & Daya', 'sort' => 2],
    );
    PhoneSpec::factory()->create([
        'phone_id' => $both->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.500 mAh',
        'comparable_value' => 5500,
    ]);

    phoneWithSpec('ram', 12, ['name' => 'Cuma RAM']);

    expect(Phone::matchingSpecFilters(['ram12', 'bat5k'])->pluck('name')->all())
        ->toBe(['Dua-duanya']);
});

it('filters nfc and ip68 as boolean columns', function (): void {
    Phone::factory()->create(['nfc' => true, 'ip68' => true, 'name' => 'Lengkap']);
    Phone::factory()->create(['nfc' => false, 'ip68' => false, 'name' => 'Polos']);

    expect(Phone::matchingSpecFilters(['nfc', 'ip68'])->pluck('name')->all())->toBe(['Lengkap']);
});

it('sorts by price in both directions and by release date', function (): void {
    Phone::factory()->create(['price' => 5_000_000, 'released_at' => '2024-01-01', 'name' => 'Mahal lama']);
    Phone::factory()->create(['price' => 2_000_000, 'released_at' => '2026-01-01', 'name' => 'Murah baru']);

    expect(Phone::sortedBy('murah')->pluck('name')->all())->toBe(['Murah baru', 'Mahal lama'])
        ->and(Phone::sortedBy('mahal')->pluck('name')->all())->toBe(['Mahal lama', 'Murah baru'])
        ->and(Phone::sortedBy('populer')->pluck('name')->all())->toBe(['Murah baru', 'Mahal lama']);
});

it('chains scopes together', function (): void {
    $samsung = Brand::factory()->create(['name' => 'Samsung']);
    Phone::factory()->for($samsung)->create(['price' => 5_000_000, 'name' => 'Cocok']);
    Phone::factory()->for($samsung)->create(['price' => 9_000_000, 'name' => 'Kemahalan']);
    Phone::factory()->create(['price' => 5_000_000, 'name' => 'Merek lain']);

    expect(
        Phone::query()->ofBrands(['Samsung'])->inPriceBands(['p3'])->pluck('name')->all()
    )->toBe(['Cocok']);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PhoneScopesTest
```

Expected: FAIL — `Call to undefined method App\Models\Phone::search()`.

- [ ] **Step 3: Add the constants**

In `app/Models/Phone.php`, add above `$fillable`:

```php
    /**
     * Catalogue price bands. Shared by the filter scope and the sidebar counts,
     * so they must not be duplicated in the Livewire component.
     *
     * @var array<string, array{label: string, min: int, max: int}>
     */
    public const array PRICE_BANDS = [
        'p1' => ['label' => 'Rp1–2 juta', 'min' => 1_000_000, 'max' => 2_000_000],
        'p2' => ['label' => 'Rp2–4 juta', 'min' => 2_000_000, 'max' => 4_000_000],
        'p3' => ['label' => 'Rp4–7 juta', 'min' => 4_000_000, 'max' => 7_000_000],
        'p4' => ['label' => 'Rp7 juta ke atas', 'min' => 7_000_000, 'max' => PHP_INT_MAX],
    ];

    /**
     * Minimum-spec filters that resolve against phone_specs rows.
     *
     * @var array<string, array{label: string, key: string, min: int}>
     */
    public const array SPEC_FILTERS = [
        'ram12' => ['label' => 'RAM 12 GB', 'key' => 'ram', 'min' => 12],
        'hz120' => ['label' => 'Layar 120 Hz', 'key' => 'refresh', 'min' => 120],
        'bat5k' => ['label' => 'Baterai 5.000 mAh+', 'key' => 'baterai', 'min' => 5000],
        'fast' => ['label' => 'Isi daya 45W+', 'key' => 'charge', 'min' => 45],
    ];

    /**
     * Filters that are plain boolean columns rather than spec rows.
     *
     * @var array<string, string>
     */
    public const array FLAG_FILTERS = [
        'nfc' => 'Ada NFC',
        'ip68' => 'Tahan air IP68',
    ];
```

- [ ] **Step 4: Add the scopes**

In `app/Models/Phone.php`, after `scopePublished()`:

```php
    /** @param  Builder<$this>  $query */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', '%'.$term.'%')
                ->orWhereRelation('brand', 'name', 'like', '%'.$term.'%');
        });
    }

    /**
     * @param  Builder<$this>  $query
     * @param  list<string>  $bandIds
     */
    public function scopeInPriceBands(Builder $query, array $bandIds): void
    {
        $bands = array_intersect_key(self::PRICE_BANDS, array_flip($bandIds));

        if ($bands === []) {
            return;
        }

        $query->where(function (Builder $query) use ($bands): void {
            foreach ($bands as $band) {
                $query->orWhereBetween('price', [$band['min'], $band['max']]);
            }
        });
    }

    /**
     * @param  Builder<$this>  $query
     * @param  list<string>  $brandNames
     */
    public function scopeOfBrands(Builder $query, array $brandNames): void
    {
        if ($brandNames === []) {
            return;
        }

        $query->whereHas('brand', fn (Builder $brand): Builder => $brand->whereIn('name', $brandNames));
    }

    /**
     * Every selected filter must match, so each becomes its own constraint.
     *
     * @param  Builder<$this>  $query
     * @param  list<string>  $filterIds
     */
    public function scopeMatchingSpecFilters(Builder $query, array $filterIds): void
    {
        foreach ($filterIds as $id) {
            if (isset(self::FLAG_FILTERS[$id])) {
                $query->where($id, true);

                continue;
            }

            $filter = self::SPEC_FILTERS[$id] ?? null;

            if ($filter === null) {
                continue;
            }

            $query->whereHas('specs', fn (Builder $spec): Builder => $spec
                ->where('comparable_value', '>=', $filter['min'])
                ->whereHas('definition', fn (Builder $definition): Builder => $definition
                    ->where('key', $filter['key'])));
        }
    }

    /** @param  Builder<$this>  $query */
    public function scopeSortedBy(Builder $query, string $sort): void
    {
        match ($sort) {
            'murah' => $query->orderBy('price'),
            'mahal' => $query->orderByDesc('price'),
            default => $query->orderByDesc('released_at'),
        };
    }
```

- [ ] **Step 5: Run the tests**

```bash
php artisan test --compact --filter=PhoneScopesTest
```

Expected: PASS, 11 tests.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add catalogue query scopes to the phone model"
```

---

### Task 3: Retype CompareDuel onto the Eloquent model

**Files:**
- Modify: `app/Actions/CompareDuel.php` — swap the DTO type hints
- Modify: `app/Data/DuelResult.php`, `app/Data/DuelRow.php`, `app/Data/DuelGroup.php` — move to `App\Duel`
- Modify: `tests/Unit/CompareDuelTest.php` → becomes a feature test
- Test: `tests/Feature/CompareDuelTest.php`

**Interfaces:**
- Consumes: `App\Models\Phone` with `display()`, `comparable()`, `price`, `formattedPrice()` from Task 1.
- Produces: `CompareDuel::handle(Phone $left, Phone $right, array $groups = self::FULL_GROUPS, bool $onlyDifferences = false): DuelResult` where `Phone` is now `App\Models\Phone`, and the three duel value objects live in `App\Duel\{DuelResult, DuelRow, DuelGroup}`.

**Why the value objects move:** `app/Data` is deleted in Task 13, but the duel result objects are not catalogue DTOs — they are the Action's return shape and stay. Moving them to `App\Duel` lets Task 13 delete `app/Data` wholesale.

**What does not change:** the comparison logic. `comparable_value` kept the "higher wins" convention, and price is still negated so cheaper wins. Every existing assertion about winners, ties and reasons must still pass.

- [ ] **Step 1: Move the duel value objects**

```bash
mkdir -p app/Duel
git mv app/Data/DuelResult.php app/Duel/DuelResult.php
git mv app/Data/DuelRow.php app/Duel/DuelRow.php
git mv app/Data/DuelGroup.php app/Duel/DuelGroup.php
```

Then in all three files change `namespace App\Data;` to `namespace App\Duel;`. In `DuelResult.php`, change the `Phone` import to `use App\Models\Phone;`.

- [ ] **Step 2: Write the failing test**

Create `tests/Feature/CompareDuelTest.php` (and delete the old unit test in Step 6):

```php
<?php

declare(strict_types=1);

use App\Actions\CompareDuel;
use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;

function duelPhone(string $name, int $price, array $specs): Phone
{
    $phone = Phone::factory()->create(['name' => $name, 'price' => $price]);

    foreach ($specs as $key => [$display, $comparable]) {
        $definition = SpecDefinition::firstOrCreate(
            ['key' => $key],
            ['label' => ucfirst($key), 'group' => 'Performa', 'sort' => 1],
        );

        PhoneSpec::factory()->create([
            'phone_id' => $phone->id,
            'spec_definition_id' => $definition->id,
            'display_value' => $display,
            'comparable_value' => $comparable,
        ]);
    }

    return $phone->load('specs.definition');
}

it('awards each row to the higher comparable value', function (): void {
    $left = duelPhone('Kiri', 4_000_000, ['baterai' => ['5.000 mAh', 5000]]);
    $right = duelPhone('Kanan', 4_000_000, ['baterai' => ['5.500 mAh', 5500]]);

    $duel = app(CompareDuel::class)->handle($left, $right, ['' => ['baterai' => 'Kapasitas']]);

    expect($duel->rightWins)->toBe(1)
        ->and($duel->leftWins)->toBe(0);
});

it('awards the price row to the cheaper phone', function (): void {
    $left = duelPhone('Murah', 3_000_000, []);
    $right = duelPhone('Mahal', 9_000_000, []);

    $duel = app(CompareDuel::class)->handle($left, $right, ['' => [CompareDuel::PRICE_KEY => 'Harga']]);

    expect($duel->leftWins)->toBe(1)
        ->and($duel->rightWins)->toBe(0);
});

it('treats identical values as a tie', function (): void {
    $left = duelPhone('Kiri', 4_000_000, ['ram' => ['8 GB', 8]]);
    $right = duelPhone('Kanan', 4_000_000, ['ram' => ['8 GB', 8]]);

    $duel = app(CompareDuel::class)->handle($left, $right, ['' => ['ram' => 'RAM']]);

    expect($duel->leftWins)->toBe(0)
        ->and($duel->rightWins)->toBe(0)
        ->and($duel->groups[0]->rows[0]->isTie())->toBeTrue();
});

it('hides tied rows when only differences are requested', function (): void {
    $left = duelPhone('Kiri', 4_000_000, ['ram' => ['8 GB', 8], 'baterai' => ['5.000 mAh', 5000]]);
    $right = duelPhone('Kanan', 4_000_000, ['ram' => ['8 GB', 8], 'baterai' => ['5.500 mAh', 5500]]);

    $rows = ['ram' => 'RAM', 'baterai' => 'Kapasitas'];

    $all = app(CompareDuel::class)->handle($left, $right, ['' => $rows], false);
    $differing = app(CompareDuel::class)->handle($left, $right, ['' => $rows], true);

    expect($all->groups[0]->rows)->toHaveCount(2)
        ->and($differing->groups[0]->rows)->toHaveCount(1);
});

it('keeps the negation convention so the lighter phone wins on weight', function (): void {
    $left = duelPhone('Ringan', 4_000_000, ['berat' => ['162 g', -162]]);
    $right = duelPhone('Berat', 4_000_000, ['berat' => ['201 g', -201]]);

    $duel = app(CompareDuel::class)->handle($left, $right, ['' => ['berat' => 'Berat']]);

    expect($duel->leftWins)->toBe(1);
});
```

- [ ] **Step 3: Run test to verify it fails**

```bash
php artisan test --compact --filter=CompareDuelTest
```

Expected: FAIL — `CompareDuel::handle()` still type-hints `App\Data\Phone`, so passing a model is a `TypeError`.

- [ ] **Step 4: Retype the Action**

In `app/Actions/CompareDuel.php`, change the imports:

```php
use App\Duel\DuelGroup;
use App\Duel\DuelResult;
use App\Duel\DuelRow;
use App\Models\Phone;
```

The method bodies need no change: `handle()`, `buildRow()` and `distinct()` already call only `display()`, `comparable()`, `formattedPrice()` and `price`, all of which Task 1 put on the model with the same signatures.

- [ ] **Step 5: Run the tests**

```bash
php artisan test --compact --filter=CompareDuelTest
```

Expected: PASS, 5 tests.

- [ ] **Step 6: Delete the superseded unit test**

```bash
git rm tests/Unit/CompareDuelTest.php
```

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "refactor: compare duel operates on eloquent phones"
```

---

### Task 4: Blade components on Eloquent

**Files:**
- Modify: `resources/views/components/phone-card.blade.php`
- Modify: `resources/views/components/article-card.blade.php`
- Modify: `app/Models/Article.php` — add date accessors
- Test: `tests/Feature/ArticleAccessorsTest.php`

**Interfaces:**
- Consumes: `App\Models\Phone` accessors from Task 1.
- Produces:
  - `Article::dateShort(): string` → `"12 Agu 2026"` (matches today's `$article->date`).
  - `Article::dateLong(): string` → `"12 Agustus 2026"`.
  - `Article::meta(): string` → `"Rilis · 12 Agu 2026"`.
  - `phone-card` accepting `App\Models\Phone`, `article-card` accepting `App\Models\Article`.

**Why explicit month names:** the design prints Indonesian dates. Relying on Carbon's locale means the output depends on runtime locale configuration; a literal map is deterministic and matches the existing strings exactly.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ArticleAccessorsTest.php`:

```php
<?php

declare(strict_types=1);

use App\Enums\ArticleCategory;
use App\Models\Article;

it('formats its publish date the way the design prints it', function (): void {
    $article = Article::factory()->create([
        'published_at' => '2026-08-12 08:00:00',
        'category' => ArticleCategory::Rilis,
    ]);

    expect($article->dateShort())->toBe('12 Agu 2026')
        ->and($article->dateLong())->toBe('12 Agustus 2026')
        ->and($article->meta())->toBe('Rilis · 12 Agu 2026');
});

it('handles every month name', function (): void {
    $article = Article::factory()->create(['published_at' => '2026-01-05 08:00:00']);

    expect($article->dateLong())->toBe('5 Januari 2026');
});

it('falls back to an empty date when unpublished', function (): void {
    $article = Article::factory()->draft()->create();

    expect($article->dateShort())->toBe('')
        ->and($article->dateLong())->toBe('');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=ArticleAccessorsTest
```

Expected: FAIL — `Call to undefined method App\Models\Article::dateShort()`.

- [ ] **Step 3: Add the accessors**

In `app/Models/Article.php`, add:

```php
    /** Indonesian month names, indexed by month number. */
    private const array MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /** "12 Agu 2026" — the compact form used on cards. */
    public function dateShort(): string
    {
        if ($this->published_at === null) {
            return '';
        }

        $month = self::MONTHS[(int) $this->published_at->format('n')];

        return $this->published_at->format('j').' '.mb_substr($month, 0, 3).' '.$this->published_at->format('Y');
    }

    /** "12 Agustus 2026" — the long form used on the article page. */
    public function dateLong(): string
    {
        if ($this->published_at === null) {
            return '';
        }

        $month = self::MONTHS[(int) $this->published_at->format('n')];

        return $this->published_at->format('j').' '.$month.' '.$this->published_at->format('Y');
    }

    public function meta(): string
    {
        return $this->category->value.' · '.$this->dateShort();
    }
```

- [ ] **Step 4: Run the accessor tests**

```bash
php artisan test --compact --filter=ArticleAccessorsTest
```

Expected: PASS, 3 tests. Note `mb_substr('Agustus', 0, 3)` is `Agu` and `mb_substr('Januari', 0, 3)` is `Jan`, which match the design's strings.

- [ ] **Step 5: Update phone-card**

In `resources/views/components/phone-card.blade.php`, change the docblock to `/** @var \App\Models\Phone $phone */` and replace every `$phone->id` with `$phone->slug` and every bare `$phone->brand` with `$phone->brand->name`. The `display()`, `formattedPrice()` and `averageScore()` calls stay exactly as they are.

- [ ] **Step 6: Update article-card**

In `resources/views/components/article-card.blade.php`, change the docblock to `/** @var \App\Models\Article $article */`, change `$article->category` to `$article->category->value`, and change `$article->date` to `$article->dateShort()`.

- [ ] **Step 7: Commit**

The page tests still fail at this point because the pages have not been migrated yet — that is expected and is fixed task by task from Task 5 onward.

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "refactor: blade components consume eloquent models"
```

---

### Task 5: HomePage on Eloquent

**Files:**
- Modify: `app/Livewire/HomePage.php`
- Modify: `resources/views/livewire/home-page.blade.php` — matchup labels
- Test: `tests/Feature/HomePageTest.php` — add `seedCatalog()`

**Interfaces:**
- Consumes: `Phone::published()`, `Phone::search()`, `CompareDuel` from Task 3, `App\Models\FeaturedMatchup`, `App\Models\Article`.
- Produces: `HomePage` with the same public properties (`$left`, `$right` as slugs, `$open`, `$leftQuery`, `$rightQuery`, `$onlyDifferences`) and the same method names (`openPicker`, `closePickers`, `pick`, `setMatchup`, `toggleOnlyDifferences`). The `MATCHUPS` constant is **removed** — the view now reads a `$matchups` collection of `FeaturedMatchup` models.

**Behaviour that must not change:** the default duel is `a56` vs `note14pro`, the picker filters by typed query and shows at most 6 suggestions, closing a picker restores the selected phone's name in the box, and the differences toggle hides tied rows.

- [ ] **Step 1: Add seeding to the existing test**

At the top of `tests/Feature/HomePageTest.php`, after the imports, add:

```php
beforeEach(function (): void {
    seedCatalog();
});
```

- [ ] **Step 2: Run to confirm the page is still on static data**

```bash
php artisan test --compact --filter=HomePageTest
```

Expected: PASS — the page still reads `PhoneCatalog`, so seeding changes nothing yet. This establishes the baseline before the swap.

- [ ] **Step 3: Rewrite the component**

Replace the body of `app/Livewire/HomePage.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CompareDuel;
use App\Duel\DuelResult;
use App\Models\Article;
use App\Models\FeaturedMatchup;
use App\Models\Phone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Bandingin.id — Jangan Beli HP Sebelum Dibandingkan')]
class HomePage extends Component
{
    /** How many suggestions the picker dropdown shows. */
    private const int SUGGESTION_LIMIT = 6;

    public string $left = 'a56';

    public string $right = 'note14pro';

    /** Which picker dropdown is open: "left", "right", or null when both are closed. */
    public ?string $open = null;

    public string $leftQuery = '';

    public string $rightQuery = '';

    public bool $onlyDifferences = false;

    public function mount(): void
    {
        $this->syncQueriesToSelection();
    }

    #[Computed]
    public function leftPhone(): Phone
    {
        return $this->resolve($this->left);
    }

    #[Computed]
    public function rightPhone(): Phone
    {
        return $this->resolve($this->right);
    }

    #[Computed]
    public function duel(): DuelResult
    {
        return app(CompareDuel::class)->handle(
            $this->leftPhone(),
            $this->rightPhone(),
            ['' => CompareDuel::SUMMARY_ROWS],
            $this->onlyDifferences,
        );
    }

    /** @return Collection<int, Phone> */
    #[Computed]
    public function leftSuggestions(): Collection
    {
        return $this->suggestions($this->leftQuery);
    }

    /** @return Collection<int, Phone> */
    #[Computed]
    public function rightSuggestions(): Collection
    {
        return $this->suggestions($this->rightQuery);
    }

    public function openPicker(string $side): void
    {
        $this->open = $side;

        $side === 'left'
            ? $this->leftQuery = ''
            : $this->rightQuery = '';
    }

    public function closePickers(): void
    {
        $this->open = null;
        $this->syncQueriesToSelection();
    }

    public function pick(string $side, string $slug): void
    {
        $side === 'left'
            ? $this->left = $slug
            : $this->right = $slug;

        $this->closePickers();
    }

    public function setMatchup(string $left, string $right): void
    {
        $this->left = $left;
        $this->right = $right;

        $this->closePickers();
    }

    public function toggleOnlyDifferences(): void
    {
        $this->onlyDifferences = ! $this->onlyDifferences;
    }

    public function render(): View
    {
        $articles = Article::published()->latest('published_at')->take(5)->get();

        return view('livewire.home-page', [
            'matchups' => FeaturedMatchup::with(['leftPhone', 'rightPhone'])->orderBy('sort')->get(),
            'featured' => $articles->first(),
            'latest' => $articles->skip(1)->values(),
        ]);
    }

    /** Falls back to the first published phone so the duel card is never empty. */
    private function resolve(string $slug): Phone
    {
        return Phone::published()->with('specs.definition')->firstWhere('slug', $slug)
            ?? Phone::published()->with('specs.definition')->orderBy('id')->firstOrFail();
    }

    /** @return Collection<int, Phone> */
    private function suggestions(string $query): Collection
    {
        return Phone::published()
            ->with('brand')
            ->search($query)
            ->orderBy('id')
            ->take(self::SUGGESTION_LIMIT)
            ->get();
    }

    /** Closed pickers show the selected phone's name rather than the search term. */
    private function syncQueriesToSelection(): void
    {
        $this->leftQuery = $this->leftPhone()->name;
        $this->rightQuery = $this->rightPhone()->name;
    }
}
```

- [ ] **Step 4: Update the matchup loop in the view**

In `resources/views/livewire/home-page.blade.php`, the loop over `$matchups` currently reads `$matchup['left']`, `$matchup['right']` and `$matchup['label']`. Change those to `$matchup->leftPhone->slug`, `$matchup->rightPhone->slug` and `$matchup->label()`.

Also change any `$phone->id` in this view to `$phone->slug`.

- [ ] **Step 5: Run the tests**

```bash
php artisan test --compact --filter=HomePageTest
```

Expected: PASS, 5 tests. If "swaps both phones when a popular matchup is picked" fails, the matchup loop in Step 4 is still passing array keys.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "refactor: home page reads phones and matchups from the database"
```

---

### Task 6: KatalogPage on Eloquent

**Files:**
- Modify: `app/Livewire/KatalogPage.php`
- Modify: `resources/views/livewire/katalog-page.blade.php`
- Test: `tests/Feature/KatalogPageTest.php` — add `seedCatalog()`

**Interfaces:**
- Consumes: every scope from Task 2, plus `Phone::PRICE_BANDS`, `Phone::SPEC_FILTERS`, `Phone::FLAG_FILTERS`.
- Produces: `KatalogPage` keeping its public URL-bound properties (`$query`, `$prices`, `$brands`, `$specs`, `$sort`, `$picks`) and its toggle methods. Its own `PRICE_RANGES` and `SPEC_FILTERS` constants are **removed** in favour of the model's.

**Behaviour that must not change:** default listing shows the whole catalogue, search narrows it, the empty state appears when nothing matches, brand and price filters combine, minimum-spec filters apply, both price sorts work, and the duel bar holds at most two picks.

- [ ] **Step 1: Add seeding to the existing test**

At the top of `tests/Feature/KatalogPageTest.php`:

```php
beforeEach(function (): void {
    seedCatalog();
});
```

- [ ] **Step 2: Run to establish the baseline**

```bash
php artisan test --compact --filter=KatalogPageTest
```

Expected: PASS, 11 tests, still on static data.

- [ ] **Step 3: Replace the query and facet methods**

In `app/Livewire/KatalogPage.php`:

Delete the `PRICE_RANGES` and `SPEC_FILTERS` constants. Change the imports to:

```php
use App\Models\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
```

Replace the results computed property with:

```php
    /** @return Collection<int, Phone> */
    #[Computed]
    public function results(): Collection
    {
        return $this->baseQuery()
            ->with(['brand', 'specs.definition'])
            ->sortedBy($this->sort)
            ->get();
    }

    /** Every active filter except price, so the price facet counts stay meaningful. */
    /** @return array<string, int> */
    #[Computed]
    public function priceCounts(): array
    {
        $counts = [];

        foreach (array_keys(Phone::PRICE_BANDS) as $bandId) {
            $counts[$bandId] = Phone::published()
                ->search($this->query)
                ->ofBrands($this->brands)
                ->matchingSpecFilters($this->specs)
                ->inPriceBands([$bandId])
                ->count();
        }

        return $counts;
    }

    /** @return Builder<Phone> */
    private function baseQuery(): Builder
    {
        return Phone::published()
            ->search($this->query)
            ->inPriceBands($this->prices)
            ->ofBrands($this->brands)
            ->matchingSpecFilters($this->specs);
    }
```

Replace the brand-list source with a query:

```php
    /** @return list<string> */
    #[Computed]
    public function brandNames(): array
    {
        return Brand::orderBy('name')->pluck('name')->all();
    }
```

adding `use App\Models\Brand;`.

Replace the `matchesSpec()` private method entirely — spec filtering now happens in SQL, so it is dead code. Delete it.

In `render()`, pass the filter tables from the model:

```php
    public function render(): View
    {
        return view('livewire.katalog-page', [
            'priceRanges' => Phone::PRICE_BANDS,
            'specFilters' => Phone::SPEC_FILTERS + array_map(
                static fn (string $label): array => ['label' => $label],
                Phone::FLAG_FILTERS,
            ),
            'sorts' => self::SORTS,
        ]);
    }
```

- [ ] **Step 4: Update the view's filter loops**

`Phone::PRICE_BANDS` is keyed by id with a `label` inside, whereas the old `PRICE_RANGES` was a list of `['id' => …, 'label' => …]`. In `resources/views/livewire/katalog-page.blade.php`, change the price loop to `@foreach ($priceRanges as $id => $range)` and use `$id` and `$range['label']`. Change the spec filter loop the same way: `@foreach ($specFilters as $id => $filter)` using `$id` and `$filter['label']`.

Change every `$phone->id` in this view to `$phone->slug`, and every bare `$phone->brand` to `$phone->brand->name`.

- [ ] **Step 5: Run the tests**

```bash
php artisan test --compact --filter=KatalogPageTest
```

Expected: PASS, 11 tests.

If "applies minimum-spec filters" fails, check that the seeded catalogue actually has phones meeting each threshold — `Phone::published()->matchingSpecFilters(['ram12'])->count()` should be non-zero against the real seed data.

- [ ] **Step 6: Check the query count is sane**

```bash
php artisan tinker --execute '
DB::enableQueryLog();
$phones = App\Models\Phone::published()->with(["brand","specs.definition"])->get();
foreach ($phones as $p) { $p->display("chipset"); $p->brand->name; }
echo count(DB::getQueryLog())." queries for ".$phones->count()." phones\n";'
```

Expected: 3 queries (phones, brands, specs+definitions), not 24 or 240. If it is higher, an eager load is missing.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "refactor: catalogue filters and sorts run in sql"
```

---

### Task 7: PerbandinganPage on Eloquent

**Files:**
- Modify: `app/Livewire/PerbandinganPage.php`
- Modify: `resources/views/livewire/perbandingan-page.blade.php`
- Test: `tests/Feature/PerbandinganPageTest.php` — add `seedCatalog()`

**Interfaces:**
- Consumes: `Phone::published()`, `Phone::search()`, `CompareDuel`, `PhoneListing`.
- Produces: `PerbandinganPage` keeping `$left`, `$right`, `$open`, `$leftQuery`, `$rightQuery`, `$onlyDifferences` and the same methods, plus a `sellersFor(Phone $phone): Collection` computed source replacing `SellerPricing::compact()`.

**Behaviour that must not change:** the query string picks the pair, an unknown slug falls back to the default pair, the sides swap, the picker replaces one side, tied rows hide behind the toggle, and the verdict lists reasons per side.

**What changes:** seller rows come from real `phone_listings` ordered cheapest first, instead of four synthetic offsets.

- [ ] **Step 1: Add seeding to the existing test**

```php
beforeEach(function (): void {
    seedCatalog();
});
```

- [ ] **Step 2: Run to establish the baseline**

```bash
php artisan test --compact --filter=PerbandinganPageTest
```

Expected: PASS, 7 tests.

- [ ] **Step 3: Swap the data source**

In `app/Livewire/PerbandinganPage.php`, replace the `PhoneCatalog` and `SellerPricing` imports with:

```php
use App\Models\Phone;
use App\Models\PhoneListing;
use Illuminate\Database\Eloquent\Collection;
```

Replace the phone resolution with the same pattern as Task 5:

```php
    private function resolve(string $slug): Phone
    {
        return Phone::published()->with('specs.definition')->firstWhere('slug', $slug)
            ?? Phone::published()->with('specs.definition')->orderBy('id')->firstOrFail();
    }
```

Replace the seller list:

```php
    /** @return Collection<int, PhoneListing> */
    public function sellersFor(Phone $phone): Collection
    {
        return $phone->listings()->with('store')->orderBy('price')->get();
    }
```

Replace the picker suggestions with the `search()` scope, taking 6, exactly as Task 5 does.

- [ ] **Step 4: Update the view**

In `resources/views/livewire/perbandingan-page.blade.php`, the seller rows currently read `$seller['store']`, `$seller['note']`, `$seller['price']` and `$seller['tag']` from an array. Change them to `$seller->store->name`, `$seller->note`, `$seller->formattedPrice()` and `$seller->warranty_tag`.

Change every `$phone->id` to `$phone->slug` and every bare `$phone->brand` to `$phone->brand->name`.

- [ ] **Step 5: Run the tests**

```bash
php artisan test --compact --filter=PerbandinganPageTest
```

Expected: PASS, 7 tests.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "refactor: comparison page reads phones and listings from the database"
```

---

### Task 8: PhoneDetailPage on Eloquent, with the variant-price correction

**Files:**
- Modify: `app/Livewire/PhoneDetailPage.php`
- Modify: `resources/views/livewire/phone-detail-page.blade.php`
- Test: `tests/Feature/PhoneDetailPageTest.php` (new)

**Interfaces:**
- Consumes: `Phone` with `specs.definition`, `variants`, `listings.store`; `SpecDefinition::group`.
- Produces: `PhoneDetailPage` with `$slug`, `$variant` (an index into the phone's variants), `$thumb`, and computed `phone()`, `variantPrice()`, `officialPriceLabel()`, `lowestPriceLabel()`, `sellers()`, `highlights()`, `specGroups()`, `rivals()`.

**The deliberate behaviour change:** today every seller price shifts by the selected variant's delta. Listings are stored per phone, so continuing that would display invented prices. From now on, selecting a variant changes the **official price headline only**; seller rows show their stored prices unchanged. `lowestPriceLabel()` is the cheapest real listing.

**Spec grouping:** `specGroups()` groups the phone's specs by `spec_definitions.group` in `sort` order, then appends the three non-spec rows the design shows — Dimensi, Jaringan and Rilis — from the phone's own columns.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PhoneDetailPageTest.php`:

```php
<?php

declare(strict_types=1);

use App\Livewire\PhoneDetailPage;
use App\Models\Phone;
use Livewire\Livewire;

beforeEach(function (): void {
    seedCatalog();
});

it('renders a seeded phone by slug', function (): void {
    $this->get(route('hp.show', 'a56'))
        ->assertSuccessful()
        ->assertSee('Galaxy A56 5G');
});

it('returns 404 for an unknown slug', function (): void {
    $this->get(route('hp.show', 'tidak-ada'))->assertNotFound();
});

it('returns 404 for an unpublished phone', function (): void {
    Phone::where('slug', 'a56')->update(['published_at' => null]);

    $this->get(route('hp.show', 'a56'))->assertNotFound();
});

it('shows real listing prices from the database', function (): void {
    $phone = Phone::where('slug', 'a56')->with('listings')->sole();
    $cheapest = $phone->listings->min('price');

    Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->assertSee(App\Money\Rupiah::format($cheapest));
});

it('does not shift seller prices when a variant is selected', function (): void {
    $phone = Phone::where('slug', 'a56')->with('listings')->sole();
    $cheapest = App\Money\Rupiah::format($phone->listings->min('price'));

    Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->call('selectVariant', 2)
        ->assertSee($cheapest);
});

it('moves the official price headline with the selected variant', function (): void {
    $phone = Phone::where('slug', 'a56')->with('variants')->sole();
    $third = $phone->variants->get(2);

    Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->call('selectVariant', 2)
        ->assertSee($third->formattedPrice());
});

it('groups specs the way the design groups them', function (): void {
    $groups = Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->instance()
        ->specGroups();

    expect(collect($groups)->pluck('title'))
        ->toContain('Performa', 'Layar', 'Kamera', 'Baterai & Daya', 'Bodi & Lainnya');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PhoneDetailPageTest
```

Expected: FAIL — the page still reads `PhoneCatalog`, so the unpublished-phone test and the listing-price tests fail.

- [ ] **Step 3: Rewrite the component**

Replace `app/Livewire/PhoneDetailPage.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Phone;
use App\Models\PhoneListing;
use App\Models\PhoneVariant;
use App\Money\Rupiah;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PhoneDetailPage extends Component
{
    /** How many gallery thumbnails the design shows under the product shot. */
    private const int THUMBNAIL_COUNT = 4;

    private const int RIVAL_COUNT = 4;

    /** The three rows the design shows that come from the phone, not from specs. */
    private const array EXTRA_BODY_ROWS = [
        'Dimensi' => 'dimensions',
        'Jaringan' => 'network',
        'Rilis' => 'released_label',
    ];

    public string $slug = '';

    public int $variant = 0;

    public int $thumb = 0;

    public function mount(string $phone): void
    {
        abort_unless(
            Phone::published()->where('slug', $phone)->exists(),
            404,
        );

        $this->slug = $phone;
    }

    #[Computed]
    public function phone(): Phone
    {
        return Phone::published()
            ->with(['brand', 'specs.definition', 'variants', 'listings.store'])
            ->where('slug', $this->slug)
            ->firstOrFail();
    }

    /** @return Collection<int, PhoneVariant> */
    #[Computed]
    public function variants(): Collection
    {
        return $this->phone()->variants;
    }

    /** Official price of the variant the visitor is looking at. */
    #[Computed]
    public function variantPrice(): int
    {
        return $this->variants()->get($this->variant)?->price ?? $this->phone()->price;
    }

    #[Computed]
    public function officialPriceLabel(): string
    {
        return Rupiah::format($this->variantPrice());
    }

    /**
     * The cheapest real listing.
     *
     * Listings are stored per phone rather than per variant, so this does not
     * move with the variant pills — showing a shifted price would be inventing
     * a marketplace price we do not have.
     */
    #[Computed]
    public function lowestPriceLabel(): string
    {
        $lowest = $this->sellers()->min('price');

        return Rupiah::format($lowest ?? $this->phone()->price);
    }

    /** @return Collection<int, PhoneListing> */
    #[Computed]
    public function sellers(): Collection
    {
        return $this->phone()->listings->sortBy('price')->values();
    }

    /** @return list<array{label: string, value: string}> */
    #[Computed]
    public function highlights(): array
    {
        $phone = $this->phone();

        return [
            ['label' => 'Chipset', 'value' => $phone->display('chipset')],
            ['label' => 'Layar', 'value' => $phone->display('refresh').' · '.$phone->display('nits')],
            ['label' => 'Baterai', 'value' => $phone->display('baterai')],
            ['label' => 'Kamera', 'value' => $phone->display('kamera')],
        ];
    }

    /** @return list<array{title: string, rows: list<array{label: string, value: string}>}> */
    #[Computed]
    public function specGroups(): array
    {
        $phone = $this->phone();

        $groups = $phone->specs
            ->filter(fn ($spec): bool => $spec->definition !== null)
            ->sortBy(fn ($spec): int => $spec->definition->sort)
            ->groupBy(fn ($spec): string => $spec->definition->group ?? 'Lainnya')
            ->map(fn ($specs, string $title): array => [
                'title' => $title,
                'rows' => $specs->map(fn ($spec): array => [
                    'label' => $spec->definition->label,
                    'value' => $spec->display_value,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return $this->appendBodyRows($groups, $phone);
    }

    /** @return Collection<int, Phone> */
    #[Computed]
    public function rivals(): Collection
    {
        $phone = $this->phone();

        $sameBrand = Phone::published()
            ->with(['brand', 'specs.definition'])
            ->where('brand_id', $phone->brand_id)
            ->whereKeyNot($phone->getKey())
            ->take(self::RIVAL_COUNT)
            ->get();

        if ($sameBrand->count() >= self::RIVAL_COUNT) {
            return $sameBrand;
        }

        $others = Phone::published()
            ->with(['brand', 'specs.definition'])
            ->whereKeyNot($phone->getKey())
            ->whereNotIn('id', $sameBrand->pluck('id'))
            ->take(self::RIVAL_COUNT - $sameBrand->count())
            ->get();

        return $sameBrand->concat($others);
    }

    public function selectVariant(int $index): void
    {
        $this->variant = $index;
    }

    public function selectThumb(int $index): void
    {
        $this->thumb = $index;
    }

    public function render(): View
    {
        return view('livewire.phone-detail-page', [
            'thumbnails' => range(0, self::THUMBNAIL_COUNT - 1),
        ])->title($this->phone()->name.' — Spesifikasi & Harga | Bandingin.id');
    }

    /**
     * The design closes the spec table with three phone-level rows.
     *
     * @param  list<array{title: string, rows: list<array{label: string, value: string}>}>  $groups
     * @return list<array{title: string, rows: list<array{label: string, value: string}>}>
     */
    private function appendBodyRows(array $groups, Phone $phone): array
    {
        $extra = [];

        foreach (self::EXTRA_BODY_ROWS as $label => $attribute) {
            $extra[] = ['label' => $label, 'value' => (string) $phone->{$attribute}];
        }

        foreach ($groups as $index => $group) {
            if ($group['title'] === 'Bodi & Lainnya') {
                $groups[$index]['rows'] = [...$group['rows'], ...$extra];

                return $groups;
            }
        }

        $groups[] = ['title' => 'Bodi & Lainnya', 'rows' => $extra];

        return $groups;
    }
}
```

- [ ] **Step 4: Update the view**

In `resources/views/livewire/phone-detail-page.blade.php`:

- The variant pills looped over `$variants` as arrays with a `label` key. Change to `$this->variants` and `$variant->label`.
- Seller rows looped over arrays. Change `$seller['store']` to `$seller->store->name`, `$seller['note']` to `$seller->note`, `$seller['price']` to `$seller->formattedPrice()`, and `$seller['tag']` to `$seller->warranty_tag`.
- The "Termurah" badge was a stored tag. Render it on the first row instead, since `sellers()` is sorted cheapest first: `@if ($loop->first)` show the badge.
- Change `$phone->id` to `$phone->slug` and bare `$phone->brand` to `$phone->brand->name`.
- Change `$phone->scores` to `$phone->scores()`.

- [ ] **Step 5: Run the tests**

```bash
php artisan test --compact --filter="PhoneDetailPageTest|PagesRenderTest"
```

Expected: PASS. `PagesRenderTest` includes "returns 404 for a phone that is not in the catalogue", which must still hold.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "refactor: phone detail page reads specs, variants and listings from the database"
```

---

### Task 9: MerekPage on Eloquent, with computed brand stats

**Files:**
- Modify: `app/Livewire/MerekPage.php`
- Modify: `resources/views/livewire/merek-page.blade.php`
- Test: `tests/Feature/MerekPageTest.php` (new)

**Interfaces:**
- Consumes: `App\Models\Brand` with `phones` and `highlightedPhones`.
- Produces: `MerekPage` keeping `$brand` (a brand **name**, URL-bound) and `selectBrand(string $name)`, plus computed `activeBrand(): Brand`, `stats(): array` and `brandNames(): array`.

**The two deferred stats land here.** Part 1 stored only `bestselling_range` and `os_update_note`, deliberately leaving "Model aktif" and "Harga terendah" to be computed. `stats()` returns the same four-key shape the view already loops over:

```
['Model aktif' => '14', 'Harga terendah' => 'Rp1,4 jt',
 'Rentang terlaris' => 'Rp2–4 jt', 'Update OS' => '3 tahun']
```

"Harga terendah" is abbreviated to one decimal in millions — `Rp1,4 jt` — matching the design's existing strings.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/MerekPageTest.php`:

```php
<?php

declare(strict_types=1);

use App\Livewire\MerekPage;
use App\Models\Brand;
use Livewire\Livewire;

beforeEach(function (): void {
    seedCatalog();
});

it('shows the active brand and its blurb', function (): void {
    Livewire::test(MerekPage::class)
        ->assertSee('Xiaomi')
        ->assertSee(Brand::where('name', 'Xiaomi')->value('blurb'));
});

it('computes model count and lowest price from real phones', function (): void {
    $brand = Brand::where('name', 'Samsung')->withCount('phones')->sole();

    $stats = Livewire::test(MerekPage::class)
        ->call('selectBrand', 'Samsung')
        ->instance()
        ->stats();

    expect($stats['Model aktif'])->toBe((string) $brand->phones_count)
        ->and($stats)->toHaveKeys(['Model aktif', 'Harga terendah', 'Rentang terlaris', 'Update OS']);
});

it('reads the stored editorial stats', function (): void {
    $stats = Livewire::test(MerekPage::class)
        ->call('selectBrand', 'Samsung')
        ->instance()
        ->stats();

    expect($stats['Update OS'])->toBe(Brand::where('name', 'Samsung')->value('os_update_note'));
});

it('lists four highlighted models for the active brand', function (): void {
    $highlights = Livewire::test(MerekPage::class)
        ->instance()
        ->activeBrand()
        ->highlightedPhones;

    expect($highlights)->toHaveCount(4);
});

it('falls back to the first brand for an unknown name', function (): void {
    Livewire::test(MerekPage::class, ['brand' => 'TidakAda'])
        ->assertSuccessful();
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=MerekPageTest
```

Expected: FAIL — `MerekPage` has no `stats()` method and still returns an `App\Data\Brand`.

- [ ] **Step 3: Rewrite the component**

Replace `app/Livewire/MerekPage.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Brand;
use App\Money\Rupiah;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Merek HP di Indonesia — Bandingin.id')]
class MerekPage extends Component
{
    #[Url]
    public string $brand = 'Xiaomi';

    #[Computed]
    public function activeBrand(): Brand
    {
        return Brand::with('highlightedPhones')
            ->withCount('phones')
            ->firstWhere('name', $this->brand)
            ?? Brand::with('highlightedPhones')->withCount('phones')->orderBy('name')->firstOrFail();
    }

    /**
     * The four figures the design prints. Two are computed from the catalogue
     * and two are editorial columns.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function stats(): array
    {
        $brand = $this->activeBrand();

        return [
            'Model aktif' => (string) $brand->phones_count,
            'Harga terendah' => self::abbreviate($brand->phones()->min('price')),
            'Rentang terlaris' => $brand->bestselling_range,
            'Update OS' => $brand->os_update_note,
        ];
    }

    /** @return list<string> */
    #[Computed]
    public function brandNames(): array
    {
        return Brand::orderBy('name')->pluck('name')->all();
    }

    public function selectBrand(string $name): void
    {
        $this->brand = $name;
    }

    public function render(): View
    {
        return view('livewire.merek-page');
    }

    /** 1_400_000 becomes "Rp1,4 jt", matching the design's compact stat style. */
    private static function abbreviate(?int $price): string
    {
        if ($price === null) {
            return '—';
        }

        if ($price < 1_000_000) {
            return Rupiah::format($price);
        }

        return 'Rp'.rtrim(rtrim(number_format($price / 1_000_000, 1, ',', '.'), '0'), ',').' jt';
    }
}
```

- [ ] **Step 4: Update the view**

In `resources/views/livewire/merek-page.blade.php`:

- `$this->activeBrand->stats` becomes `$this->stats`.
- `$this->activeBrand->models` becomes `$this->activeBrand->highlightedPhones`, and inside that loop `$model['name']` becomes `$model->name`, `$model['price']` becomes `$model->formattedPrice()`, and `$model['spec']` becomes `$model->network` (which is where the seeder put the catalogue's spec blurb for highlight-only phones).
- `$brandNames` passed from `render()` becomes `$this->brandNames`.
- `$this->activeBrand->pros` / `->cons` / `->blurb` stay as they are — the model has the same array casts.

- [ ] **Step 5: Run the tests**

```bash
php artisan test --compact --filter="MerekPageTest|ContentPagesTest"
```

Expected: PASS. `ContentPagesTest` includes "switches brands on the merek page", which must still hold. Add `beforeEach(fn () => seedCatalog())` to `ContentPagesTest` if it is not already there.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "refactor: brand page computes its stats from the catalogue"
```

---

### Task 10: RenderArticleBody — the one Filament bridge

**Files:**
- Create: `app/Actions/RenderArticleBody.php`
- Test: `tests/Feature/RenderArticleBodyTest.php`

**Interfaces:**
- Consumes: `App\Models\Article`, `Filament\Forms\Components\RichEditor\RichContentRenderer`, `App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\PhoneComparisonBlock`.
- Produces: `RenderArticleBody::handle(Article $article): HtmlString`.

**Why an Action rather than calling the renderer in the view:** Part 2's arch test asserts `App\Livewire` never uses `App\Filament`. Rendering comparison blocks needs Filament on the public side. Putting that dependency in exactly one single-purpose class keeps the boundary true and reviewable — Livewire depends on the Action, and a new arch assertion pins that only this class may reference the block.

**Verified API:** `RichContentRenderer::make(string|array|null $content)`, `->customBlocks(?array $blocks)`, `->toHtml(): string` — confirmed against `vendor/filament/forms/src/Components/RichEditor/RichContentRenderer.php`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/RenderArticleBodyTest.php`:

```php
<?php

declare(strict_types=1);

use App\Actions\RenderArticleBody;
use App\Models\Article;
use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;

it('passes plain html through unchanged', function (): void {
    $article = Article::factory()->create([
        'body' => '<p>Paragraf pertama.</p><p>Paragraf kedua.</p>',
    ]);

    $html = (string) app(RenderArticleBody::class)->handle($article);

    expect($html)->toContain('Paragraf pertama')
        ->and($html)->toContain('Paragraf kedua');
});

it('returns an htmlstring so blade can echo it', function (): void {
    $article = Article::factory()->create(['body' => '<p>Halo.</p>']);

    expect(app(RenderArticleBody::class)->handle($article))
        ->toBeInstanceOf(Illuminate\Support\HtmlString::class);
});

it('renders an empty body without failing', function (): void {
    $article = Article::factory()->create(['body' => '']);

    expect((string) app(RenderArticleBody::class)->handle($article))->toBeString();
});

it('expands an embedded comparison block from live phone data', function (): void {
    $definition = SpecDefinition::factory()->create(['key' => 'baterai', 'label' => 'Kapasitas']);
    $left = Phone::factory()->create(['name' => 'Galaxy A56 5G']);
    $right = Phone::factory()->create(['name' => 'Redmi Note 14 Pro 5G']);

    foreach ([[$left, '5.000 mAh', 5000], [$right, '5.500 mAh', 5500]] as [$phone, $display, $comparable]) {
        PhoneSpec::factory()->create([
            'phone_id' => $phone->id,
            'spec_definition_id' => $definition->id,
            'display_value' => $display,
            'comparable_value' => $comparable,
        ]);
    }

    // The editor stores a block as a custom node carrying its config.
    $body = json_encode([
        'type' => 'doc',
        'content' => [[
            'type' => 'customBlock',
            'attrs' => [
                'id' => 'phone_comparison',
                'config' => [
                    'leftPhoneId' => $left->id,
                    'rightPhoneId' => $right->id,
                    'specKeys' => ['baterai'],
                ],
            ],
        ]],
    ]);

    $article = Article::factory()->create(['body' => $body]);

    $html = (string) app(RenderArticleBody::class)->handle($article);

    expect($html)->toContain('Galaxy A56 5G')
        ->and($html)->toContain('5.500 mAh');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=RenderArticleBodyTest
```

Expected: FAIL — `Class "App\Actions\RenderArticleBody" not found`.

- [ ] **Step 3: Write the Action**

Create `app/Actions/RenderArticleBody.php`:

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\PhoneComparisonBlock;
use App\Models\Article;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\HtmlString;

/**
 * The only place the public site touches Filament.
 *
 * Article bodies are authored in the admin RichEditor and may embed a phone
 * comparison block, which has to be expanded from live catalogue data at read
 * time. Keeping that dependency in one Action lets the Livewire pages stay
 * free of Filament imports, which tests/Feature/Filament/AdminBoundaryTest.php
 * asserts.
 */
final class RenderArticleBody
{
    public function handle(Article $article): HtmlString
    {
        $body = $article->body;

        if ($body === null || $body === '') {
            return new HtmlString('');
        }

        return new HtmlString(
            RichContentRenderer::make($body)
                ->customBlocks([PhoneComparisonBlock::class])
                ->toHtml(),
        );
    }
}
```

- [ ] **Step 4: Run the tests**

```bash
php artisan test --compact --filter=RenderArticleBodyTest
```

Expected: PASS, 4 tests.

If the comparison-block test fails on the JSON node shape, inspect what the editor actually stores by creating an article with a block in the panel and dumping `Article::latest()->value('body')`, then correct the fixture. The other three tests are the ones that gate this task; if the block shape proves different, fix the fixture rather than the Action.

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: render article bodies through filament rich content"
```

---

### Task 11: Berita pages on Eloquent

**Files:**
- Modify: `app/Livewire/BeritaPage.php`, `app/Livewire/BeritaDetailPage.php`
- Modify: `resources/views/livewire/berita-page.blade.php`
- Modify: `resources/views/livewire/berita-detail-page.blade.php` — rebuild the body
- Test: `tests/Feature/BeritaPageTest.php` (new)

**Interfaces:**
- Consumes: `Article::published()`, `ArticleCategory`, `RenderArticleBody` from Task 10, `Article::dateShort()`/`dateLong()`/`meta()` from Task 4.
- Produces:
  - `BeritaPage` keeping `$category` (URL-bound as `kategori`) and `setCategory()`, with computed `articles()`, `showsFeatured()`, `listHeading()`.
  - `BeritaDetailPage` keeping `$slug` and `mount(string $slug)`, with computed `article()`, `related()`, and `body()` returning the rendered `HtmlString`.

**Category constant:** `ArticleRepository::ALL_CATEGORIES` was `'Semua'`. That string moves onto `BeritaPage` as `public const string ALL_CATEGORIES = 'Semua';` so the URL contract and the chip labels are unchanged.

**The view rebuild:** `berita-detail-page.blade.php` currently loops `$article->body` paragraphs, prints `$article->quote` in a pull-quote, renders `$article->specs` as a table, then loops `$article->bodyRest`. All four are gone. They are replaced by a single `{!! $this->body !!}` inside a prose wrapper. Keep the surrounding chrome — breadcrumb, title, `$article->dateLong()`, tags, related rail — exactly as-is.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/BeritaPageTest.php`:

```php
<?php

declare(strict_types=1);

use App\Livewire\BeritaDetailPage;
use App\Livewire\BeritaPage;
use App\Models\Article;
use Livewire\Livewire;

beforeEach(function (): void {
    seedCatalog();
});

it('lists published articles and features the newest', function (): void {
    $newest = Article::published()->latest('published_at')->first();

    Livewire::test(BeritaPage::class)
        ->assertSee($newest->title)
        ->assertSet('category', BeritaPage::ALL_CATEGORIES);
});

it('hides unpublished articles', function (): void {
    $draft = Article::factory()->draft()->create(['title' => 'Draf rahasia']);

    Livewire::test(BeritaPage::class)->assertDontSee($draft->title);
});

it('changes the list heading when a category is picked', function (): void {
    $component = Livewire::test(BeritaPage::class)->call('setCategory', 'Panduan')->instance();

    expect($component->listHeading())->toBe('Panduan')
        ->and($component->showsFeatured())->toBeFalse();
});

it('opens an article and renders its body', function (): void {
    $article = Article::published()->first();

    $this->get(route('berita.show', $article->slug))
        ->assertSuccessful()
        ->assertSee($article->title);
});

it('returns 404 for an unknown article', function (): void {
    $this->get(route('berita.show', 'tidak-ada'))->assertNotFound();
});

it('returns 404 for a draft article', function (): void {
    $draft = Article::factory()->draft()->create();

    $this->get(route('berita.show', $draft->slug))->assertNotFound();
});

it('prefers same-category articles in the related rail', function (): void {
    $article = Article::published()->first();

    $related = Livewire::test(BeritaDetailPage::class, ['slug' => $article->slug])
        ->instance()
        ->related();

    expect($related)->not->toBeEmpty()
        ->and($related->pluck('id'))->not->toContain($article->id);
});
```

`showsFeatured` is a computed method, so it is asserted through `->instance()` rather than `assertSet()`.

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=BeritaPageTest
```

Expected: FAIL — `BeritaPage::ALL_CATEGORIES` does not exist, and drafts are not filtered.

- [ ] **Step 3: Rewrite BeritaPage**

Replace `app/Livewire/BeritaPage.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\ArticleCategory;
use App\Models\Article;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Berita & Ulasan — Bandingin.id')]
class BeritaPage extends Component
{
    public const string ALL_CATEGORIES = 'Semua';

    #[Url(as: 'kategori')]
    public string $category = self::ALL_CATEGORIES;

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    /**
     * The featured article already has its own hero block on the unfiltered
     * list, so it is dropped from the grid there.
     *
     * @return Collection<int, Article>
     */
    #[Computed]
    public function articles(): Collection
    {
        $query = Article::published()->latest('published_at');

        if (! $this->showsFeatured()) {
            return $query->where('category', $this->category)->get();
        }

        $featured = $this->featured();

        return $query->when(
            $featured !== null,
            fn ($query) => $query->whereKeyNot($featured->getKey()),
        )->get();
    }

    #[Computed]
    public function featured(): ?Article
    {
        return Article::published()->latest('published_at')->first();
    }

    /** The featured hero only makes sense on the unfiltered list. */
    #[Computed]
    public function showsFeatured(): bool
    {
        return $this->category === self::ALL_CATEGORIES;
    }

    #[Computed]
    public function listHeading(): string
    {
        return $this->showsFeatured() ? 'Terbaru' : $this->category;
    }

    public function render(): View
    {
        return view('livewire.berita-page', [
            'categories' => [self::ALL_CATEGORIES, ...ArticleCategory::values()],
        ]);
    }
}
```

- [ ] **Step 4: Rewrite BeritaDetailPage**

Replace `app/Livewire/BeritaDetailPage.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\RenderArticleBody;
use App\Models\Article;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BeritaDetailPage extends Component
{
    /** How many articles the related rail shows. */
    private const int RELATED_COUNT = 3;

    public string $slug = '';

    public function mount(string $slug): void
    {
        abort_unless(Article::published()->where('slug', $slug)->exists(), 404);

        $this->slug = $slug;
    }

    #[Computed]
    public function article(): Article
    {
        return Article::published()->where('slug', $this->slug)->firstOrFail();
    }

    #[Computed]
    public function body(): HtmlString
    {
        return app(RenderArticleBody::class)->handle($this->article());
    }

    /**
     * Same-category articles first, then anything else, so the rail is never empty.
     *
     * @return Collection<int, Article>
     */
    #[Computed]
    public function related(): Collection
    {
        $article = $this->article();

        return Article::published()
            ->whereKeyNot($article->getKey())
            ->orderByRaw('case when category = ? then 0 else 1 end', [$article->category->value])
            ->latest('published_at')
            ->take(self::RELATED_COUNT)
            ->get();
    }

    /** The page title is per-article, so it cannot be set with the #[Title] attribute. */
    public function render(): View
    {
        return view('livewire.berita-detail-page')
            ->title($this->article()->title.' — Bandingin.id');
    }
}
```

- [ ] **Step 5: Rebuild the detail view's body**

In `resources/views/livewire/berita-detail-page.blade.php`:

- Replace `{{ $article->dateLong }}` with `{{ $article->dateLong() }}`.
- Delete the `@foreach ($article->body as $paragraph)` loop, the `$article->quote` pull-quote block, the whole `@if ($article->specs)` table block, and the `@foreach ($article->bodyRest as $paragraph)` loop.
- In their place put a single prose container:

```blade
        <div class="article-prose mt-6 space-y-4 text-[15.5px] leading-[1.75] text-ink">
            {!! $this->body !!}
        </div>
```

- Change `$article->category` to `$article->category->value` and `$article->readMinutes` to `$article->read_minutes` wherever they appear.

Echoing the body unescaped is safe: it is authored by authenticated admins through the RichEditor, which is the same trust model Filament applies in its own panel.

- [ ] **Step 6: Update the listing view**

In `resources/views/livewire/berita-page.blade.php`, change `$featured` to `$this->featured`, and any `$article->category` to `$article->category->value`, `$article->date` to `$article->dateShort()`, `$article->readMinutes` to `$article->read_minutes`.

- [ ] **Step 7: Add prose styling for rendered HTML**

The rendered body now emits raw `<p>`, `<h3>`, `<blockquote>`, `<ul>` and `<table>` elements that previously had per-element Tailwind classes. Add scoped rules to `resources/css/app.css` so the article keeps its typography, using the existing theme tokens rather than new hex values:

```css
.article-prose h3 { @apply mt-8 font-display text-xl font-extrabold tracking-[-.02em]; }
.article-prose blockquote { @apply my-6 border-l-4 border-brand pl-4 font-display text-[clamp(18px,2vw,22px)] leading-[1.4] font-bold tracking-[-.02em]; }
.article-prose ul { @apply list-disc space-y-2 pl-5; }
.article-prose a { @apply text-brand underline; }
.article-prose table { @apply w-full border-collapse text-sm; }
.article-prose th, .article-prose td { @apply border-b border-line px-2.5 py-3 text-left; }
```

Then rebuild assets:

```bash
npm run build
```

- [ ] **Step 8: Run the tests**

```bash
php artisan test --compact --filter="BeritaPageTest|ContentPagesTest|PagesRenderTest"
```

Expected: PASS. `ContentPagesTest` covers "filters articles by category and hides the featured block" and "opens an article on its own page"; add `beforeEach(fn () => seedCatalog())` there if not already present.

- [ ] **Step 9: Check the article page by eye**

```bash
curl -s http://bandingin.test/berita/$(php artisan tinker --execute 'echo App\Models\Article::published()->value("slug");' | tail -1) | grep -c "article-prose"
```

Expected: `1`. Then open the same URL in a browser and confirm the body reads as formatted prose rather than a wall of unstyled text.

- [ ] **Step 10: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "refactor: article pages render rich bodies from the database"
```

---

### Task 12: FaqPage on Eloquent

**Files:**
- Modify: `app/Livewire/FaqPage.php`
- Modify: `resources/views/livewire/faq-page.blade.php`
- Test: `tests/Feature/FaqPageTest.php` (new)

**Interfaces:**
- Consumes: `App\Models\Faq`, `App\Enums\FaqTopic`.
- Produces: `FaqPage` keeping `$topic` (URL-bound as `topik`), `$open`, `selectTopic()` and `toggle()`, with computed `faqs()`.

**What stays hardcoded:** the three "steps" cards. They are prose about how to use the site, not data an editor maintains, and Part 1 deliberately left them out of the schema. They move from `FaqRepository::steps()` to a constant on the component.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/FaqPageTest.php`:

```php
<?php

declare(strict_types=1);

use App\Livewire\FaqPage;
use App\Models\Faq;
use Livewire\Livewire;

beforeEach(function (): void {
    seedCatalog();
});

it('lists every faq by default', function (): void {
    Livewire::test(FaqPage::class)
        ->assertSet('topic', FaqPage::ALL_TOPICS)
        ->assertSee(Faq::orderBy('sort')->value('question'));
});

it('filters by topic', function (): void {
    $component = Livewire::test(FaqPage::class)->call('selectTopic', 'Harga')->instance();

    expect($component->faqs()->pluck('topic')->unique()->pluck('value')->all())
        ->toBe(['Harga']);
});

it('keeps the faqs in their curated order', function (): void {
    $faqs = Livewire::test(FaqPage::class)->instance()->faqs();

    expect($faqs->pluck('sort')->all())->toBe($faqs->pluck('sort')->sort()->values()->all());
});

it('opens one answer at a time', function (): void {
    Livewire::test(FaqPage::class)
        ->assertSet('open', 0)
        ->call('toggle', 2)
        ->assertSet('open', 2)
        ->call('toggle', 2)
        ->assertSet('open', -1);
});

it('resets the open item when the topic changes', function (): void {
    Livewire::test(FaqPage::class)
        ->call('toggle', 3)
        ->call('selectTopic', 'Skor')
        ->assertSet('open', 0);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=FaqPageTest
```

Expected: FAIL — `FaqPage::ALL_TOPICS` does not exist.

- [ ] **Step 3: Rewrite the component**

Replace `app/Livewire/FaqPage.php` with:

```php
<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\FaqTopic;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Pertanyaan yang Sering Diajukan — Bandingin.id')]
class FaqPage extends Component
{
    public const string ALL_TOPICS = 'Semua';

    /**
     * How to use the site, not editorial data — deliberately not in the schema.
     *
     * @var list<array{n: string, title: string, text: string}>
     */
    private const array STEPS = [
        ['n' => '1', 'title' => 'Pilih dua HP', 'text' => 'Ketik nama HP di dua kolom pencarian, atau pakai pintasan duel populer di bawah tombol bandingkan.'],
        ['n' => '2', 'title' => 'Baca penanda unggul', 'text' => 'Setiap baris menandai HP yang menang. Nyalakan “hanya perbedaan” kalau ingin melihat yang berbeda saja.'],
        ['n' => '3', 'title' => 'Cek harga sebelum beli', 'text' => 'Buka daftar penjual untuk melihat harga per toko hari ini, lalu pasang notifikasi kalau ingin menunggu harga turun.'],
    ];

    #[Url(as: 'topik')]
    public string $topic = self::ALL_TOPICS;

    /** Index of the open accordion item within the filtered list; -1 when all are closed. */
    public int $open = 0;

    /** @return Collection<int, Faq> */
    #[Computed]
    public function faqs(): Collection
    {
        return Faq::query()
            ->when($this->topic !== self::ALL_TOPICS, fn ($query) => $query->where('topic', $this->topic))
            ->orderBy('sort')
            ->get();
    }

    public function selectTopic(string $topic): void
    {
        $this->topic = $topic;
        $this->open = 0;
    }

    public function toggle(int $index): void
    {
        $this->open = $this->open === $index ? -1 : $index;
    }

    public function render(): View
    {
        return view('livewire.faq-page', [
            'steps' => self::STEPS,
            'topics' => [self::ALL_TOPICS, ...FaqTopic::values()],
        ]);
    }
}
```

Copy the `STEPS` strings verbatim from `app/Support/FaqRepository::steps()` — including the curly quotes — so the rendered copy is byte-identical.

- [ ] **Step 4: Update the view**

In `resources/views/livewire/faq-page.blade.php`, the accordion loops over arrays. Change `$faq['question']` to `$faq->question` and `$faq['answer']` to `$faq->answer`. The `$steps` and `$topics` loops are unchanged.

- [ ] **Step 5: Run the tests**

```bash
php artisan test --compact --filter="FaqPageTest|ContentPagesTest"
```

Expected: PASS. `ContentPagesTest` covers "filters the faq by topic" and "opens one faq answer at a time".

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "refactor: faq page reads questions from the database"
```

---

### Task 13: Retire the static layer

**Files:**
- Modify: `database/seeders/*.php` — inline the data
- Delete: `app/Support/`, `app/Data/`, `tests/Unit/PhoneCatalogTest.php`
- Modify: `.ai/rules/support.md` → replaced via `record-rule`
- Test: `tests/Feature/StaticLayerRetiredTest.php`

**Interfaces:**
- Consumes: everything from Tasks 1–12.
- Produces: a codebase with no `App\Support` and no `App\Data` namespace, and seeders that carry their own data.

**Order matters.** The seeders currently read `App\Support\*` at runtime, and the frontend tests now depend on those seeders. Inline the data **before** deleting anything, and let the existing seeder tests — which assert exact counts and verbatim values — prove the inlining was faithful.

- [ ] **Step 1: Confirm the seeder tests are the safety net**

```bash
php artisan test --compact --filter="PhoneSeederTest|ContentSeederTest|CatalogSeederTest"
```

Expected: PASS, 22 tests. These assert 12 phones, 19 specs each, verbatim spec values (`198 g` / `-198`), four listings per phone at the old offsets, and preserved article dates. They are what makes the next step safe.

- [ ] **Step 2: Inline the phone data**

`database/seeders/PhoneSeeder.php` currently calls `PhoneCatalog::all()` and `SellerPricing::detailed()`. Copy `PhoneCatalog::rows()` verbatim into a private `const array PHONES` on the seeder, and `SellerPricing`'s store/offset table into a private `const array SELLER_OFFSETS`, then iterate those instead of the Support classes.

Two details to preserve exactly:

- `SellerPricing::VARIANTS` (labels `8/256 GB`, `12/256 GB`, `12/512 GB` with deltas `0`, `500_000`, `1_200_000`) becomes `const array VARIANTS` on the seeder.
- `SellerPricing::LOWEST_OFFSET` is `-520_000` and belongs to the `Toko distributor` row.

Because the raw rows are arrays rather than DTOs, the seeder now reads `$row['id']` where it read `$data->id`, `$row['dimensi']` where it read `$data->dimensi`, and `$row['specs'][$key]` gives `[$display, $comparable]` pairs directly.

- [ ] **Step 3: Run the phone seeder test**

```bash
php artisan test --compact --filter=PhoneSeederTest
```

Expected: PASS, 12 tests. Any failure here means the inlining lost data — fix the seeder, not the test.

- [ ] **Step 4: Inline the brand data**

Copy `BrandCatalog::rows()` into `database/seeders/BrandSeeder.php` as `const array BRANDS`, and have `BrandHighlightSeeder` read the same constant via `BrandSeeder::BRANDS` rather than `BrandCatalog::all()`. Make that constant `public` so the highlight seeder can reach it, with a docblock noting why.

- [ ] **Step 5: Inline the article and faq data**

Copy `ArticleRepository::rows()` into `ArticleSeeder` as `const array ARTICLES` and `FaqRepository::rows()` into `FaqSeeder` as `const array FAQS`. `ArticleSeeder` was already converting `body` / `quote` / `bodyRest` / `specs` into HTML; that logic stays, it just reads array keys instead of DTO properties.

`FeaturedMatchupSeeder` reads `HomePage::MATCHUPS`, which Task 5 deleted. Inline the four pairs as `const array MATCHUPS` on the seeder:

```php
    /** @var list<array{left: string, right: string}> */
    private const array MATCHUPS = [
        ['left' => 'a56', 'right' => 'note14pro'],
        ['left' => 'x7pro', 'right' => 'r13pp'],
        ['left' => 'ip16', 'right' => 's25'],
        ['left' => 'note40pro', 'right' => 'note14pro'],
    ];
```

- [ ] **Step 6: Run all seeder tests**

```bash
php artisan test --compact --filter="PhoneSeederTest|ContentSeederTest|CatalogSeederTest"
```

Expected: PASS, 22 tests, with no seeder importing `App\Support` any more. Verify:

```bash
grep -rn "App..Support" database/ || echo "no Support references left in seeders"
```

- [ ] **Step 7: Write the retirement test**

Create `tests/Feature/StaticLayerRetiredTest.php`:

```php
<?php

declare(strict_types=1);

arch('the static catalogue layer is gone')
    ->expect('App\Support')
    ->toBeUsedIn([]);

arch('the catalogue dtos are gone')
    ->expect('App\Data')
    ->toBeUsedIn([]);

arch('only the render action bridges to filament')
    ->expect('App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\PhoneComparisonBlock')
    ->toOnlyBeUsedIn([
        'App\Actions\RenderArticleBody',
        'App\Filament\Resources\Articles\Schemas\ArticleForm',
    ]);
```

- [ ] **Step 8: Delete the static layer**

```bash
git rm -r app/Support app/Data
git rm tests/Unit/PhoneCatalogTest.php
```

- [ ] **Step 9: Verify nothing references the deleted namespaces**

```bash
grep -rn "App..Support\|App..Data" app/ resources/ database/ tests/ || echo "clean"
```

Expected: `clean`. If anything remains, it is a missed call site — fix it before continuing.

- [ ] **Step 10: Run the whole suite**

```bash
composer test
```

Expected: PASS, everything green. This is the moment the migration is either complete or not.

- [ ] **Step 11: Verify the site end to end**

```bash
php artisan migrate:fresh --seed
for p in / /katalog /hp/a56 /bandingkan /berita /merek /faq /lapor /legal /cara-kami-menilai /sumber-data-harga; do
  echo "$(curl -s -o /dev/null -w '%{http_code}' http://bandingin.test$p)  $p"
done
```

Expected: `200` for all eleven paths.

Then prove the panel actually drives the site — the whole point of three parts of work:

```bash
php artisan tinker --execute '
$p = App\Models\Phone::where("slug","a56")->first();
$p->update(["name" => "Galaxy A56 5G Bukti"]);
echo "renamed\n";'
curl -s http://bandingin.test/hp/a56 | grep -c "Galaxy A56 5G Bukti"
php artisan tinker --execute '
App\Models\Phone::where("slug","a56")->update(["name" => "Galaxy A56 5G"]);'
```

Expected: `1` from the grep — an edit made outside the static layer reaches a visitor.

- [ ] **Step 12: Replace the Support rule**

Use the Boost `record-rule` MCP tool with:

- **glob:** `app/Models/**`
- **title:** Catalogue querying lives in Phone query scopes
- **note:**

```
The public site reads the database directly — App\Support and App\Data no longer exist, and tests/Feature/StaticLayerRetiredTest.php asserts they stay gone.

Catalogue filtering belongs on App\Models\Phone as scopes: search, inPriceBands, ofBrands, matchingSpecFilters, sortedBy, published. PRICE_BANDS, SPEC_FILTERS and FLAG_FILTERS are constants on that model so the scopes and the sidebar facet counts share one definition — do not duplicate them in a Livewire component.

Phone::display($key) and comparable($key) read the LOADED specs relation and never query. Any page listing phones must eager-load specs.definition and brand, or a 24-card grid fires hundreds of queries.

Article bodies are rich HTML rendered through App\Actions\RenderArticleBody, which is the only class on the public side allowed to touch App\Filament.
```

Then delete the now-obsolete `.ai/rules/support.md`:

```bash
git rm .ai/rules/support.md
```

and remove its row from `.ai/rules/index.md`.

- [ ] **Step 13: Final format and commit**

```bash
vendor/bin/pint --dirty --format agent
composer test
git add -A
git commit -m "refactor: retire the static catalogue layer"
```

---

## Done criteria

- `composer test` is green.
- `grep -rn "App..Support\|App..Data" app/ resources/ database/ tests/` returns nothing.
- All eleven public routes return 200 after `migrate:fresh --seed`.
- An edit made through Eloquent (or the Filament panel) is visible on the public site.
- The catalogue page issues a constant number of queries regardless of how many phones it lists.
- `vendor/bin/pint --dirty --format agent` reports no changes.

## What this deliberately leaves undone

- **Listings are still per-phone, not per-variant.** The detail page now shows real listing prices that do not move with the variant pills. Giving each variant its own per-store prices is a schema change plus admin rework — a Part 4 if it is ever worth it.
- No caching, pagination, or full-text search.
- No visual redesign beyond the article body and the variant-price correction.
