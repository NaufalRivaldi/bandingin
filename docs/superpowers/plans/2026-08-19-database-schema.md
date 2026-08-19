# Bandingin Database Schema (Part 1) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move Bandingin's catalog data (phones, brands, specs, prices, articles, FAQs) out of hardcoded `app/Support/*` classes and into a seeded database, without changing a single line of frontend code.

**Architecture:** Fourteen new tables built bottom-up (brands → phones → specs → pricing → content), one Eloquent model each, with seeders that read the existing `App\Support\*` classes directly so the seeded data is provably identical to today's dummy data. The Livewire pages keep reading from the Support classes throughout Part 1; the swap happens in Part 3.

**Tech Stack:** Laravel 13, PHP 8.5, SQLite (in-memory for tests), Pest 4, Eloquent factories/seeders.

## Global Constraints

- Every new PHP file starts with `declare(strict_types=1);` — this is **not** in the Laravel Pint preset, so it must be written by hand.
- Run `vendor/bin/pint --dirty --format agent` after editing any PHP file, before every commit.
- Create files with `php artisan make:*` commands, passing `--no-interaction`.
- Money is stored as integer rupiah (`unsignedInteger`). Never decimals, never formatted strings.
- `comparable_value` on specs is always "higher is better" — inverted metrics are stored negated (berat 198 g → `-198`). This preserves `App\Actions\CompareDuel`'s existing logic unchanged.
- Explicit return types and parameter type hints on every method. Curly braces on every control structure. PHPDoc array shapes over inline comments.
- **Do not modify** any file in `app/Livewire/`, `app/Support/`, `app/Data/`, or `resources/views/`. Part 1 is additive only.
- `RefreshDatabase` is already active in `tests/Pest.php` for the `Feature` suite — no change needed there.
- Model tests go in `tests/Feature/` (they touch the database).
- Phone slugs must keep today's values (`a56`, `note14pro`, `x7pro`, `r13pp`, `ip16`, `s25`, `note40pro`, `a16`, `note14`, `c75`, `hot50`, `m7pro`) so `/hp/{phone}` URLs do not break.

---

### Task 1: Brands

**Files:**
- Create: `database/migrations/2026_08_19_100000_create_brands_table.php`
- Create: `app/Models/Brand.php`
- Create: `database/factories/BrandFactory.php`
- Test: `tests/Feature/BrandModelTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `App\Models\Brand` with `$fillable` = name, slug, blurb, pros, cons, bestselling_range, os_update_note; `pros`/`cons` cast to `array`; `phones(): HasMany`; `getRouteKeyName(): string` returning `'slug'`. `Database\Factories\BrandFactory`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/BrandModelTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Brand;

it('casts pros and cons to arrays', function (): void {
    $brand = Brand::factory()->create([
        'pros' => ['Layar cerah', 'Servis luas'],
        'cons' => ['Update singkat'],
    ]);

    expect($brand->fresh()->pros)->toBe(['Layar cerah', 'Servis luas'])
        ->and($brand->fresh()->cons)->toBe(['Update singkat']);
});

it('resolves route bindings by slug', function (): void {
    expect((new Brand)->getRouteKeyName())->toBe('slug');
});

it('rejects a duplicate slug', function (): void {
    Brand::factory()->create(['slug' => 'samsung']);

    expect(fn (): Brand => Brand::factory()->create(['slug' => 'samsung']))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=BrandModelTest
```

Expected: FAIL — `Class "App\Models\Brand" not found`.

- [ ] **Step 3: Generate the model, migration and factory**

```bash
php artisan make:model Brand --migration --factory --no-interaction
```

Rename the generated migration file to `2026_08_19_100000_create_brands_table.php` so it runs before phones.

- [ ] **Step 4: Write the migration**

Replace the body of `database/migrations/2026_08_19_100000_create_brands_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('blurb');
            $table->json('pros');
            $table->json('cons');
            $table->string('bestselling_range');
            $table->string('os_update_note');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
```

- [ ] **Step 5: Write the model**

Replace `app/Models/Brand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'blurb',
        'pros',
        'cons',
        'bestselling_range',
        'os_update_note',
    ];

    /** @return HasMany<Phone, $this> */
    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'pros' => 'array',
            'cons' => 'array',
        ];
    }
}
```

The `phones()` relation references `App\Models\Phone`, which Task 2 creates. The test for this task does not call `phones()`, so it passes before Phone exists.

- [ ] **Step 6: Write the factory**

Replace `database/factories/BrandFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'blurb' => fake()->paragraph(),
            'pros' => [fake()->sentence(), fake()->sentence()],
            'cons' => [fake()->sentence()],
            'bestselling_range' => 'Rp2–4 jt',
            'os_update_note' => '3 tahun',
        ];
    }
}
```

- [ ] **Step 7: Run the tests**

```bash
php artisan test --compact --filter=BrandModelTest
```

Expected: PASS, 3 tests.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_08_19_100000_create_brands_table.php app/Models/Brand.php database/factories/BrandFactory.php tests/Feature/BrandModelTest.php
git commit -m "feat: add brands table and model"
```

---

### Task 2: Phones

**Files:**
- Create: `database/migrations/2026_08_19_100100_create_phones_table.php`
- Create: `app/Models/Phone.php`
- Create: `database/factories/PhoneFactory.php`
- Test: `tests/Feature/PhoneModelTest.php`

**Interfaces:**
- Consumes: `App\Models\Brand`, `Database\Factories\BrandFactory`.
- Produces: `App\Models\Phone` with `brand(): BelongsTo`, `scopePublished(Builder $query): void`, `getRouteKeyName(): string` returning `'slug'`, `pros`/`cons` array casts, `released_at` date cast, `published_at` datetime cast, `nfc`/`ip68` boolean casts. `PhoneFactory` with an `unpublished()` state.

**Note:** `App\Models\Phone` is a different class from the existing `App\Data\Phone` DTO. Both exist during Part 1. Import carefully.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PhoneModelTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Phone;

it('belongs to a brand', function (): void {
    $brand = Brand::factory()->create(['name' => 'Samsung']);
    $phone = Phone::factory()->for($brand)->create();

    expect($phone->brand->name)->toBe('Samsung');
});

it('casts flags, dates and lists', function (): void {
    $phone = Phone::factory()->create([
        'nfc' => true,
        'ip68' => false,
        'released_at' => '2025-03-01',
        'pros' => ['Baterai awet'],
        'cons' => ['Cas lambat'],
    ])->fresh();

    expect($phone->nfc)->toBeTrue()
        ->and($phone->ip68)->toBeFalse()
        ->and($phone->released_at->format('Y-m-d'))->toBe('2025-03-01')
        ->and($phone->pros)->toBe(['Baterai awet'])
        ->and($phone->cons)->toBe(['Cas lambat']);
});

it('scopes to published phones only', function (): void {
    Phone::factory()->create(['slug' => 'tayang']);
    Phone::factory()->unpublished()->create(['slug' => 'draf']);

    expect(Phone::published()->pluck('slug')->all())->toBe(['tayang']);
});

it('resolves route bindings by slug', function (): void {
    expect((new Phone)->getRouteKeyName())->toBe('slug');
});

it('exposes a brand to its phones', function (): void {
    $brand = Brand::factory()->create();
    Phone::factory()->count(2)->for($brand)->create();

    expect($brand->phones)->toHaveCount(2);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PhoneModelTest
```

Expected: FAIL — `Class "App\Models\Phone" not found`.

- [ ] **Step 3: Generate the model, migration and factory**

```bash
php artisan make:model Phone --migration --factory --no-interaction
```

Rename the migration to `2026_08_19_100100_create_phones_table.php`.

- [ ] **Step 4: Write the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->string('tag')->nullable();
            $table->boolean('nfc')->default(false);
            $table->boolean('ip68')->default(false);
            $table->string('dimensions');
            $table->string('network');
            $table->string('released_label');
            $table->date('released_at');
            $table->json('pros');
            $table->json('cons');
            $table->unsignedTinyInteger('score_performa');
            $table->unsignedTinyInteger('score_layar');
            $table->unsignedTinyInteger('score_kamera');
            $table->unsignedTinyInteger('score_baterai');
            $table->unsignedTinyInteger('score_nilai');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phones');
    }
};
```

- [ ] **Step 5: Write the model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PhoneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Phone extends Model
{
    /** @use HasFactory<PhoneFactory> */
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'slug',
        'name',
        'price',
        'tag',
        'nfc',
        'ip68',
        'dimensions',
        'network',
        'released_label',
        'released_at',
        'pros',
        'cons',
        'score_performa',
        'score_layar',
        'score_kamera',
        'score_baterai',
        'score_nilai',
        'published_at',
    ];

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @param Builder<$this> $query */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'nfc' => 'boolean',
            'ip68' => 'boolean',
            'released_at' => 'date',
            'published_at' => 'datetime',
            'pros' => 'array',
            'cons' => 'array',
        ];
    }
}
```

- [ ] **Step 6: Write the factory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Phone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Phone>
 */
class PhoneFactory extends Factory
{
    protected $model = Phone::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->words(2, true),
            'price' => fake()->numberBetween(1_500_000, 15_000_000),
            'tag' => null,
            'nfc' => true,
            'ip68' => false,
            'dimensions' => '162,2 x 77,5 x 7,4 mm',
            'network' => '5G Dual SIM · Wi-Fi 6 · NFC',
            'released_label' => 'Maret 2025',
            'released_at' => '2025-03-01',
            'pros' => [fake()->sentence()],
            'cons' => [fake()->sentence()],
            'score_performa' => fake()->numberBetween(40, 99),
            'score_layar' => fake()->numberBetween(40, 99),
            'score_kamera' => fake()->numberBetween(40, 99),
            'score_baterai' => fake()->numberBetween(40, 99),
            'score_nilai' => fake()->numberBetween(40, 99),
            'published_at' => now(),
        ];
    }

    public function unpublished(): self
    {
        return $this->state(fn (): array => ['published_at' => null]);
    }
}
```

- [ ] **Step 7: Run the tests**

```bash
php artisan test --compact --filter="PhoneModelTest|BrandModelTest"
```

Expected: PASS, 8 tests.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_08_19_100100_create_phones_table.php app/Models/Phone.php database/factories/PhoneFactory.php tests/Feature/PhoneModelTest.php
git commit -m "feat: add phones table and model"
```

---

### Task 3: Spec definitions and phone specs

**Files:**
- Create: `database/migrations/2026_08_19_100200_create_spec_definitions_table.php`
- Create: `database/migrations/2026_08_19_100300_create_phone_specs_table.php`
- Create: `app/Models/SpecDefinition.php`, `app/Models/PhoneSpec.php`
- Create: `database/factories/SpecDefinitionFactory.php`, `database/factories/PhoneSpecFactory.php`
- Modify: `app/Models/Phone.php` — add the `specs()` relation
- Test: `tests/Feature/PhoneSpecModelTest.php`

**Interfaces:**
- Consumes: `App\Models\Phone`.
- Produces: `App\Models\SpecDefinition` (fillable: key, label, group, sort). `App\Models\PhoneSpec` (fillable: phone_id, spec_definition_id, display_value, comparable_value; `phone(): BelongsTo`, `definition(): BelongsTo`). `Phone::specs(): HasMany` returning `PhoneSpec`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PhoneSpecModelTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;

it('links a phone to a spec definition with both values', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create(['key' => 'baterai', 'label' => 'Kapasitas']);

    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.000 mAh',
        'comparable_value' => 5000,
    ]);

    $spec = $phone->specs()->with('definition')->sole();

    expect($spec->display_value)->toBe('5.000 mAh')
        ->and($spec->comparable_value)->toBe(5000)
        ->and($spec->definition->key)->toBe('baterai');
});

it('stores lower-is-better metrics negated', function (): void {
    $spec = PhoneSpec::factory()->create([
        'display_value' => '198 g',
        'comparable_value' => -198,
    ]);

    expect($spec->fresh()->comparable_value)->toBe(-198);
});

it('rejects the same spec twice on one phone', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create();

    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
    ]);

    expect(fn (): PhoneSpec => PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
    ]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

it('rejects a duplicate spec definition key', function (): void {
    SpecDefinition::factory()->create(['key' => 'chipset']);

    expect(fn (): SpecDefinition => SpecDefinition::factory()->create(['key' => 'chipset']))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

it('deletes specs when the phone is deleted', function (): void {
    $phone = Phone::factory()->create();
    PhoneSpec::factory()->create(['phone_id' => $phone->id]);

    $phone->delete();

    expect(PhoneSpec::count())->toBe(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PhoneSpecModelTest
```

Expected: FAIL — `Class "App\Models\SpecDefinition" not found`.

- [ ] **Step 3: Generate models, migrations and factories**

```bash
php artisan make:model SpecDefinition --migration --factory --no-interaction
php artisan make:model PhoneSpec --migration --factory --no-interaction
```

Rename the migrations to `2026_08_19_100200_create_spec_definitions_table.php` and `2026_08_19_100300_create_phone_specs_table.php`.

- [ ] **Step 4: Write both migrations**

`2026_08_19_100200_create_spec_definitions_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spec_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('group')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spec_definitions');
    }
};
```

`2026_08_19_100300_create_phone_specs_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_specs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('phone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spec_definition_id')->constrained()->cascadeOnDelete();
            $table->string('display_value');
            $table->bigInteger('comparable_value');
            $table->timestamps();

            $table->unique(['phone_id', 'spec_definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_specs');
    }
};
```

- [ ] **Step 5: Write both models**

`app/Models/SpecDefinition.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SpecDefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecDefinition extends Model
{
    /** @use HasFactory<SpecDefinitionFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'label',
        'group',
        'sort',
    ];
}
```

`app/Models/PhoneSpec.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PhoneSpecFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneSpec extends Model
{
    /** @use HasFactory<PhoneSpecFactory> */
    use HasFactory;

    protected $fillable = [
        'phone_id',
        'spec_definition_id',
        'display_value',
        'comparable_value',
    ];

    /** @return BelongsTo<Phone, $this> */
    public function phone(): BelongsTo
    {
        return $this->belongsTo(Phone::class);
    }

    /** @return BelongsTo<SpecDefinition, $this> */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(SpecDefinition::class, 'spec_definition_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'comparable_value' => 'integer',
        ];
    }
}
```

- [ ] **Step 6: Write both factories**

`database/factories/SpecDefinitionFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SpecDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpecDefinition>
 */
class SpecDefinitionFactory extends Factory
{
    protected $model = SpecDefinition::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'label' => fake()->word(),
            'group' => 'Performa',
            'sort' => 0,
        ];
    }
}
```

`database/factories/PhoneSpecFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhoneSpec>
 */
class PhoneSpecFactory extends Factory
{
    protected $model = PhoneSpec::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'phone_id' => Phone::factory(),
            'spec_definition_id' => SpecDefinition::factory(),
            'display_value' => '8 GB',
            'comparable_value' => 8,
        ];
    }
}
```

- [ ] **Step 7: Add the specs relation to Phone**

In `app/Models/Phone.php`, add the import `use Illuminate\Database\Eloquent\Relations\HasMany;` and this method directly after `brand()`:

```php
    /** @return HasMany<PhoneSpec, $this> */
    public function specs(): HasMany
    {
        return $this->hasMany(PhoneSpec::class);
    }
```

- [ ] **Step 8: Run the tests**

```bash
php artisan test --compact --filter=PhoneSpecModelTest
```

Expected: PASS, 5 tests.

- [ ] **Step 9: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_08_19_1002*.php database/migrations/2026_08_19_1003*.php app/Models/SpecDefinition.php app/Models/PhoneSpec.php app/Models/Phone.php database/factories/SpecDefinitionFactory.php database/factories/PhoneSpecFactory.php tests/Feature/PhoneSpecModelTest.php
git commit -m "feat: add spec definitions and phone specs"
```

---

### Task 4: Brand highlights

**Files:**
- Create: `database/migrations/2026_08_19_100400_create_brand_highlights_table.php`
- Modify: `app/Models/Brand.php` — add `highlightedPhones()`
- Test: `tests/Feature/BrandHighlightTest.php`

**Interfaces:**
- Consumes: `App\Models\Brand`, `App\Models\Phone`.
- Produces: `Brand::highlightedPhones(): BelongsToMany` — a `belongsToMany` through `brand_highlights` carrying a `sort` pivot column, ordered by `sort`. No dedicated model; this is a pivot table only.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/BrandHighlightTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Phone;

it('returns highlighted phones in sort order', function (): void {
    $brand = Brand::factory()->create();
    $first = Phone::factory()->create(['name' => 'Redmi Note 14 Pro 5G']);
    $second = Phone::factory()->create(['name' => 'Redmi Note 14']);

    $brand->highlightedPhones()->attach($second, ['sort' => 2]);
    $brand->highlightedPhones()->attach($first, ['sort' => 1]);

    expect($brand->highlightedPhones->pluck('name')->all())
        ->toBe(['Redmi Note 14 Pro 5G', 'Redmi Note 14']);
});

it('rejects highlighting the same phone twice for one brand', function (): void {
    $brand = Brand::factory()->create();
    $phone = Phone::factory()->create();

    $brand->highlightedPhones()->attach($phone, ['sort' => 1]);

    expect(fn () => $brand->highlightedPhones()->attach($phone, ['sort' => 2]))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=BrandHighlightTest
```

Expected: FAIL — `Call to undefined method App\Models\Brand::highlightedPhones()`.

- [ ] **Step 3: Create the migration**

```bash
php artisan make:migration create_brand_highlights_table --no-interaction
```

Rename it to `2026_08_19_100400_create_brand_highlights_table.php` and write:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_highlights', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phone_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['brand_id', 'phone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_highlights');
    }
};
```

- [ ] **Step 4: Add the relation to Brand**

In `app/Models/Brand.php`, add the import `use Illuminate\Database\Eloquent\Relations\BelongsToMany;` and this method after `phones()`:

```php
    /** @return BelongsToMany<Phone, $this> */
    public function highlightedPhones(): BelongsToMany
    {
        return $this->belongsToMany(Phone::class, 'brand_highlights')
            ->withPivot('sort')
            ->withTimestamps()
            ->orderBy('brand_highlights.sort');
    }
```

- [ ] **Step 5: Run the tests**

```bash
php artisan test --compact --filter=BrandHighlightTest
```

Expected: PASS, 2 tests.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_08_19_100400_create_brand_highlights_table.php app/Models/Brand.php tests/Feature/BrandHighlightTest.php
git commit -m "feat: add brand highlighted phones pivot"
```

---

### Task 5: Stores, listings and price history

**Files:**
- Create: `database/migrations/2026_08_19_100500_create_stores_table.php`
- Create: `database/migrations/2026_08_19_100600_create_phone_listings_table.php`
- Create: `database/migrations/2026_08_19_100700_create_price_snapshots_table.php`
- Create: `app/Models/Store.php`, `app/Models/PhoneListing.php`, `app/Models/PriceSnapshot.php`
- Create: `database/factories/StoreFactory.php`, `database/factories/PhoneListingFactory.php`
- Create: `app/Observers/PhoneListingObserver.php`
- Modify: `app/Models/Phone.php` — add `listings()`
- Test: `tests/Feature/PhoneListingModelTest.php`

**Interfaces:**
- Consumes: `App\Models\Phone`.
- Produces: `App\Models\Store` (fillable: name, slug, is_official; `is_official` boolean cast). `App\Models\PhoneListing` (fillable: phone_id, store_id, price, product_url, note, warranty_tag; `phone()`, `store()`, `snapshots(): HasMany`). `App\Models\PriceSnapshot` (fillable: phone_listing_id, price, recorded_at; `$timestamps = false`; `recorded_at` datetime cast). `Phone::listings(): HasMany`. `App\Observers\PhoneListingObserver` with `created()` and `updated()` writing snapshots.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PhoneListingModelTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Phone;
use App\Models\PhoneListing;
use App\Models\PriceSnapshot;
use App\Models\Store;

it('records a snapshot when a listing is created', function (): void {
    $listing = PhoneListing::factory()->create(['price' => 3_999_000]);

    expect($listing->snapshots)->toHaveCount(1)
        ->and($listing->snapshots->first()->price)->toBe(3_999_000);
});

it('records a new snapshot when the price changes', function (): void {
    $listing = PhoneListing::factory()->create(['price' => 3_999_000]);

    $listing->update(['price' => 3_799_000]);

    expect($listing->snapshots()->orderBy('id')->pluck('price')->all())
        ->toBe([3_999_000, 3_799_000]);
});

it('does not record a snapshot when a non-price field changes', function (): void {
    $listing = PhoneListing::factory()->create(['price' => 3_999_000]);

    $listing->update(['note' => 'Cicilan 0% 12 bulan']);

    expect($listing->snapshots()->count())->toBe(1);
});

it('rejects two listings for the same phone and store', function (): void {
    $phone = Phone::factory()->create();
    $store = Store::factory()->create();

    PhoneListing::factory()->create(['phone_id' => $phone->id, 'store_id' => $store->id]);

    expect(fn (): PhoneListing => PhoneListing::factory()->create([
        'phone_id' => $phone->id,
        'store_id' => $store->id,
    ]))->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

it('finds the cheapest listing for a phone', function (): void {
    $phone = Phone::factory()->create();
    PhoneListing::factory()->create(['phone_id' => $phone->id, 'price' => 5_499_000]);
    PhoneListing::factory()->create(['phone_id' => $phone->id, 'price' => 4_979_000]);

    expect($phone->listings()->min('price'))->toBe(4_979_000);
});

it('deletes snapshots when the listing is deleted', function (): void {
    $listing = PhoneListing::factory()->create();

    $listing->delete();

    expect(PriceSnapshot::count())->toBe(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PhoneListingModelTest
```

Expected: FAIL — `Class "App\Models\PhoneListing" not found`.

- [ ] **Step 3: Generate models, migrations, factories and the observer**

```bash
php artisan make:model Store --migration --factory --no-interaction
php artisan make:model PhoneListing --migration --factory --no-interaction
php artisan make:model PriceSnapshot --migration --no-interaction
php artisan make:observer PhoneListingObserver --model=PhoneListing --no-interaction
```

Rename the migrations to `2026_08_19_100500_create_stores_table.php`, `2026_08_19_100600_create_phone_listings_table.php`, `2026_08_19_100700_create_price_snapshots_table.php`.

- [ ] **Step 4: Write the three migrations**

`2026_08_19_100500_create_stores_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_official')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
```

`2026_08_19_100600_create_phone_listings_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_listings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('phone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price');
            $table->string('product_url')->nullable();
            $table->string('note')->nullable();
            $table->string('warranty_tag')->nullable();
            $table->timestamps();

            $table->unique(['phone_id', 'store_id']);
            $table->index(['phone_id', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_listings');
    }
};
```

`2026_08_19_100700_create_price_snapshots_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('phone_listing_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price');
            $table->timestamp('recorded_at');

            $table->index(['phone_listing_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_snapshots');
    }
};
```

- [ ] **Step 5: Write the three models**

`app/Models/Store.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_official',
    ];

    /** @return HasMany<PhoneListing, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(PhoneListing::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_official' => 'boolean',
        ];
    }
}
```

`app/Models/PhoneListing.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PhoneListingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PhoneListing extends Model
{
    /** @use HasFactory<PhoneListingFactory> */
    use HasFactory;

    protected $fillable = [
        'phone_id',
        'store_id',
        'price',
        'product_url',
        'note',
        'warranty_tag',
    ];

    /** @return BelongsTo<Phone, $this> */
    public function phone(): BelongsTo
    {
        return $this->belongsTo(Phone::class);
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return HasMany<PriceSnapshot, $this> */
    public function snapshots(): HasMany
    {
        return $this->hasMany(PriceSnapshot::class);
    }
}
```

`app/Models/PriceSnapshot.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'phone_listing_id',
        'price',
        'recorded_at',
    ];

    /** @return BelongsTo<PhoneListing, $this> */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(PhoneListing::class, 'phone_listing_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 6: Write the observer**

Replace `app/Observers/PhoneListingObserver.php`:

```php
<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PhoneListing;

/**
 * Keeps price_snapshots as the append-only history of every listing price,
 * so a future price tracker inherits a populated timeline.
 */
class PhoneListingObserver
{
    public function created(PhoneListing $listing): void
    {
        $this->recordPrice($listing);
    }

    public function updated(PhoneListing $listing): void
    {
        if (! $listing->wasChanged('price')) {
            return;
        }

        $this->recordPrice($listing);
    }

    private function recordPrice(PhoneListing $listing): void
    {
        $listing->snapshots()->create([
            'price' => $listing->price,
            'recorded_at' => now(),
        ]);
    }
}
```

- [ ] **Step 7: Register the observer**

Add the `ObservedBy` attribute to `app/Models/PhoneListing.php` — add the imports `use App\Observers\PhoneListingObserver;` and `use Illuminate\Database\Eloquent\Attributes\ObservedBy;`, then put the attribute directly above the class declaration:

```php
#[ObservedBy(PhoneListingObserver::class)]
class PhoneListing extends Model
```

- [ ] **Step 8: Write the two factories**

`database/factories/StoreFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Store>
 */
class StoreFactory extends Factory
{
    protected $model = Store::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_official' => true,
        ];
    }

    public function distributor(): self
    {
        return $this->state(fn (): array => ['is_official' => false]);
    }
}
```

`database/factories/PhoneListingFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Phone;
use App\Models\PhoneListing;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhoneListing>
 */
class PhoneListingFactory extends Factory
{
    protected $model = PhoneListing::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'phone_id' => Phone::factory(),
            'store_id' => Store::factory(),
            'price' => fake()->numberBetween(1_500_000, 15_000_000),
            'product_url' => 'https://example.test/produk',
            'note' => 'Toko resmi · gratis ongkir',
            'warranty_tag' => null,
        ];
    }
}
```

- [ ] **Step 9: Add the listings relation to Phone**

In `app/Models/Phone.php`, add after `specs()`:

```php
    /** @return HasMany<PhoneListing, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(PhoneListing::class);
    }
```

- [ ] **Step 10: Run the tests**

```bash
php artisan test --compact --filter=PhoneListingModelTest
```

Expected: PASS, 6 tests.

- [ ] **Step 11: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_08_19_1005*.php database/migrations/2026_08_19_1006*.php database/migrations/2026_08_19_1007*.php app/Models/Store.php app/Models/PhoneListing.php app/Models/PriceSnapshot.php app/Models/Phone.php app/Observers/PhoneListingObserver.php database/factories/StoreFactory.php database/factories/PhoneListingFactory.php tests/Feature/PhoneListingModelTest.php
git commit -m "feat: add stores, phone listings and price snapshot history"
```

---

### Task 6: Phone variants

**Files:**
- Create: `database/migrations/2026_08_19_100800_create_phone_variants_table.php`
- Create: `app/Models/PhoneVariant.php`
- Create: `database/factories/PhoneVariantFactory.php`
- Modify: `app/Models/Phone.php` — add `variants()` and `defaultVariant()`
- Test: `tests/Feature/PhoneVariantModelTest.php`

**Interfaces:**
- Consumes: `App\Models\Phone`.
- Produces: `App\Models\PhoneVariant` (fillable: phone_id, label, price, is_default, sort; `is_default` boolean cast; `phone(): BelongsTo`). `Phone::variants(): HasMany` ordered by `sort`, and `Phone::defaultVariant(): ?PhoneVariant`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PhoneVariantModelTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Phone;
use App\Models\PhoneVariant;

it('returns variants in sort order', function (): void {
    $phone = Phone::factory()->create();
    PhoneVariant::factory()->create(['phone_id' => $phone->id, 'label' => '12/512 GB', 'sort' => 3]);
    PhoneVariant::factory()->create(['phone_id' => $phone->id, 'label' => '8/256 GB', 'sort' => 1]);
    PhoneVariant::factory()->create(['phone_id' => $phone->id, 'label' => '12/256 GB', 'sort' => 2]);

    expect($phone->variants->pluck('label')->all())
        ->toBe(['8/256 GB', '12/256 GB', '12/512 GB']);
});

it('exposes the default variant', function (): void {
    $phone = Phone::factory()->create();
    PhoneVariant::factory()->create(['phone_id' => $phone->id, 'label' => '12/256 GB', 'is_default' => false, 'sort' => 2]);
    PhoneVariant::factory()->create(['phone_id' => $phone->id, 'label' => '8/256 GB', 'is_default' => true, 'sort' => 1]);

    expect($phone->defaultVariant()?->label)->toBe('8/256 GB');
});

it('returns null when a phone has no variants', function (): void {
    expect(Phone::factory()->create()->defaultVariant())->toBeNull();
});

it('deletes variants when the phone is deleted', function (): void {
    $phone = Phone::factory()->create();
    PhoneVariant::factory()->create(['phone_id' => $phone->id]);

    $phone->delete();

    expect(PhoneVariant::count())->toBe(0);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PhoneVariantModelTest
```

Expected: FAIL — `Class "App\Models\PhoneVariant" not found`.

- [ ] **Step 3: Generate the model, migration and factory**

```bash
php artisan make:model PhoneVariant --migration --factory --no-interaction
```

Rename the migration to `2026_08_19_100800_create_phone_variants_table.php`.

- [ ] **Step 4: Write the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('phone_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('price');
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['phone_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_variants');
    }
};
```

- [ ] **Step 5: Write the model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PhoneVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneVariant extends Model
{
    /** @use HasFactory<PhoneVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'phone_id',
        'label',
        'price',
        'is_default',
        'sort',
    ];

    /** @return BelongsTo<Phone, $this> */
    public function phone(): BelongsTo
    {
        return $this->belongsTo(Phone::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
```

- [ ] **Step 6: Write the factory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Phone;
use App\Models\PhoneVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhoneVariant>
 */
class PhoneVariantFactory extends Factory
{
    protected $model = PhoneVariant::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'phone_id' => Phone::factory(),
            'label' => '8/256 GB',
            'price' => fake()->numberBetween(1_500_000, 15_000_000),
            'is_default' => true,
            'sort' => 1,
        ];
    }
}
```

- [ ] **Step 7: Add the relations to Phone**

In `app/Models/Phone.php`, add after `listings()`:

```php
    /** @return HasMany<PhoneVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(PhoneVariant::class)->orderBy('sort');
    }

    public function defaultVariant(): ?PhoneVariant
    {
        return $this->variants->firstWhere('is_default', true);
    }
```

- [ ] **Step 8: Run the tests**

```bash
php artisan test --compact --filter=PhoneVariantModelTest
```

Expected: PASS, 4 tests.

- [ ] **Step 9: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_08_19_100800_create_phone_variants_table.php app/Models/PhoneVariant.php app/Models/Phone.php database/factories/PhoneVariantFactory.php tests/Feature/PhoneVariantModelTest.php
git commit -m "feat: add per-phone RAM and storage variants"
```

---

### Task 7: Articles

**Files:**
- Create: `app/Enums/ArticleCategory.php`
- Create: `database/migrations/2026_08_19_100900_create_articles_table.php`
- Create: `app/Models/Article.php`
- Create: `database/factories/ArticleFactory.php`
- Test: `tests/Feature/ArticleModelTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `App\Enums\ArticleCategory` — string-backed enum with cases `Rilis`, `Harga`, `Panduan`, `Ulasan` (values `'Rilis'`, `'Harga'`, `'Panduan'`, `'Ulasan'` — the labels the frontend filter chips already display) plus `public static function values(): array` returning the four strings. `App\Models\Article` (fillable: slug, title, category, excerpt, body, tags, read_minutes, published_at; `category` cast to the enum, `tags` to array, `published_at` to datetime; `scopePublished`, `getRouteKeyName()` → `'slug'`). `ArticleFactory` with a `draft()` state.

**Note:** `App\Models\Article` is a different class from the existing `App\Data\Article` DTO. Both exist during Part 1.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ArticleModelTest.php`:

```php
<?php

declare(strict_types=1);

use App\Enums\ArticleCategory;
use App\Models\Article;

it('casts the category to an enum', function (): void {
    $article = Article::factory()->create(['category' => ArticleCategory::Panduan]);

    expect($article->fresh()->category)->toBe(ArticleCategory::Panduan);
});

it('casts tags to an array', function (): void {
    $article = Article::factory()->create(['tags' => ['Xiaomi', 'Harga']]);

    expect($article->fresh()->tags)->toBe(['Xiaomi', 'Harga']);
});

it('scopes to published articles only', function (): void {
    Article::factory()->create(['slug' => 'tayang']);
    Article::factory()->draft()->create(['slug' => 'draf']);

    expect(Article::published()->pluck('slug')->all())->toBe(['tayang']);
});

it('resolves route bindings by slug', function (): void {
    expect((new Article)->getRouteKeyName())->toBe('slug');
});

it('lists the four frontend categories', function (): void {
    expect(ArticleCategory::values())->toBe(['Rilis', 'Harga', 'Panduan', 'Ulasan']);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=ArticleModelTest
```

Expected: FAIL — `Class "App\Enums\ArticleCategory" not found`.

- [ ] **Step 3: Write the enum**

```bash
php artisan make:enum ArticleCategory --string --no-interaction
```

Replace `app/Enums/ArticleCategory.php`:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum ArticleCategory: string
{
    case Rilis = 'Rilis';
    case Harga = 'Harga';
    case Panduan = 'Panduan';
    case Ulasan = 'Ulasan';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

If `make:enum` is unavailable in this Laravel version, create the file by hand.

- [ ] **Step 4: Generate the model, migration and factory**

```bash
php artisan make:model Article --migration --factory --no-interaction
```

Rename the migration to `2026_08_19_100900_create_articles_table.php`.

- [ ] **Step 5: Write the migration**

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('category');
            $table->text('excerpt');
            $table->longText('body');
            $table->json('tags');
            $table->unsignedTinyInteger('read_minutes');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['category', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
```

- [ ] **Step 6: Write the model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleCategory;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'category',
        'excerpt',
        'body',
        'tags',
        'read_minutes',
        'published_at',
    ];

    /** @param Builder<$this> $query */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => ArticleCategory::class,
            'tags' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
```

- [ ] **Step 7: Write the factory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ArticleCategory;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(4),
            'title' => fake()->sentence(),
            'category' => ArticleCategory::Rilis,
            'excerpt' => fake()->paragraph(),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'tags' => ['Xiaomi'],
            'read_minutes' => fake()->numberBetween(3, 12),
            'published_at' => now(),
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (): array => ['published_at' => null]);
    }
}
```

- [ ] **Step 8: Run the tests**

```bash
php artisan test --compact --filter=ArticleModelTest
```

Expected: PASS, 5 tests.

- [ ] **Step 9: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/ArticleCategory.php database/migrations/2026_08_19_100900_create_articles_table.php app/Models/Article.php database/factories/ArticleFactory.php tests/Feature/ArticleModelTest.php
git commit -m "feat: add articles table with rich text body"
```

---

### Task 8: FAQs and featured matchups

**Files:**
- Create: `app/Enums/FaqTopic.php`
- Create: `database/migrations/2026_08_19_101000_create_faqs_table.php`
- Create: `database/migrations/2026_08_19_101100_create_featured_matchups_table.php`
- Create: `app/Models/Faq.php`, `app/Models/FeaturedMatchup.php`
- Create: `database/factories/FaqFactory.php`, `database/factories/FeaturedMatchupFactory.php`
- Test: `tests/Feature/FaqModelTest.php`, `tests/Feature/FeaturedMatchupModelTest.php`

**Interfaces:**
- Consumes: `App\Models\Phone`.
- Produces: `App\Enums\FaqTopic` — string-backed enum with cases `KartuDuel` (value `'Kartu Duel'`), `Harga`, `Skor`, `Data`, plus `public static function values(): array`. `App\Models\Faq` (fillable: topic, question, answer, sort; `topic` cast to enum). `App\Models\FeaturedMatchup` (fillable: left_phone_id, right_phone_id, sort; `leftPhone()`, `rightPhone()` BelongsTo; `label(): string` returning `"{left name} vs {right name}"`).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/FaqModelTest.php`:

```php
<?php

declare(strict_types=1);

use App\Enums\FaqTopic;
use App\Models\Faq;

it('casts the topic to an enum', function (): void {
    $faq = Faq::factory()->create(['topic' => FaqTopic::KartuDuel]);

    expect($faq->fresh()->topic)->toBe(FaqTopic::KartuDuel);
});

it('lists the four frontend topics', function (): void {
    expect(FaqTopic::values())->toBe(['Kartu Duel', 'Harga', 'Skor', 'Data']);
});

it('filters by topic', function (): void {
    Faq::factory()->create(['topic' => FaqTopic::Harga, 'question' => 'Seberapa sering harga diperbarui?']);
    Faq::factory()->create(['topic' => FaqTopic::Skor, 'question' => 'Bagaimana skor dihitung?']);

    expect(Faq::where('topic', FaqTopic::Harga)->pluck('question')->all())
        ->toBe(['Seberapa sering harga diperbarui?']);
});
```

Create `tests/Feature/FeaturedMatchupModelTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\FeaturedMatchup;
use App\Models\Phone;

it('builds its label from both phone names', function (): void {
    $matchup = FeaturedMatchup::factory()->create([
        'left_phone_id' => Phone::factory()->create(['name' => 'Galaxy A56 5G'])->id,
        'right_phone_id' => Phone::factory()->create(['name' => 'Redmi Note 14 Pro 5G'])->id,
    ]);

    expect($matchup->label())->toBe('Galaxy A56 5G vs Redmi Note 14 Pro 5G');
});

it('deletes the matchup when either phone is deleted', function (): void {
    $left = Phone::factory()->create();
    FeaturedMatchup::factory()->create(['left_phone_id' => $left->id]);

    $left->delete();

    expect(FeaturedMatchup::count())->toBe(0);
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
php artisan test --compact --filter="FaqModelTest|FeaturedMatchupModelTest"
```

Expected: FAIL — `Class "App\Enums\FaqTopic" not found`.

- [ ] **Step 3: Write the enum**

Create `app/Enums/FaqTopic.php`:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum FaqTopic: string
{
    case KartuDuel = 'Kartu Duel';
    case Harga = 'Harga';
    case Skor = 'Skor';
    case Data = 'Data';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

- [ ] **Step 4: Generate the models, migrations and factories**

```bash
php artisan make:model Faq --migration --factory --no-interaction
php artisan make:model FeaturedMatchup --migration --factory --no-interaction
```

Rename the migrations to `2026_08_19_101000_create_faqs_table.php` and `2026_08_19_101100_create_featured_matchups_table.php`.

- [ ] **Step 5: Write both migrations**

`2026_08_19_101000_create_faqs_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table): void {
            $table->id();
            $table->string('topic');
            $table->string('question');
            $table->text('answer');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['topic', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
```

`2026_08_19_101100_create_featured_matchups_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_matchups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('left_phone_id')->constrained('phones')->cascadeOnDelete();
            $table->foreignId('right_phone_id')->constrained('phones')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['left_phone_id', 'right_phone_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_matchups');
    }
};
```

- [ ] **Step 6: Write both models**

`app/Models/Faq.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FaqTopic;
use Database\Factories\FaqFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    /** @use HasFactory<FaqFactory> */
    use HasFactory;

    protected $fillable = [
        'topic',
        'question',
        'answer',
        'sort',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'topic' => FaqTopic::class,
        ];
    }
}
```

`app/Models/FeaturedMatchup.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FeaturedMatchupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedMatchup extends Model
{
    /** @use HasFactory<FeaturedMatchupFactory> */
    use HasFactory;

    protected $fillable = [
        'left_phone_id',
        'right_phone_id',
        'sort',
    ];

    /** @return BelongsTo<Phone, $this> */
    public function leftPhone(): BelongsTo
    {
        return $this->belongsTo(Phone::class, 'left_phone_id');
    }

    /** @return BelongsTo<Phone, $this> */
    public function rightPhone(): BelongsTo
    {
        return $this->belongsTo(Phone::class, 'right_phone_id');
    }

    public function label(): string
    {
        return $this->leftPhone->name.' vs '.$this->rightPhone->name;
    }
}
```

- [ ] **Step 7: Write both factories**

`database/factories/FaqFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FaqTopic;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'topic' => FaqTopic::Harga,
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
            'sort' => 0,
        ];
    }
}
```

`database/factories/FeaturedMatchupFactory.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FeaturedMatchup;
use App\Models\Phone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeaturedMatchup>
 */
class FeaturedMatchupFactory extends Factory
{
    protected $model = FeaturedMatchup::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'left_phone_id' => Phone::factory(),
            'right_phone_id' => Phone::factory(),
            'sort' => 0,
        ];
    }
}
```

- [ ] **Step 8: Run the tests**

```bash
php artisan test --compact --filter="FaqModelTest|FeaturedMatchupModelTest"
```

Expected: PASS, 5 tests.

- [ ] **Step 9: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/FaqTopic.php database/migrations/2026_08_19_1010*.php database/migrations/2026_08_19_1011*.php app/Models/Faq.php app/Models/FeaturedMatchup.php database/factories/FaqFactory.php database/factories/FeaturedMatchupFactory.php tests/Feature/FaqModelTest.php tests/Feature/FeaturedMatchupModelTest.php
git commit -m "feat: add faqs and featured matchups"
```

---

### Task 9: Spec definition and store seeders

**Files:**
- Create: `database/seeders/SpecDefinitionSeeder.php`
- Create: `database/seeders/StoreSeeder.php`
- Test: `tests/Feature/CatalogSeederTest.php`

**Interfaces:**
- Consumes: `App\Models\SpecDefinition`, `App\Models\Store`.
- Produces: two idempotent seeders. `SpecDefinitionSeeder` creates the 19 spec definitions keyed exactly as `App\Support\PhoneCatalog` uses them. `StoreSeeder` creates the 4 stores named exactly as `App\Support\SellerPricing::detailed()` names them.

**Why these keys and names matter:** Task 10 looks specs up by key and stores up by name while porting `PhoneCatalog` and `SellerPricing`. A typo here surfaces as a missing row there.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CatalogSeederTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\SpecDefinition;
use App\Models\Store;
use Database\Seeders\SpecDefinitionSeeder;
use Database\Seeders\StoreSeeder;

it('seeds all nineteen spec definitions', function (): void {
    $this->seed(SpecDefinitionSeeder::class);

    expect(SpecDefinition::count())->toBe(19)
        ->and(SpecDefinition::where('key', 'berat')->value('label'))->toBe('Berat')
        ->and(SpecDefinition::where('key', 'osUpdate')->value('group'))->toBe('Bodi & Lainnya');
});

it('seeds spec definitions idempotently', function (): void {
    $this->seed(SpecDefinitionSeeder::class);
    $this->seed(SpecDefinitionSeeder::class);

    expect(SpecDefinition::count())->toBe(19);
});

it('seeds the four stores with the distributor marked unofficial', function (): void {
    $this->seed(StoreSeeder::class);

    expect(Store::count())->toBe(4)
        ->and(Store::where('name', 'Toko distributor')->sole()->is_official)->toBeFalse()
        ->and(Store::where('name', 'Shopee Mall')->sole()->is_official)->toBeTrue();
});

it('seeds stores idempotently', function (): void {
    $this->seed(StoreSeeder::class);
    $this->seed(StoreSeeder::class);

    expect(Store::count())->toBe(4);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=CatalogSeederTest
```

Expected: FAIL — `Class "Database\Seeders\SpecDefinitionSeeder" not found`.

- [ ] **Step 3: Generate the seeders**

```bash
php artisan make:seeder SpecDefinitionSeeder --no-interaction
php artisan make:seeder StoreSeeder --no-interaction
```

- [ ] **Step 4: Write SpecDefinitionSeeder**

The 19 keys come from `App\Support\PhoneCatalog`; the labels and groups come from `App\Livewire\PhoneDetailPage::SPEC_GROUPS` and `App\Actions\CompareDuel::FULL_GROUPS`. Replace `database/seeders/SpecDefinitionSeeder.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SpecDefinition;
use Illuminate\Database\Seeder;

class SpecDefinitionSeeder extends Seeder
{
    /**
     * Keys match App\Support\PhoneCatalog's spec array exactly; the loader in
     * PhoneSeeder resolves them by key, so renaming one breaks that seeder.
     *
     * @var list<array{key: string, label: string, group: string}>
     */
    private const array DEFINITIONS = [
        ['key' => 'chipset', 'label' => 'Chipset', 'group' => 'Performa'],
        ['key' => 'antutu', 'label' => 'Skor AnTuTu', 'group' => 'Performa'],
        ['key' => 'ram', 'label' => 'RAM', 'group' => 'Performa'],
        ['key' => 'storage', 'label' => 'Penyimpanan', 'group' => 'Performa'],
        ['key' => 'panel', 'label' => 'Panel', 'group' => 'Layar'],
        ['key' => 'nits', 'label' => 'Kecerahan', 'group' => 'Layar'],
        ['key' => 'layar', 'label' => 'Panel & Kecerahan', 'group' => 'Layar'],
        ['key' => 'refresh', 'label' => 'Refresh Rate', 'group' => 'Layar'],
        ['key' => 'resolusi', 'label' => 'Resolusi', 'group' => 'Layar'],
        ['key' => 'kamera', 'label' => 'Kamera Utama', 'group' => 'Kamera'],
        ['key' => 'ultrawide', 'label' => 'Ultrawide', 'group' => 'Kamera'],
        ['key' => 'selfie', 'label' => 'Kamera Depan', 'group' => 'Kamera'],
        ['key' => 'video', 'label' => 'Rekam Video', 'group' => 'Kamera'],
        ['key' => 'baterai', 'label' => 'Kapasitas', 'group' => 'Baterai & Daya'],
        ['key' => 'charge', 'label' => 'Pengisian Daya', 'group' => 'Baterai & Daya'],
        ['key' => 'ip', 'label' => 'IP Rating', 'group' => 'Bodi & Lainnya'],
        ['key' => 'os', 'label' => 'Software', 'group' => 'Bodi & Lainnya'],
        ['key' => 'osUpdate', 'label' => 'Dukungan Software', 'group' => 'Bodi & Lainnya'],
        ['key' => 'berat', 'label' => 'Berat', 'group' => 'Bodi & Lainnya'],
    ];

    public function run(): void
    {
        foreach (self::DEFINITIONS as $sort => $definition) {
            SpecDefinition::updateOrCreate(
                ['key' => $definition['key']],
                [
                    'label' => $definition['label'],
                    'group' => $definition['group'],
                    'sort' => $sort + 1,
                ],
            );
        }
    }
}
```

- [ ] **Step 5: Write StoreSeeder**

Names match `App\Support\SellerPricing::detailed()`. Replace `database/seeders/StoreSeeder.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StoreSeeder extends Seeder
{
    /**
     * Names match App\Support\SellerPricing::detailed(); PhoneSeeder resolves
     * stores by name when it ports today's listing prices.
     *
     * @var list<array{name: string, is_official: bool}>
     */
    private const array STORES = [
        ['name' => 'Shopee Mall', 'is_official' => true],
        ['name' => 'Tokopedia Official', 'is_official' => true],
        ['name' => 'TikTok Shop', 'is_official' => true],
        ['name' => 'Toko distributor', 'is_official' => false],
    ];

    public function run(): void
    {
        foreach (self::STORES as $store) {
            Store::updateOrCreate(
                ['name' => $store['name']],
                [
                    'slug' => Str::slug($store['name']),
                    'is_official' => $store['is_official'],
                ],
            );
        }
    }
}
```

- [ ] **Step 6: Run the tests**

```bash
php artisan test --compact --filter=CatalogSeederTest
```

Expected: PASS, 4 tests.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/seeders/SpecDefinitionSeeder.php database/seeders/StoreSeeder.php tests/Feature/CatalogSeederTest.php
git commit -m "feat: seed spec definitions and stores"
```

---

### Task 10: Brand and phone seeders

**Files:**
- Create: `database/seeders/BrandSeeder.php`
- Create: `database/seeders/PhoneSeeder.php`
- Create: `database/seeders/BrandHighlightSeeder.php`
- Test: `tests/Feature/PhoneSeederTest.php`

**Interfaces:**
- Consumes: `App\Support\BrandCatalog`, `App\Support\PhoneCatalog`, `App\Support\SellerPricing`, `App\Data\Phone`, `App\Data\Brand`, and every model from Tasks 1–6, plus `SpecDefinitionSeeder` and `StoreSeeder` from Task 9.
- Produces: three seeders that reproduce today's dummy data exactly.

**Key approach:** the seeders read the existing `App\Support\*` classes at runtime rather than duplicating their data. That makes transcription errors impossible. In Part 3, before the Support classes are deleted, their data gets inlined into these seeders.

**Brand stats mapping:** today's `BrandCatalog` stats array has four keys — `'Model aktif'`, `'Harga terendah'`, `'Rentang terlaris'`, `'Update OS'`. Only the last two are stored (`bestselling_range`, `os_update_note`); the first two become computed values in Part 3.

**Highlight-only phones:** `BrandCatalog` lists showcase models with no `PhoneCatalog` entry (e.g. Redmi 14C, iPhone 16 Pro). `BrandHighlightSeeder` creates a published `phones` row for each, parsing the `'Rp3.999.000'` string into an integer price, with empty specs and the model's spec string reused as the network field. A Filament editor fills in the rest later.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PhoneSeederTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Phone;
use App\Models\PhoneListing;
use App\Support\PhoneCatalog;
use App\Support\SellerPricing;
use Database\Seeders\BrandHighlightSeeder;
use Database\Seeders\BrandSeeder;
use Database\Seeders\PhoneSeeder;
use Database\Seeders\SpecDefinitionSeeder;
use Database\Seeders\StoreSeeder;

beforeEach(function (): void {
    $this->seed(SpecDefinitionSeeder::class);
    $this->seed(StoreSeeder::class);
    $this->seed(BrandSeeder::class);
    $this->seed(PhoneSeeder::class);
});

it('seeds the six brands with their pros and cons', function (): void {
    expect(Brand::count())->toBe(6)
        ->and(Brand::where('name', 'Samsung')->value('os_update_note'))->toBe('6–7 tahun')
        ->and(Brand::where('name', 'Xiaomi')->first()->pros)->toHaveCount(3);
});

it('seeds every catalogue phone with its slug and price intact', function (): void {
    expect(Phone::whereIn('slug', ['a56', 'note14pro', 'x7pro', 'r13pp', 'ip16', 's25'])->count())->toBe(6)
        ->and(Phone::where('slug', 'a56')->value('price'))->toBe(5_499_000)
        ->and(Phone::where('slug', 'note14pro')->value('tag'))->toBe('Terlaris');
});

it('gives every catalogue phone all nineteen specs', function (): void {
    $phone = Phone::where('slug', 'a56')->sole();

    expect($phone->specs()->count())->toBe(19);
});

it('ports spec display and comparable values verbatim', function (): void {
    $phone = Phone::where('slug', 'a56')->with('specs.definition')->sole();
    $berat = $phone->specs->first(fn ($spec): bool => $spec->definition->key === 'berat');
    $baterai = $phone->specs->first(fn ($spec): bool => $spec->definition->key === 'baterai');

    expect($berat->display_value)->toBe('198 g')
        ->and($berat->comparable_value)->toBe(-198)
        ->and($baterai->display_value)->toBe('5.000 mAh')
        ->and($baterai->comparable_value)->toBe(5000);
});

it('links each phone to its brand', function (): void {
    expect(Phone::where('slug', 'a56')->sole()->brand->name)->toBe('Samsung');
});

it('gives every catalogue phone four listings matching the old offsets', function (): void {
    $phone = Phone::where('slug', 'a56')->sole();
    $expected = SellerPricing::detailed(5_499_000);

    expect($phone->listings()->count())->toBe(4)
        ->and($phone->listings()->min('price'))->toBe(SellerPricing::lowestPrice(5_499_000));

    $distributor = $phone->listings()->whereRelation('store', 'name', 'Toko distributor')->sole();

    expect($distributor->warranty_tag)->toBe('Non-resmi')
        ->and($expected)->toHaveCount(4);
});

it('gives every catalogue phone three variants with one default', function (): void {
    $phone = Phone::where('slug', 'a56')->sole();

    expect($phone->variants)->toHaveCount(3)
        ->and($phone->defaultVariant()?->label)->toBe('8/256 GB')
        ->and($phone->defaultVariant()?->price)->toBe(5_499_000);
});

it('parses the release label into a sortable date', function (): void {
    expect(Phone::where('slug', 'a56')->sole()->released_at->format('Y-m'))->toBe('2025-03');
});

it('records a price snapshot for every seeded listing', function (): void {
    expect(PhoneListing::count())->toBe(count(PhoneCatalog::all()) * 4);

    $listing = PhoneListing::with('snapshots')->first();

    expect($listing->snapshots)->toHaveCount(1);
});

it('runs the phone seeder idempotently', function (): void {
    $this->seed(PhoneSeeder::class);

    expect(Phone::count())->toBe(count(PhoneCatalog::all()))
        ->and(PhoneListing::count())->toBe(count(PhoneCatalog::all()) * 4);
});

it('gives every brand four highlighted phones', function (): void {
    $this->seed(BrandHighlightSeeder::class);

    Brand::each(function (Brand $brand): void {
        expect($brand->highlightedPhones)->toHaveCount(4);
    });
});

it('creates phones for highlight models missing from the catalogue', function (): void {
    $this->seed(BrandHighlightSeeder::class);

    $phone = Phone::where('name', 'Redmi 14C')->sole();

    expect($phone->price)->toBe(1_399_000)
        ->and($phone->published_at)->not->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PhoneSeederTest
```

Expected: FAIL — `Class "Database\Seeders\BrandSeeder" not found`.

- [ ] **Step 3: Generate the seeders**

```bash
php artisan make:seeder BrandSeeder --no-interaction
php artisan make:seeder PhoneSeeder --no-interaction
php artisan make:seeder BrandHighlightSeeder --no-interaction
```

- [ ] **Step 4: Write BrandSeeder**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\Brand as BrandData;
use App\Models\Brand;
use App\Support\BrandCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Ports App\Support\BrandCatalog into the brands table.
 *
 * "Model aktif" and "Harga terendah" from the old stats array are not stored —
 * they become computed values over the phones relation in Part 3.
 */
class BrandSeeder extends Seeder
{
    public function run(): void
    {
        foreach (BrandCatalog::all() as $brand) {
            $this->seedBrand($brand);
        }
    }

    private function seedBrand(BrandData $brand): void
    {
        Brand::updateOrCreate(
            ['name' => $brand->name],
            [
                'slug' => Str::slug($brand->name),
                'blurb' => $brand->blurb,
                'pros' => $brand->pros,
                'cons' => $brand->cons,
                'bestselling_range' => $brand->stats['Rentang terlaris'],
                'os_update_note' => $brand->stats['Update OS'],
            ],
        );
    }
}
```

`App\Data\Brand` exposes exactly `name`, `blurb`, `stats`, `models`, `pros`, `cons` — the property names above are verified against it.

- [ ] **Step 5: Write PhoneSeeder**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\Phone as PhoneData;
use App\Models\Brand;
use App\Models\Phone;
use App\Models\SpecDefinition;
use App\Models\Store;
use App\Support\PhoneCatalog;
use App\Support\SellerPricing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Ports App\Support\PhoneCatalog, including the seller prices that
 * App\Support\SellerPricing derived from each phone's official price.
 */
class PhoneSeeder extends Seeder
{
    /** Indonesian month names as they appear in PhoneCatalog's "rilis" field. */
    private const array MONTHS = [
        'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
        'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
        'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12,
    ];

    public function run(): void
    {
        $brands = Brand::pluck('id', 'name');
        $definitions = SpecDefinition::pluck('id', 'key');
        $stores = Store::pluck('id', 'name');

        foreach (PhoneCatalog::all() as $data) {
            $phone = $this->seedPhone($data, (int) $brands[$data->brand]);

            $this->seedSpecs($phone, $data, $definitions->all());
            $this->seedVariants($phone, $data);
            $this->seedListings($phone, $data, $stores->all());
        }
    }

    private function seedPhone(PhoneData $data, int $brandId): Phone
    {
        return Phone::updateOrCreate(
            ['slug' => $data->id],
            [
                'brand_id' => $brandId,
                'name' => $data->name,
                'price' => $data->price,
                'tag' => $data->tag === '' ? null : $data->tag,
                'nfc' => $data->nfc,
                'ip68' => $data->ip68,
                'dimensions' => $data->dimensi,
                'network' => $data->jaringan,
                'released_label' => $data->rilis,
                'released_at' => $this->parseReleaseDate($data->rilis),
                'pros' => $data->pros,
                'cons' => $data->cons,
                'score_performa' => $data->scores['Performa'],
                'score_layar' => $data->scores['Layar'],
                'score_kamera' => $data->scores['Kamera'],
                'score_baterai' => $data->scores['Baterai'],
                'score_nilai' => $data->scores['Nilai per Rupiah'],
                'published_at' => now(),
            ],
        );
    }

    /** @param array<string, int> $definitions Spec key => spec_definitions.id */
    private function seedSpecs(Phone $phone, PhoneData $data, array $definitions): void
    {
        foreach ($data->specs as $key => $spec) {
            $phone->specs()->updateOrCreate(
                ['spec_definition_id' => $definitions[$key]],
                [
                    'display_value' => $spec->display,
                    'comparable_value' => $spec->comparable,
                ],
            );
        }
    }

    private function seedVariants(Phone $phone, PhoneData $data): void
    {
        foreach (SellerPricing::VARIANTS as $index => $variant) {
            $phone->variants()->updateOrCreate(
                ['label' => $variant['label']],
                [
                    'price' => $data->price + $variant['delta'],
                    'is_default' => $variant['delta'] === 0,
                    'sort' => $index + 1,
                ],
            );
        }
    }

    /** @param array<string, int> $stores Store name => stores.id */
    private function seedListings(Phone $phone, PhoneData $data, array $stores): void
    {
        foreach (SellerPricing::detailed($data->price) as $seller) {
            $phone->listings()->updateOrCreate(
                ['store_id' => $stores[$seller['store']]],
                [
                    'price' => $this->parseRupiah($seller['price']),
                    'product_url' => 'https://example.test/'.$phone->slug,
                    'note' => $seller['note'],
                    'warranty_tag' => $seller['tag'] === '' ? null : $seller['tag'],
                ],
            );
        }
    }

    /** "Maret 2025" becomes 2025-03-01. */
    private function parseReleaseDate(string $label): Carbon
    {
        [$month, $year] = explode(' ', $label);

        return Carbon::create((int) $year, self::MONTHS[$month], 1)->startOfDay();
    }

    /** "Rp5.499.000" becomes 5499000. */
    private function parseRupiah(string $formatted): int
    {
        return (int) preg_replace('/\D/', '', $formatted);
    }
}
```

Note `SellerPricing::detailed()` returns the price already formatted as a string, so it is parsed back to an integer. The "Termurah" tag from that array is deliberately dropped — cheapest is computed, per the spec.

- [ ] **Step 6: Write BrandHighlightSeeder**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Phone;
use App\Support\BrandCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Attaches each brand's four showcase models from App\Support\BrandCatalog.
 *
 * Models with no PhoneCatalog entry get a minimal published phone row — name,
 * price and the catalogue's spec blurb — so the Merek page can render its card.
 * An editor fills in specs, scores and copy later through the admin panel.
 */
class BrandHighlightSeeder extends Seeder
{
    public function run(): void
    {
        foreach (BrandCatalog::all() as $brandData) {
            $brand = Brand::where('name', $brandData->name)->sole();

            foreach ($brandData->models as $sort => $model) {
                $phone = $this->resolvePhone($brand, $model);

                $brand->highlightedPhones()->syncWithoutDetaching([
                    $phone->id => ['sort' => $sort + 1],
                ]);
            }
        }
    }

    /** @param array{name: string, price: string, spec: string} $model */
    private function resolvePhone(Brand $brand, array $model): Phone
    {
        $existing = Phone::where('name', $model['name'])->first();

        if ($existing instanceof Phone) {
            return $existing;
        }

        return Phone::create([
            'brand_id' => $brand->id,
            'slug' => Str::slug($model['name']),
            'name' => $model['name'],
            'price' => (int) preg_replace('/\D/', '', $model['price']),
            'tag' => null,
            'nfc' => false,
            'ip68' => false,
            'dimensions' => '—',
            'network' => $model['spec'],
            'released_label' => '—',
            'released_at' => now()->startOfDay(),
            'pros' => [],
            'cons' => [],
            'score_performa' => 0,
            'score_layar' => 0,
            'score_kamera' => 0,
            'score_baterai' => 0,
            'score_nilai' => 0,
            'published_at' => now(),
        ]);
    }
}
```

Note `syncWithoutDetaching` rather than `attach`, so re-running the seeder updates the `sort` pivot instead of hitting the unique constraint added in Task 4.

- [ ] **Step 7: Run the tests**

```bash
php artisan test --compact --filter=PhoneSeederTest
```

Expected: PASS, 12 tests.

If `it('runs the phone seeder idempotently')` fails on a unique-constraint error, the `updateOrCreate` keys are wrong — check that each is scoped to the parent relation (`$phone->specs()->updateOrCreate([...])`, not `PhoneSpec::updateOrCreate([...])`).

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/seeders/BrandSeeder.php database/seeders/PhoneSeeder.php database/seeders/BrandHighlightSeeder.php tests/Feature/PhoneSeederTest.php
git commit -m "feat: seed brands, phones, specs, variants and listings"
```

---

### Task 11: Content seeders and DatabaseSeeder wiring

**Files:**
- Create: `database/seeders/ArticleSeeder.php`
- Create: `database/seeders/FaqSeeder.php`
- Create: `database/seeders/FeaturedMatchupSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/ContentSeederTest.php`

**Interfaces:**
- Consumes: `App\Support\ArticleRepository`, `App\Support\FaqRepository`, `App\Livewire\HomePage::MATCHUPS`, and the models from Tasks 7–8.
- Produces: three seeders plus a `DatabaseSeeder` that runs all eight in dependency order.

**Article body conversion:** today's `App\Data\Article` splits prose across `body`, `quote` and `bodyRest`. The seeder concatenates them into one HTML string: each paragraph wrapped in `<p>`, the pull quote as `<blockquote>`. Articles carrying an `ArticleSpecTable` get it rendered as a plain `<table>` appended to the body — a stopgap until the Filament RichEditor custom block replaces it in Part 2.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ContentSeederTest.php`:

```php
<?php

declare(strict_types=1);

use App\Enums\ArticleCategory;
use App\Enums\FaqTopic;
use App\Models\Article;
use App\Models\Faq;
use App\Models\FeaturedMatchup;
use App\Support\ArticleRepository;
use App\Support\FaqRepository;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\FaqSeeder;

it('seeds every article with a published date', function (): void {
    $this->seed(ArticleSeeder::class);

    expect(Article::count())->toBe(count(ArticleRepository::all()))
        ->and(Article::whereNull('published_at')->count())->toBe(0);
});

it('preserves the original publish dates', function (): void {
    $this->seed(ArticleSeeder::class);

    $article = Article::where('slug', 'galaxy-a57-bocor-layar-120-hz')->sole();

    expect($article->published_at->format('Y-m-d'))->toBe('2026-08-12');
});

it('folds the split prose into one html body', function (): void {
    $this->seed(ArticleSeeder::class);

    $article = Article::first();

    expect($article->body)->toContain('<p>')
        ->and($article->category)->toBeInstanceOf(ArticleCategory::class);
});

it('seeds articles idempotently', function (): void {
    $this->seed(ArticleSeeder::class);
    $this->seed(ArticleSeeder::class);

    expect(Article::count())->toBe(count(ArticleRepository::all()));
});

it('seeds every faq under a valid topic', function (): void {
    $this->seed(FaqSeeder::class);

    expect(Faq::count())->toBe(count(FaqRepository::all()))
        ->and(Faq::where('topic', FaqTopic::KartuDuel)->count())->toBe(3);
});

it('seeds the full database in dependency order', function (): void {
    $this->seed();

    expect(FeaturedMatchup::count())->toBe(4)
        ->and(FeaturedMatchup::with(['leftPhone', 'rightPhone'])->first()->label())
        ->toBe('Galaxy A56 5G vs Redmi Note 14 Pro 5G');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=ContentSeederTest
```

Expected: FAIL — `Class "Database\Seeders\ArticleSeeder" not found`.

- [ ] **Step 3: Generate the seeders**

```bash
php artisan make:seeder ArticleSeeder --no-interaction
php artisan make:seeder FaqSeeder --no-interaction
php artisan make:seeder FeaturedMatchupSeeder --no-interaction
```

- [ ] **Step 4: Write ArticleSeeder**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Data\Article as ArticleData;
use App\Enums\ArticleCategory;
use App\Models\Article;
use App\Support\ArticleRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Ports App\Support\ArticleRepository, folding the design's three prose fields
 * (body, quote, bodyRest) into the single rich-text body the admin will edit.
 */
class ArticleSeeder extends Seeder
{
    /** Indonesian month names as they appear in the DTO's "dateLong" field. */
    private const array MONTHS = [
        'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
        'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
        'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12,
    ];

    public function run(): void
    {
        foreach (ArticleRepository::all() as $data) {
            Article::updateOrCreate(
                ['slug' => $data->slug],
                [
                    'title' => $data->title,
                    'category' => ArticleCategory::from($data->category),
                    'excerpt' => $data->excerpt,
                    'body' => $this->toHtml($data),
                    'tags' => $data->tags,
                    'read_minutes' => $data->readMinutes,
                    'published_at' => $this->parsePublishedAt($data->dateLong),
                ],
            );
        }
    }

    /** "12 Agustus 2026" becomes 2026-08-12. */
    private function parsePublishedAt(string $dateLong): Carbon
    {
        [$day, $month, $year] = explode(' ', $dateLong);

        return Carbon::create((int) $year, self::MONTHS[$month], (int) $day)->startOfDay();
    }

    private function toHtml(ArticleData $data): string
    {
        $html = $this->paragraphs($data->body);

        if ($data->quote !== '') {
            $html .= '<blockquote><p>'.e($data->quote).'</p></blockquote>';
        }

        $html .= $this->paragraphs($data->bodyRest);

        if ($data->specs !== null) {
            $html .= $this->specTable($data);
        }

        return $html;
    }

    /** @param list<string> $prose */
    private function paragraphs(array $prose): string
    {
        return collect($prose)
            ->filter(fn (string $paragraph): bool => trim($paragraph) !== '')
            ->map(fn (string $paragraph): string => '<p>'.e($paragraph).'</p>')
            ->implode('');
    }

    /** A stopgap until the Filament RichEditor comparison block lands in Part 2. */
    private function specTable(ArticleData $data): string
    {
        $table = $data->specs;

        $rows = collect($table->rows)
            ->map(fn (array $row): string => '<tr><th>'.e($row['label']).'</th>'
                .'<td>'.e($row['left']).'</td>'
                .'<td>'.e($row['right']).'</td></tr>')
            ->implode('');

        return '<h3>'.e($table->title).'</h3>'
            .'<table><thead><tr><th></th><th>'.e($table->left).'</th>'
            .'<th>'.e($table->right).'</th></tr></thead>'
            .'<tbody>'.$rows.'</tbody></table>';
    }
}
```

Verified against the DTOs: `App\Data\Article` exposes `body` and `bodyRest` as `list<string>` (paragraphs), `quote` as a plain string, and a nullable `specs` of type `App\Data\ArticleSpecTable` whose `rows` are `array{label, left, right, winner}`. The `winner` key is intentionally dropped — the stopgap HTML table does not mark winners.

- [ ] **Step 5: Write FaqSeeder**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FaqTopic;
use App\Models\Faq;
use App\Support\FaqRepository;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        foreach (FaqRepository::all() as $sort => $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                [
                    'topic' => FaqTopic::from($faq['topic']),
                    'answer' => $faq['answer'],
                    'sort' => $sort + 1,
                ],
            );
        }
    }
}
```

- [ ] **Step 6: Write FeaturedMatchupSeeder**

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Livewire\HomePage;
use App\Models\FeaturedMatchup;
use App\Models\Phone;
use Illuminate\Database\Seeder;

/**
 * Ports the hardcoded HomePage::MATCHUPS list. The label is derived from the
 * phone names, so only the two slugs and the ordering are stored.
 */
class FeaturedMatchupSeeder extends Seeder
{
    public function run(): void
    {
        $phones = Phone::pluck('id', 'slug');

        foreach (HomePage::MATCHUPS as $sort => $matchup) {
            FeaturedMatchup::updateOrCreate(
                [
                    'left_phone_id' => $phones[$matchup['left']],
                    'right_phone_id' => $phones[$matchup['right']],
                ],
                ['sort' => $sort + 1],
            );
        }
    }
}
```

- [ ] **Step 7: Wire up DatabaseSeeder**

Replace `database/seeders/DatabaseSeeder.php`:

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            SpecDefinitionSeeder::class,
            StoreSeeder::class,
            BrandSeeder::class,
            PhoneSeeder::class,
            BrandHighlightSeeder::class,
            ArticleSeeder::class,
            FaqSeeder::class,
            FeaturedMatchupSeeder::class,
        ]);
    }
}
```

The `WithoutModelEvents` trait is deliberately dropped — `PhoneListingObserver` must fire during seeding so price history is populated.

- [ ] **Step 8: Run the tests**

```bash
php artisan test --compact --filter=ContentSeederTest
```

Expected: PASS, 6 tests.

- [ ] **Step 9: Run the whole suite**

```bash
composer test
```

Expected: PASS, all tests green including the pre-existing Livewire page tests — they still read from the Support classes, which are untouched.

- [ ] **Step 10: Verify against a real database**

```bash
php artisan migrate:fresh --seed
```

Expected: all migrations run, all seeders complete without error. Then confirm the site still renders identically at `/`, `/katalog`, `/hp/a56`, `/berita`.

- [ ] **Step 11: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/seeders/ArticleSeeder.php database/seeders/FaqSeeder.php database/seeders/FeaturedMatchupSeeder.php database/seeders/DatabaseSeeder.php tests/Feature/ContentSeederTest.php
git commit -m "feat: seed articles, faqs and featured matchups"
```

---

### Task 12: Record the new data-layer rule

**Files:**
- Modify: `.ai/rules/support.md` (via the Boost `record-rule` MCP tool, not by hand)
- Create: a new rule covering `app/Models/**` and `database/**`

**Interfaces:**
- Consumes: everything built in Tasks 1–11.
- Produces: updated project rules so the next agent does not re-derive these decisions.

**Why:** `.ai/rules/support.md` currently states "There are no migrations for them and no Eloquent models — do not add any without agreeing to swap the layer over." That agreement has now happened, and the rule is actively misleading.

- [ ] **Step 1: Record the data-layer rule**

Use the Boost `record-rule` MCP tool with:

- **glob:** `app/Models/**`
- **title:** Catalogue data lives in Eloquent models
- **note:**

```
Phones, brands, specs, prices, articles, FAQs and featured matchups are database-backed (App\Models\*), seeded from the legacy App\Support\* classes by database/seeders/*.

Spec values live in phone_specs as display_value + comparable_value pairs against spec_definitions rows. comparable_value is always "higher is better" — inverted metrics are stored negated (berat 198 g is -198) and price is negated inside CompareDuel. Adding a spec means adding a spec_definitions row, not a migration.

Money is integer rupiah everywhere. The cheapest listing ("Termurah") is computed from phone_listings, never stored. PhoneListingObserver appends a price_snapshots row on create and on every price change, so seeding must not run WithoutModelEvents.
```

- [ ] **Step 2: Update the Support rule**

Use `record-rule` with:

- **glob:** `app/Support/**`
- **title:** Support catalogues are legacy read models pending removal
- **note:**

```
App\Support\{PhoneCatalog, BrandCatalog, ArticleRepository, FaqRepository, SellerPricing} are the original hardcoded demo data. They still serve every Livewire page, but the same data now also lives in the database (see app/Models/**), and the seeders read these classes to populate it.

Do not add new content here — add it through the database. These classes get retired in Part 3 when the Livewire pages switch to Eloquent; before deleting them, inline their data into database/seeders/* so seeding keeps working.
```

- [ ] **Step 3: Verify the rules landed**

```bash
grep -rl "comparable_value" .ai/rules
```

Expected: at least one matching rule file.

- [ ] **Step 4: Commit**

```bash
git add .ai/rules
git commit -m "docs: record data layer rules for the new catalogue models"
```

---

## Done criteria

- `composer test` is green.
- `php artisan migrate:fresh --seed` completes and the site renders identically at `/`, `/katalog`, `/hp/a56`, `/berita`, `/merek`, `/faq`.
- No file under `app/Livewire/`, `app/Support/`, `app/Data/`, or `resources/views/` was modified.
- `vendor/bin/pint --dirty --format agent` reports no changes.

## What comes next

**Part 2** installs Filament and builds resources over these models, including the RichEditor custom block for in-article phone comparisons. **Part 3** switches the Livewire pages to Eloquent, rebuilds the berita-detail view around rendered HTML, and retires the `App\Support\*` catalogues.
