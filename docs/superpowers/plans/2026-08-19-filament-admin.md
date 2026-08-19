# Bandingin Filament Admin Panel (Part 2) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give Bandingin an admin panel at `/admin` where every catalogue table from Part 1 can be edited, without touching a single line of the public site.

**Architecture:** One Filament v5 panel with 11 resources. Resources are produced with `make:filament-resource --generate`, which introspects the Part 1 schema and writes correct forms and tables, then edited where the spec calls for something specific. The phone form carries its 19 specs inline as a relationship repeater; variants and listings are relation managers.

**Tech Stack:** Filament v5.7.6, Laravel 13.25, Livewire 4.4, PHP 8.4, Pest 5.

## Global Constraints

- **Filament v5, not v4.** v4 targets Livewire 3; this project runs Livewire 4.4. Require exactly `filament/filament:"^5.0"`.
- Every new PHP file starts with `declare(strict_types=1);` — not in the Pint preset, so write it by hand.
- Run `vendor/bin/pint --dirty --format agent` after editing any PHP file, before every commit.
- **Do not modify** any file in `app/Livewire/`, `app/Support/`, `app/Data/`, or `resources/views/`. Task 14 adds an architecture test that enforces this.
- Money is integer rupiah. Never format money into a stored column.
- `comparable_value` is always "higher is better" — inverted metrics are negated (198 g is `-198`). Any form field exposing it must say so in helper text.
- Every Filament table showing a related value must eager-load it (`->modifyQueryUsing(...)`, `withCount(...)`). N+1 is a bug in this project, not a nit.
- Filament v5 form methods take `Filament\Schemas\Schema $schema` and return `Schema` — **not** `Form`. Generated stubs are the source of truth; if a signature here disagrees with a freshly generated stub, the stub wins.
- Filament resources generate into `app/Filament/Resources/<Plural>/` with `Pages/`, `Schemas/` and `Tables/` subdirectories. Edit the `Schemas/*Form.php` and `Tables/*Table.php` classes, not the resource shell.
- Panel tests live in `tests/Feature/Filament/`.

---

### Task 1: Install Filament and open the panel

**Files:**
- Create: `app/Providers/Filament/AdminPanelProvider.php` (generated)
- Modify: `composer.json`, `bootstrap/providers.php` (both by the installer)
- Test: `tests/Feature/Filament/PanelAccessTest.php`

**Interfaces:**
- Consumes: `App\Models\User` from the existing schema.
- Produces: a panel with id `admin` at path `/admin`, login enabled, registration **not** enabled, brand name `Bandingin`, and navigation groups `Katalog`, `Konten`, `Operasional`. Route names follow `filament.admin.*`; the dashboard is `filament.admin.pages.dashboard`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Filament/PanelAccessTest.php`:

```php
<?php

declare(strict_types=1);

use App\Models\User;

it('redirects guests away from the panel', function (): void {
    $this->get('/admin')->assertRedirect();
});

it('lets an authenticated user reach the panel', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/admin')
        ->assertSuccessful();
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PanelAccessTest
```

Expected: FAIL — `/admin` returns 404, so neither the redirect nor the success assertion holds.

- [ ] **Step 3: Install Filament**

```bash
composer require filament/filament:"^5.0" --no-interaction
```

Expected: resolves `filament/filament v5.7.6` plus its sibling packages. If Composer reports a conflict, **stop** — do not downgrade Laravel or Livewire to satisfy it; report the conflict instead.

- [ ] **Step 4: Install the panel**

```bash
php artisan filament:install --panels --no-interaction
```

When asked for a panel ID, accept the default `admin`. This writes `app/Providers/Filament/AdminPanelProvider.php` and registers it in `bootstrap/providers.php`.

- [ ] **Step 5: Configure the panel**

Open `app/Providers/Filament/AdminPanelProvider.php`. Add `declare(strict_types=1);` under the opening tag. In the `panel()` method chain, confirm `->login()` is present, confirm there is **no** `->registration()`, and add the brand name and navigation groups. The chain should include:

```php
            ->brandName('Bandingin')
            ->navigationGroups([
                'Katalog',
                'Konten',
                'Operasional',
            ])
```

Leave the rest of the generated chain (id, path, colors, discovery paths, middleware) exactly as generated.

- [ ] **Step 6: Run the tests**

```bash
php artisan test --compact --filter=PanelAccessTest
```

Expected: PASS, 2 tests.

- [ ] **Step 7: Create an admin account and check the panel by hand**

```bash
php artisan make:filament-user
```

Then open `http://bandingin.test/admin` and sign in. Confirm the dashboard loads and the brand reads "Bandingin".

- [ ] **Step 8: Run the whole suite**

```bash
composer test
```

Expected: PASS. Installing Filament must not break any of the 141 existing tests. If Livewire-related failures appear in the existing frontend tests, **stop and report** — that would mean Filament v5 and Livewire 4 disagree in this app, which the plan assumes they do not.

- [ ] **Step 9: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: install filament v5 admin panel"
```

---

### Task 2: Report status

**Files:**
- Create: `app/Enums/ReportStatus.php`
- Create: `database/migrations/2026_08_19_110000_add_status_to_reports_table.php`
- Modify: `app/Models/Report.php` — add `status` to `$fillable` and casts
- Modify: `database/factories/ReportFactory.php` — add a status state
- Test: `tests/Feature/ReportStatusTest.php`

**Interfaces:**
- Consumes: the existing `App\Models\Report` and `App\Enums\ReportType`.
- Produces: `App\Enums\ReportStatus` — string-backed, cases `Baru` (`'Baru'`), `Diproses` (`'Diproses'`), `Selesai` (`'Selesai'`), `Ditolak` (`'Ditolak'`), plus `public static function values(): array`. `Report::$status` cast to that enum, nullable. `ReportFactory::processed()` state.

**Why nullable:** existing report rows have no status, and `/lapor` never sets one. The frontend must keep working untouched.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ReportStatusTest.php`:

```php
<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Models\Report;

it('lists the four triage statuses', function (): void {
    expect(ReportStatus::values())->toBe(['Baru', 'Diproses', 'Selesai', 'Ditolak']);
});

it('defaults to no status when submitted from the site', function (): void {
    expect(Report::factory()->create()->status)->toBeNull();
});

it('casts an assigned status to the enum', function (): void {
    $report = Report::factory()->processed()->create();

    expect($report->fresh()->status)->toBe(ReportStatus::Diproses);
});

it('filters reports awaiting triage', function (): void {
    Report::factory()->create();
    Report::factory()->processed()->create();

    $awaiting = Report::whereNull('status')
        ->orWhere('status', ReportStatus::Baru)
        ->count();

    expect($awaiting)->toBe(1);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=ReportStatusTest
```

Expected: FAIL — `Class "App\Enums\ReportStatus" not found`.

- [ ] **Step 3: Write the enum**

Create `app/Enums/ReportStatus.php`:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportStatus: string
{
    case Baru = 'Baru';
    case Diproses = 'Diproses';
    case Selesai = 'Selesai';
    case Ditolak = 'Ditolak';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
```

- [ ] **Step 4: Write the migration**

```bash
php artisan make:migration add_status_to_reports_table --no-interaction
```

Rename it to `2026_08_19_110000_add_status_to_reports_table.php` and write:

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
        Schema::table('reports', function (Blueprint $table): void {
            $table->string('status')->nullable()->after('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
```

- [ ] **Step 5: Update the model**

In `app/Models/Report.php`, add `'status',` to `$fillable` directly after `'type',`, add the import `use App\Enums\ReportStatus;`, and add to the `casts()` array:

```php
            'status' => ReportStatus::class,
```

- [ ] **Step 6: Update the factory**

In `database/factories/ReportFactory.php`, add `'status' => null,` to the `definition()` array, add the import `use App\Enums\ReportStatus;`, and append this state method:

```php
    public function processed(): self
    {
        return $this->state(fn (): array => ['status' => ReportStatus::Diproses]);
    }
```

- [ ] **Step 7: Run the tests**

```bash
php artisan test --compact --filter="ReportStatusTest|LaporPageTest"
```

Expected: PASS. `LaporPageTest` is included deliberately — it proves the public report form still works with the new column.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add triage status to reports"
```

---

### Task 3: Simple catalogue resources — SpecDefinition and Store

**Files:**
- Create: `app/Filament/Resources/SpecDefinitions/**` (generated)
- Create: `app/Filament/Resources/Stores/**` (generated)
- Test: `tests/Feature/Filament/ResourcePagesTest.php`

**Interfaces:**
- Consumes: `App\Models\SpecDefinition`, `App\Models\Store`.
- Produces: `App\Filament\Resources\SpecDefinitions\SpecDefinitionResource` and `App\Filament\Resources\Stores\StoreResource`, both in navigation group `Katalog`. `ResourcePagesTest` establishes the dataset-driven page-render test that later tasks extend.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Filament/ResourcePagesTest.php`:

```php
<?php

declare(strict_types=1);

use App\Filament\Resources\SpecDefinitions\SpecDefinitionResource;
use App\Filament\Resources\Stores\StoreResource;
use App\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('renders the list page', function (string $resource): void {
    $this->get($resource::getUrl('index'))->assertSuccessful();
})->with([
    SpecDefinitionResource::class,
    StoreResource::class,
]);

it('renders the create page', function (string $resource): void {
    $this->get($resource::getUrl('create'))->assertSuccessful();
})->with([
    SpecDefinitionResource::class,
    StoreResource::class,
]);
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=ResourcePagesTest
```

Expected: FAIL — `Class "App\Filament\Resources\SpecDefinitions\SpecDefinitionResource" not found`.

- [ ] **Step 3: Generate both resources**

```bash
php artisan make:filament-resource SpecDefinition --generate --no-interaction
php artisan make:filament-resource Store --generate --no-interaction
```

`--generate` introspects the tables and writes form fields and table columns automatically.

- [ ] **Step 4: Read what was generated**

Open `app/Filament/Resources/SpecDefinitions/SpecDefinitionResource.php` and `app/Filament/Resources/SpecDefinitions/Schemas/SpecDefinitionForm.php`. Note the exact namespaces, imports and method signatures — later tasks in this plan follow the same shapes, and the generated stub is authoritative if anything here disagrees.

- [ ] **Step 5: Put both resources in the Katalog group**

In each of `SpecDefinitionResource.php` and `StoreResource.php`, add `declare(strict_types=1);` under the opening tag and add this property beside the existing `$model` / `$navigationIcon` properties:

```php
    protected static string|\UnitEnum|null $navigationGroup = 'Katalog';
```

If the generated class already declares `$navigationGroup` with a different type, match the generated type rather than this one.

- [ ] **Step 6: Protect the spec key from renaming**

In `app/Filament/Resources/SpecDefinitions/Schemas/SpecDefinitionForm.php`, find the `key` text input and chain:

```php
                    ->disabledOn('edit')
                    ->helperText('PhoneSeeder dan CompareDuel mencari spesifikasi lewat key ini — mengubahnya memutus keduanya.')
```

- [ ] **Step 7: Run the tests**

```bash
php artisan test --compact --filter=ResourcePagesTest
```

Expected: PASS, 4 tests (2 resources × 2 pages).

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add spec definition and store resources"
```

---

### Task 4: Brand resource

**Files:**
- Create: `app/Filament/Resources/Brands/**` (generated)
- Modify: `tests/Feature/Filament/ResourcePagesTest.php` — add `BrandResource` to both datasets
- Test: `tests/Feature/Filament/BrandResourceTest.php`

**Interfaces:**
- Consumes: `App\Models\Brand`, `Brand::phones()`, `Brand::highlightedPhones()`.
- Produces: `App\Filament\Resources\Brands\BrandResource` in group `Katalog`, with pros/cons repeaters and a highlighted-phones multi-select.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Filament/BrandResourceTest.php`:

```php
<?php

declare(strict_types=1);

use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Models\Brand;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('creates a brand with its pros and cons', function (): void {
    livewire(CreateBrand::class)
        ->fillForm([
            'name' => 'Vivo',
            'slug' => 'vivo',
            'blurb' => 'Merek dengan fokus kamera selfie.',
            'bestselling_range' => 'Rp3–5 jt',
            'os_update_note' => '3 tahun',
            'pros' => ['Kamera depan tajam'],
            'cons' => ['Harga jual kembali rendah'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $brand = Brand::where('slug', 'vivo')->sole();

    expect($brand->pros)->toBe(['Kamera depan tajam'])
        ->and($brand->cons)->toBe(['Harga jual kembali rendah']);
});
```

Also add `BrandResource::class` to both `->with([...])` datasets in `tests/Feature/Filament/ResourcePagesTest.php`, importing `App\Filament\Resources\Brands\BrandResource`.

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter="BrandResourceTest|ResourcePagesTest"
```

Expected: FAIL — `Class "App\Filament\Resources\Brands\Pages\CreateBrand" not found`.

- [ ] **Step 3: Generate the resource**

```bash
php artisan make:filament-resource Brand --generate --no-interaction
```

- [ ] **Step 4: Set the navigation group**

In `app/Filament/Resources/Brands/BrandResource.php`, add `declare(strict_types=1);` and the `$navigationGroup = 'Katalog';` property, matching the property type used by the generated stub.

- [ ] **Step 5: Replace the pros and cons fields with repeaters**

`--generate` renders json columns as a textarea or key-value field. Open `app/Filament/Resources/Brands/Schemas/BrandForm.php`, remove the generated `pros` and `cons` fields, and add these in their place (adding `use Filament\Forms\Components\Repeater;` and `use Filament\Forms\Components\TextInput;` if absent):

```php
                Repeater::make('pros')
                    ->label('Kelebihan')
                    ->simple(TextInput::make('item')->required())
                    ->defaultItems(1)
                    ->reorderable(),
                Repeater::make('cons')
                    ->label('Kekurangan')
                    ->simple(TextInput::make('item')->required())
                    ->defaultItems(1)
                    ->reorderable(),
```

`->simple()` stores each row as a bare string rather than an object, which matches the `array` cast holding a plain list.

- [ ] **Step 6: Add the highlighted-phones picker**

In the same form file, append (adding `use Filament\Forms\Components\Select;`):

```php
                Select::make('highlightedPhones')
                    ->label('Model unggulan')
                    ->relationship('highlightedPhones', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText('Empat model yang tampil di halaman merek.'),
```

- [ ] **Step 7: Eager-load counts in the table**

In `app/Filament/Resources/Brands/Tables/BrandsTable.php`, add a phones count column and eager-load it (adding `use Filament\Tables\Columns\TextColumn;` if absent):

```php
                TextColumn::make('phones_count')
                    ->label('Jumlah HP')
                    ->counts('phones')
                    ->sortable(),
```

- [ ] **Step 8: Run the tests**

```bash
php artisan test --compact --filter="BrandResourceTest|ResourcePagesTest"
```

Expected: PASS, 7 tests.

- [ ] **Step 9: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add brand resource"
```

---

### Task 5: Phone resource form and table

**Files:**
- Create: `app/Filament/Resources/Phones/**` (generated)
- Modify: `tests/Feature/Filament/ResourcePagesTest.php` — add `PhoneResource`
- Test: `tests/Feature/Filament/PhoneResourceTest.php`

**Interfaces:**
- Consumes: `App\Models\Phone` and its `brand`, `specs`, `variants`, `listings` relations.
- Produces: `App\Filament\Resources\Phones\PhoneResource` in group `Katalog`, with the form organised into the five sections named in the spec. Specs are added in Task 6, relation managers in Task 7.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Filament/PhoneResourceTest.php`:

```php
<?php

declare(strict_types=1);

use App\Filament\Resources\Phones\Pages\CreatePhone;
use App\Models\Brand;
use App\Models\Phone;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('creates a phone through the form', function (): void {
    $brand = Brand::factory()->create();

    livewire(CreatePhone::class)
        ->fillForm([
            'brand_id' => $brand->id,
            'slug' => 'a57',
            'name' => 'Galaxy A57 5G',
            'price' => 5_999_000,
            'nfc' => true,
            'ip68' => false,
            'dimensions' => '162,0 x 77,0 x 7,4 mm',
            'network' => '5G Dual SIM · Wi-Fi 6 · NFC',
            'released_label' => 'Maret 2026',
            'released_at' => '2026-03-01',
            'pros' => ['Layar cerah'],
            'cons' => ['Cas lambat'],
            'score_performa' => 75,
            'score_layar' => 82,
            'score_kamera' => 79,
            'score_baterai' => 76,
            'score_nilai' => 71,
            'published_at' => now(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Phone::where('slug', 'a57')->sole()->name)->toBe('Galaxy A57 5G');
});

it('rejects a phone without a brand', function (): void {
    livewire(CreatePhone::class)
        ->fillForm(['slug' => 'tanpa-merek', 'name' => 'Tanpa Merek'])
        ->call('create')
        ->assertHasFormErrors(['brand_id']);
});
```

Add `PhoneResource::class` to both datasets in `ResourcePagesTest.php`.

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PhoneResourceTest
```

Expected: FAIL — `Class "App\Filament\Resources\Phones\Pages\CreatePhone" not found`.

- [ ] **Step 3: Generate the resource**

```bash
php artisan make:filament-resource Phone --generate --no-interaction
```

- [ ] **Step 4: Set the navigation group and record title**

In `app/Filament/Resources/Phones/PhoneResource.php`, add `declare(strict_types=1);`, the `$navigationGroup = 'Katalog';` property, and:

```php
    protected static ?string $recordTitleAttribute = 'name';
```

- [ ] **Step 5: Organise the form into sections**

Open `app/Filament/Resources/Phones/Schemas/PhoneForm.php`. Keep every generated field, but wrap them in sections in this order (adding `use Filament\Schemas\Components\Section;`). The structure is:

```php
            ->components([
                Section::make('Identitas')
                    ->columns(2)
                    ->schema([
                        // brand_id, name, slug, price, tag, published_at
                    ]),
                Section::make('Bodi & jaringan')
                    ->columns(2)
                    ->schema([
                        // nfc, ip68, dimensions, network, released_label, released_at
                    ]),
                Section::make('Skor')
                    ->columns(5)
                    ->schema([
                        // score_performa, score_layar, score_kamera, score_baterai, score_nilai
                    ]),
                Section::make('Plus & minus')
                    ->columns(2)
                    ->schema([
                        // pros and cons repeaters
                    ]),
            ])
```

Move each generated field into the matching section. Replace the generated `pros` and `cons` fields with simple repeaters exactly as in Task 4 Step 5:

```php
                        Repeater::make('pros')
                            ->label('Kelebihan')
                            ->simple(TextInput::make('item')->required())
                            ->defaultItems(1)
                            ->reorderable(),
                        Repeater::make('cons')
                            ->label('Kekurangan')
                            ->simple(TextInput::make('item')->required())
                            ->defaultItems(1)
                            ->reorderable(),
```

Give each score field `->numeric()->minValue(0)->maxValue(100)->required()`.

- [ ] **Step 6: Make the phone table useful**

In `app/Filament/Resources/Phones/Tables/PhonesTable.php`, eager-load the brand and add a cheapest-listing column. Add to the table's configuration:

```php
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('brand'))
```

with `use Illuminate\Database\Eloquent\Builder;`, and add this column:

```php
                TextColumn::make('listings_min_price')
                    ->label('Termurah')
                    ->min('listings', 'price')
                    ->money('IDR', 100)
                    ->sortable(),
```

Add filters (with `use Filament\Tables\Filters\SelectFilter;` and `use Filament\Tables\Filters\TernaryFilter;`):

```php
            ->filters([
                SelectFilter::make('brand')->relationship('brand', 'name'),
                TernaryFilter::make('published_at')
                    ->label('Status tayang')
                    ->nullable()
                    ->trueLabel('Tayang')
                    ->falseLabel('Draf'),
                TernaryFilter::make('nfc'),
                TernaryFilter::make('ip68'),
            ])
```

If `->min()` is not available on `TextColumn` in this Filament build, drop that column and note it — it is a convenience, not a requirement, and the plan must not stall on it.

- [ ] **Step 7: Run the tests**

```bash
php artisan test --compact --filter="PhoneResourceTest|ResourcePagesTest"
```

Expected: PASS, 10 tests — 2 from `PhoneResourceTest`, plus 4 resources × 2 pages in `ResourcePagesTest` (SpecDefinition, Store, Brand, Phone).

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add phone resource"
```

---

### Task 6: Inline spec editing on the phone form

**Files:**
- Modify: `app/Filament/Resources/Phones/Schemas/PhoneForm.php` — add the specs section
- Test: `tests/Feature/Filament/PhoneSpecEditingTest.php`

**Interfaces:**
- Consumes: `Phone::specs()`, `PhoneSpec::definition()`, `App\Models\SpecDefinition`.
- Produces: a `Spesifikasi` section on the phone form editing all of a phone's `phone_specs` rows inline.

**Why inline rather than a relation manager:** every phone carries all 19 specs at all times, so they are part of the phone, not a variable-length child collection.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Filament/PhoneSpecEditingTest.php`:

```php
<?php

declare(strict_types=1);

use App\Filament\Resources\Phones\Pages\EditPhone;
use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('loads a phone existing specs into the form', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create(['key' => 'baterai', 'label' => 'Kapasitas']);
    PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.000 mAh',
        'comparable_value' => 5000,
    ]);

    livewire(EditPhone::class, ['record' => $phone->getRouteKey()])
        ->assertFormSet(fn (array $state): bool => collect($state['specs'] ?? [])
            ->contains(fn (array $row): bool => $row['display_value'] === '5.000 mAh'));
});

it('saves an edited spec value back to the database', function (): void {
    $phone = Phone::factory()->create();
    $definition = SpecDefinition::factory()->create(['key' => 'berat', 'label' => 'Berat']);
    $spec = PhoneSpec::factory()->create([
        'phone_id' => $phone->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '198 g',
        'comparable_value' => -198,
    ]);

    livewire(EditPhone::class, ['record' => $phone->getRouteKey()])
        ->fillForm(['specs' => [[
            'spec_definition_id' => $definition->id,
            'display_value' => '190 g',
            'comparable_value' => -190,
        ]]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($spec->fresh()->display_value)->toBe('190 g')
        ->and($spec->fresh()->comparable_value)->toBe(-190);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PhoneSpecEditingTest
```

Expected: FAIL — the form has no `specs` key, so `assertFormSet` finds nothing and the save leaves the spec unchanged.

- [ ] **Step 3: Add the specs section to the form**

In `app/Filament/Resources/Phones/Schemas/PhoneForm.php`, append a fifth section inside `->components([...])`:

```php
                Section::make('Spesifikasi')
                    ->description('Semua 19 baris spesifikasi untuk HP ini.')
                    ->schema([
                        Repeater::make('specs')
                            ->hiddenLabel()
                            ->relationship('specs')
                            ->schema([
                                Select::make('spec_definition_id')
                                    ->label('Spesifikasi')
                                    ->options(fn (): array => SpecDefinition::orderBy('sort')
                                        ->pluck('label', 'id')
                                        ->all())
                                    ->required()
                                    ->distinct()
                                    ->searchable(),
                                TextInput::make('display_value')
                                    ->label('Nilai tampil')
                                    ->required()
                                    ->helperText('Persis seperti yang dibaca pengunjung, misal "5.000 mAh".'),
                                TextInput::make('comparable_value')
                                    ->label('Nilai pembanding')
                                    ->numeric()
                                    ->required()
                                    ->helperText('Selalu "makin tinggi makin menang". Metrik terbalik ditulis negatif — 198 g ditulis -198.'),
                            ])
                            ->columns(3)
                            ->addActionLabel('Tambah spesifikasi')
                            ->defaultItems(0),
                    ]),
```

Add `use App\Models\SpecDefinition;` and `use Filament\Forms\Components\Select;` to the imports if absent.

The `comparable_value` helper text is required by the global constraints — do not shorten it away.

- [ ] **Step 4: Run the tests**

```bash
php artisan test --compact --filter=PhoneSpecEditingTest
```

Expected: PASS, 2 tests.

If `assertFormSet` with a closure is unsupported in this Filament build, replace the first test's assertion with a direct read of the component state:

```php
    $state = livewire(EditPhone::class, ['record' => $phone->getRouteKey()])->get('data');

    expect(collect($state['specs'])->pluck('display_value'))->toContain('5.000 mAh');
```

- [ ] **Step 5: Verify by hand**

Open a seeded phone in the panel — for example `http://bandingin.test/admin/phones` — and confirm the Spesifikasi section lists 19 rows with their values, and that saving preserves them.

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: edit phone specs inline on the phone form"
```

---

### Task 7: Variant and listing relation managers

**Files:**
- Create: `app/Filament/Resources/Phones/RelationManagers/VariantsRelationManager.php`
- Create: `app/Filament/Resources/Phones/RelationManagers/ListingsRelationManager.php`
- Modify: `app/Filament/Resources/Phones/PhoneResource.php` — register both in `getRelations()`
- Test: `tests/Feature/Filament/PhoneRelationManagersTest.php`

**Interfaces:**
- Consumes: `Phone::variants()`, `Phone::listings()`, `PhoneListing::snapshots()`, and the existing `App\Observers\PhoneListingObserver`.
- Produces: two relation managers registered on `PhoneResource::getRelations()`.

**Key behaviour:** editing a listing price here must write a `price_snapshots` row. No admin code does that — `PhoneListingObserver` already does, and the test proves the panel does not bypass it.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Filament/PhoneRelationManagersTest.php`:

```php
<?php

declare(strict_types=1);

use App\Filament\Resources\Phones\RelationManagers\ListingsRelationManager;
use App\Filament\Resources\Phones\RelationManagers\VariantsRelationManager;
use App\Models\Phone;
use App\Models\PhoneListing;
use App\Models\PhoneVariant;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('lists a phone variants', function (): void {
    $phone = Phone::factory()->create();
    $variant = PhoneVariant::factory()->create(['phone_id' => $phone->id, 'label' => '12/512 GB']);

    livewire(VariantsRelationManager::class, [
        'ownerRecord' => $phone,
        'pageClass' => App\Filament\Resources\Phones\Pages\EditPhone::class,
    ])->assertCanSeeTableRecords([$variant]);
});

it('lists a phone listings', function (): void {
    $phone = Phone::factory()->create();
    $listing = PhoneListing::factory()->create(['phone_id' => $phone->id]);

    livewire(ListingsRelationManager::class, [
        'ownerRecord' => $phone,
        'pageClass' => App\Filament\Resources\Phones\Pages\EditPhone::class,
    ])->assertCanSeeTableRecords([$listing]);
});

it('records a price snapshot when a listing price is edited in the panel', function (): void {
    $phone = Phone::factory()->create();
    $listing = PhoneListing::factory()->create(['phone_id' => $phone->id, 'price' => 4_999_000]);

    expect($listing->snapshots()->count())->toBe(1);

    $listing->update(['price' => 4_499_000]);

    expect($listing->fresh()->snapshots()->orderBy('id')->pluck('price')->all())
        ->toBe([4_999_000, 4_499_000]);
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PhoneRelationManagersTest
```

Expected: FAIL — `Class "App\Filament\Resources\Phones\RelationManagers\VariantsRelationManager" not found`.

- [ ] **Step 3: Generate both relation managers**

```bash
php artisan make:filament-relation-manager PhoneResource variants label --no-interaction
php artisan make:filament-relation-manager PhoneResource listings price --no-interaction
```

If the generator asks for the resource's fully-qualified name, give `App\Filament\Resources\Phones\PhoneResource`.

- [ ] **Step 4: Fill in the variants relation manager**

Open the generated `VariantsRelationManager.php`, add `declare(strict_types=1);`, and set its form fields to label, price, `is_default`, sort:

```php
                TextInput::make('label')
                    ->required()
                    ->helperText('Misal "12/256 GB".'),
                TextInput::make('price')
                    ->numeric()
                    ->required(),
                Toggle::make('is_default')
                    ->label('Varian utama'),
                TextInput::make('sort')
                    ->numeric()
                    ->default(0),
```

Set its table columns to `label`, `price` (with `->money('IDR', 100)`), `is_default` as an `IconColumn`, and make the table `->defaultSort('sort')->reorderable('sort')`.

- [ ] **Step 5: Fill in the listings relation manager**

Open the generated `ListingsRelationManager.php`, add `declare(strict_types=1);`, and set its form fields:

```php
                Select::make('store_id')
                    ->relationship('store', 'name')
                    ->required(),
                TextInput::make('price')
                    ->numeric()
                    ->required(),
                TextInput::make('product_url')
                    ->url()
                    ->maxLength(2000),
                TextInput::make('note')
                    ->maxLength(255),
                TextInput::make('warranty_tag')
                    ->maxLength(255),
```

Set its table columns to store name, price, note, warranty tag, plus a read-only snapshot count, and eager-load the store:

```php
                TextColumn::make('store.name')->label('Toko')->sortable(),
                TextColumn::make('price')->money('IDR', 100)->sortable(),
                TextColumn::make('note')->limit(40),
                TextColumn::make('warranty_tag')->label('Garansi')->badge(),
                TextColumn::make('snapshots_count')
                    ->label('Riwayat harga')
                    ->counts('snapshots'),
```

and on the table configuration:

```php
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('store'))
```

with `use Illuminate\Database\Eloquent\Builder;`.

- [ ] **Step 6: Register both on the resource**

In `app/Filament/Resources/Phones/PhoneResource.php`, set:

```php
    public static function getRelations(): array
    {
        return [
            RelationManagers\VariantsRelationManager::class,
            RelationManagers\ListingsRelationManager::class,
        ];
    }
```

adding `use App\Filament\Resources\Phones\RelationManagers;` if the generator did not.

- [ ] **Step 7: Run the tests**

```bash
php artisan test --compact --filter=PhoneRelationManagersTest
```

Expected: PASS, 3 tests.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add phone variant and listing relation managers"
```

---

### Task 8: Cross-phone listing resource

**Files:**
- Create: `app/Filament/Resources/PhoneListings/**` (generated)
- Modify: `tests/Feature/Filament/ResourcePagesTest.php` — add `PhoneListingResource`

**Interfaces:**
- Consumes: `App\Models\PhoneListing` and its `phone` / `store` relations.
- Produces: `App\Filament\Resources\PhoneListings\PhoneListingResource` in group `Katalog` — a flat view across all phones for price sweeps, complementing the per-phone relation manager from Task 7.

- [ ] **Step 1: Add the resource to the page test**

In `tests/Feature/Filament/ResourcePagesTest.php`, import `App\Filament\Resources\PhoneListings\PhoneListingResource` and add `PhoneListingResource::class` to both datasets.

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=ResourcePagesTest
```

Expected: FAIL — `Class "App\Filament\Resources\PhoneListings\PhoneListingResource" not found`.

- [ ] **Step 3: Generate the resource**

```bash
php artisan make:filament-resource PhoneListing --generate --no-interaction
```

- [ ] **Step 4: Set the navigation group**

In `PhoneListingResource.php`, add `declare(strict_types=1);` and `$navigationGroup = 'Katalog';`.

- [ ] **Step 5: Eager-load and filter the table**

In `app/Filament/Resources/PhoneListings/Tables/PhoneListingsTable.php`, add to the table configuration:

```php
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['phone', 'store']))
            ->filters([
                SelectFilter::make('store')->relationship('store', 'name'),
                SelectFilter::make('phone')->relationship('phone', 'name')->searchable(),
            ])
```

with `use Illuminate\Database\Eloquent\Builder;` and `use Filament\Tables\Filters\SelectFilter;`. Ensure the columns include `phone.name`, `store.name` and `price` formatted with `->money('IDR', 100)`.

- [ ] **Step 6: Run the tests**

```bash
php artisan test --compact --filter=ResourcePagesTest
```

Expected: PASS, 10 tests — 5 resources × 2 pages (SpecDefinition, Store, Brand, Phone, PhoneListing).

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add cross-phone listing resource"
```

---

### Task 9: Article resource

**Files:**
- Create: `app/Filament/Resources/Articles/**` (generated)
- Modify: `tests/Feature/Filament/ResourcePagesTest.php` — add `ArticleResource`
- Test: `tests/Feature/Filament/ArticleResourceTest.php`

**Interfaces:**
- Consumes: `App\Models\Article`, `App\Enums\ArticleCategory`.
- Produces: `App\Filament\Resources\Articles\ArticleResource` in group `Konten`, with a `RichEditor` body. The comparison custom block is attached in Task 10.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Filament/ArticleResourceTest.php`:

```php
<?php

declare(strict_types=1);

use App\Enums\ArticleCategory;
use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Models\Article;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('creates an article with html body and tags', function (): void {
    livewire(CreateArticle::class)
        ->fillForm([
            'slug' => 'panduan-beli-hp-2026',
            'title' => 'Panduan beli HP 2026',
            'category' => ArticleCategory::Panduan->value,
            'excerpt' => 'Cara memilih HP tanpa menyesal.',
            'body' => '<p>Paragraf pertama.</p>',
            'tags' => ['Panduan'],
            'read_minutes' => 6,
            'published_at' => now(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::where('slug', 'panduan-beli-hp-2026')->sole();

    expect($article->category)->toBe(ArticleCategory::Panduan)
        ->and($article->tags)->toBe(['Panduan'])
        ->and($article->body)->toContain('Paragraf pertama');
});
```

Add `ArticleResource::class` to both datasets in `ResourcePagesTest.php`.

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=ArticleResourceTest
```

Expected: FAIL — `Class "App\Filament\Resources\Articles\Pages\CreateArticle" not found`.

- [ ] **Step 3: Generate the resource**

```bash
php artisan make:filament-resource Article --generate --no-interaction
```

- [ ] **Step 4: Set the navigation group**

In `ArticleResource.php`, add `declare(strict_types=1);`, `$navigationGroup = 'Konten';`, and `$recordTitleAttribute = 'title';`.

- [ ] **Step 5: Replace the body field with a RichEditor**

In `app/Filament/Resources/Articles/Schemas/ArticleForm.php`, replace the generated `body` textarea with (adding `use Filament\Forms\Components\RichEditor;`):

```php
                RichEditor::make('body')
                    ->label('Isi artikel')
                    ->required()
                    ->columnSpanFull(),
```

Replace the generated `category` field with an enum-backed select (adding `use App\Enums\ArticleCategory;` and `use Filament\Forms\Components\Select;`):

```php
                Select::make('category')
                    ->label('Kategori')
                    ->options(ArticleCategory::class)
                    ->required(),
```

Replace the generated `tags` field with a simple repeater, exactly as in Task 4 Step 5 but named `tags` with label `Tag`.

- [ ] **Step 6: Filter the table**

In `app/Filament/Resources/Articles/Tables/ArticlesTable.php`, add:

```php
            ->filters([
                SelectFilter::make('category')->options(ArticleCategory::class),
                TernaryFilter::make('published_at')
                    ->label('Status')
                    ->nullable()
                    ->trueLabel('Tayang')
                    ->falseLabel('Draf'),
            ])
            ->defaultSort('published_at', 'desc')
```

with `use App\Enums\ArticleCategory;`, `use Filament\Tables\Filters\SelectFilter;` and `use Filament\Tables\Filters\TernaryFilter;`.

- [ ] **Step 7: Run the tests**

```bash
php artisan test --compact --filter="ArticleResourceTest|ResourcePagesTest"
```

Expected: PASS, 13 tests — 1 from `ArticleResourceTest`, plus 6 resources × 2 pages.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add article resource with rich editor"
```

---

### Task 10: Phone comparison rich-content block

**Files:**
- Create: `app/Filament/RichContent/PhoneComparisonBlock.php`
- Modify: `app/Filament/Resources/Articles/Schemas/ArticleForm.php` — attach the block
- Test: `tests/Feature/Filament/PhoneComparisonBlockTest.php`

**Interfaces:**
- Consumes: `App\Models\Phone`, `Phone::specs()`, `PhoneSpec::definition()`.
- Produces: `App\Filament\RichContent\PhoneComparisonBlock` extending `Filament\Forms\Components\RichEditor\RichContentCustomBlock`, with `getId(): string` returning `'phone_comparison'`, `getLabel(): string`, `configureEditorAction(Action $action): Action`, `toPreviewHtml(array $config): string`, and `toHtml(array $config, array $data): string`.

**Config shape:** `['leftPhoneId' => int|string, 'rightPhoneId' => int|string, 'specKeys' => list<string>]`.

**Part 3 dependency:** the stored body renders through `Filament\Forms\Components\RichEditor\RichContentRenderer` for blocks to expand. Part 3 does that in the Blade view; Part 2 only produces the block.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Filament/PhoneComparisonBlockTest.php`:

```php
<?php

declare(strict_types=1);

use App\Filament\RichContent\PhoneComparisonBlock;
use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;

it('identifies itself for the editor', function (): void {
    expect(PhoneComparisonBlock::getId())->toBe('phone_comparison')
        ->and(PhoneComparisonBlock::getLabel())->not->toBe('');
});

it('renders a table from live phone data', function (): void {
    $definition = SpecDefinition::factory()->create(['key' => 'baterai', 'label' => 'Kapasitas']);

    $left = Phone::factory()->create(['name' => 'Galaxy A56 5G']);
    $right = Phone::factory()->create(['name' => 'Redmi Note 14 Pro 5G']);

    PhoneSpec::factory()->create([
        'phone_id' => $left->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.000 mAh',
        'comparable_value' => 5000,
    ]);
    PhoneSpec::factory()->create([
        'phone_id' => $right->id,
        'spec_definition_id' => $definition->id,
        'display_value' => '5.500 mAh',
        'comparable_value' => 5500,
    ]);

    $html = PhoneComparisonBlock::toHtml([
        'leftPhoneId' => $left->id,
        'rightPhoneId' => $right->id,
        'specKeys' => ['baterai'],
    ], []);

    expect($html)->toContain('Galaxy A56 5G')
        ->and($html)->toContain('Redmi Note 14 Pro 5G')
        ->and($html)->toContain('5.000 mAh')
        ->and($html)->toContain('5.500 mAh')
        ->and($html)->toContain('Kapasitas');
});

it('renders nothing when a referenced phone is gone', function (): void {
    $html = PhoneComparisonBlock::toHtml([
        'leftPhoneId' => 9999,
        'rightPhoneId' => 9998,
        'specKeys' => ['baterai'],
    ], []);

    expect($html)->toBe('');
});

it('names both phones in the editor preview', function (): void {
    $left = Phone::factory()->create(['name' => 'iPhone 16']);
    $right = Phone::factory()->create(['name' => 'Galaxy S25']);

    $preview = PhoneComparisonBlock::toPreviewHtml([
        'leftPhoneId' => $left->id,
        'rightPhoneId' => $right->id,
        'specKeys' => [],
    ]);

    expect($preview)->toContain('iPhone 16')
        ->and($preview)->toContain('Galaxy S25');
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=PhoneComparisonBlockTest
```

Expected: FAIL — `Class "App\Filament\RichContent\PhoneComparisonBlock" not found`.

- [ ] **Step 3: Generate the block**

```bash
php artisan make:filament-rich-content-custom-block PhoneComparisonBlock --no-interaction
```

Note where the generator places the file. If it lands somewhere other than `app/Filament/RichContent/`, move it there and fix its namespace to `App\Filament\RichContent`.

- [ ] **Step 4: Implement the block**

Replace the generated file with:

```php
<?php

declare(strict_types=1);

namespace App\Filament\RichContent;

use App\Models\Phone;
use App\Models\SpecDefinition;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;

/**
 * An article-embedded duel table.
 *
 * Only the two phone ids and the chosen spec keys are stored, so the rendered
 * table always reflects current catalogue data rather than a frozen copy.
 */
class PhoneComparisonBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'phone_comparison';
    }

    public static function getLabel(): string
    {
        return 'Tabel perbandingan';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Tabel perbandingan')
            ->schema([
                Select::make('leftPhoneId')
                    ->label('HP kiri')
                    ->options(fn (): array => Phone::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->required(),
                Select::make('rightPhoneId')
                    ->label('HP kanan')
                    ->options(fn (): array => Phone::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable()
                    ->required(),
                Select::make('specKeys')
                    ->label('Baris spesifikasi')
                    ->options(fn (): array => SpecDefinition::orderBy('sort')->pluck('label', 'key')->all())
                    ->multiple()
                    ->default(['chipset', 'ram', 'layar', 'baterai', 'kamera'])
                    ->required(),
            ]);
    }

    /** @param  array<string, mixed>  $config */
    public static function toPreviewHtml(array $config): string
    {
        $phones = self::resolvePhones($config);

        if ($phones === null) {
            return '<p>Tabel perbandingan: HP tidak ditemukan.</p>';
        }

        [$left, $right] = $phones;

        return '<p><strong>Tabel perbandingan:</strong> '
            .e($left->name).' vs '.e($right->name).'</p>';
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        $phones = self::resolvePhones($config);

        if ($phones === null) {
            return '';
        }

        [$left, $right] = $phones;

        /** @var list<string> $keys */
        $keys = $config['specKeys'] ?? [];

        $rows = '';

        foreach ($keys as $key) {
            $leftSpec = self::specFor($left, $key);
            $rightSpec = self::specFor($right, $key);

            if ($leftSpec === null && $rightSpec === null) {
                continue;
            }

            $label = $leftSpec?->definition->label ?? $rightSpec?->definition->label ?? $key;

            $rows .= '<tr><th>'.e($label).'</th>'
                .'<td>'.e($leftSpec?->display_value ?? '—').'</td>'
                .'<td>'.e($rightSpec?->display_value ?? '—').'</td></tr>';
        }

        if ($rows === '') {
            return '';
        }

        return '<table class="phone-comparison">'
            .'<thead><tr><th></th><th>'.e($left->name).'</th><th>'.e($right->name).'</th></tr></thead>'
            .'<tbody>'.$rows.'</tbody></table>';
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: Phone, 1: Phone}|null
     */
    private static function resolvePhones(array $config): ?array
    {
        $left = Phone::with('specs.definition')->find($config['leftPhoneId'] ?? null);
        $right = Phone::with('specs.definition')->find($config['rightPhoneId'] ?? null);

        if (! $left instanceof Phone || ! $right instanceof Phone) {
            return null;
        }

        return [$left, $right];
    }

    private static function specFor(Phone $phone, string $key): mixed
    {
        return $phone->specs->first(
            fn ($spec): bool => $spec->definition?->key === $key,
        );
    }
}
```

The `with('specs.definition')` eager load is why this renders in two queries rather than one per spec row.

- [ ] **Step 5: Attach the block to the editor**

In `app/Filament/Resources/Articles/Schemas/ArticleForm.php`, chain onto the `RichEditor`:

```php
                    ->customBlocks([
                        PhoneComparisonBlock::class,
                    ])
```

adding `use App\Filament\RichContent\PhoneComparisonBlock;`.

- [ ] **Step 6: Run the tests**

```bash
php artisan test --compact --filter=PhoneComparisonBlockTest
```

Expected: PASS, 4 tests.

If `configureEditorAction` expects a different `Action` class than `Filament\Actions\Action`, use whichever the generated stub imported — the stub is authoritative.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add phone comparison rich content block"
```

---

### Task 11: FAQ and featured matchup resources

**Files:**
- Create: `app/Filament/Resources/Faqs/**` (generated)
- Create: `app/Filament/Resources/FeaturedMatchups/**` (generated)
- Modify: `tests/Feature/Filament/ResourcePagesTest.php` — add both

**Interfaces:**
- Consumes: `App\Models\Faq`, `App\Enums\FaqTopic`, `App\Models\FeaturedMatchup`.
- Produces: `App\Filament\Resources\Faqs\FaqResource` and `App\Filament\Resources\FeaturedMatchups\FeaturedMatchupResource`, both in group `Konten`.

- [ ] **Step 1: Add both to the page test**

In `tests/Feature/Filament/ResourcePagesTest.php`, import `App\Filament\Resources\Faqs\FaqResource` and `App\Filament\Resources\FeaturedMatchups\FeaturedMatchupResource`, and add both to the two datasets.

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=ResourcePagesTest
```

Expected: FAIL — `Class "App\Filament\Resources\Faqs\FaqResource" not found`.

- [ ] **Step 3: Generate both resources**

```bash
php artisan make:filament-resource Faq --generate --no-interaction
php artisan make:filament-resource FeaturedMatchup --generate --no-interaction
```

- [ ] **Step 4: Configure the FAQ resource**

In `FaqResource.php`, add `declare(strict_types=1);` and `$navigationGroup = 'Konten';`.

In `app/Filament/Resources/Faqs/Schemas/FaqForm.php`, replace the generated `topic` field with an enum select (adding `use App\Enums\FaqTopic;` and `use Filament\Forms\Components\Select;`):

```php
                Select::make('topic')
                    ->label('Topik')
                    ->options(FaqTopic::class)
                    ->required(),
```

In `app/Filament/Resources/Faqs/Tables/FaqsTable.php`, add:

```php
            ->defaultSort('sort')
            ->reorderable('sort')
            ->filters([
                SelectFilter::make('topic')->options(FaqTopic::class),
            ])
```

with `use App\Enums\FaqTopic;` and `use Filament\Tables\Filters\SelectFilter;`.

- [ ] **Step 5: Configure the featured matchup resource**

In `FeaturedMatchupResource.php`, add `declare(strict_types=1);` and `$navigationGroup = 'Konten';`.

In `app/Filament/Resources/FeaturedMatchups/Schemas/FeaturedMatchupForm.php`, make both phone fields searchable selects (adding `use Filament\Forms\Components\Select;`):

```php
                Select::make('left_phone_id')
                    ->label('HP kiri')
                    ->relationship('leftPhone', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('right_phone_id')
                    ->label('HP kanan')
                    ->relationship('rightPhone', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
```

In `app/Filament/Resources/FeaturedMatchups/Tables/FeaturedMatchupsTable.php`, eager-load both phones and show the derived label:

```php
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['leftPhone', 'rightPhone']))
            ->defaultSort('sort')
            ->reorderable('sort')
```

with `use Illuminate\Database\Eloquent\Builder;`, and ensure the columns include `leftPhone.name` and `rightPhone.name`.

- [ ] **Step 6: Run the tests**

```bash
php artisan test --compact --filter=ResourcePagesTest
```

Expected: PASS, 16 tests — 8 resources × 2 pages (SpecDefinition, Store, Brand, Phone, PhoneListing, Article, Faq, FeaturedMatchup). The three operational resources arrive in Task 12.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add faq and featured matchup resources"
```

---

### Task 12: Operational resources — Report, NewsletterSubscriber, User

**Files:**
- Create: `app/Filament/Resources/Reports/**` (generated)
- Create: `app/Filament/Resources/NewsletterSubscribers/**` (generated)
- Create: `app/Filament/Resources/Users/**` (generated)
- Modify: `tests/Feature/Filament/ResourcePagesTest.php`
- Test: `tests/Feature/Filament/ReportResourceTest.php`

**Interfaces:**
- Consumes: `App\Models\Report`, `App\Enums\ReportType`, `App\Enums\ReportStatus` (Task 2), `App\Models\NewsletterSubscriber`, `App\Models\User`.
- Produces: three resources in group `Operasional`. `ReportResource` disables creation; `NewsletterSubscriberResource` disables create and edit.

**Note on the page test:** `ReportResource` and `NewsletterSubscriberResource` have no create page, so they belong only in the index dataset, not the create dataset.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Filament/ReportResourceTest.php`:

```php
<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Models\Report;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('filters reports by status', function (): void {
    $untriaged = Report::factory()->create();
    $processed = Report::factory()->processed()->create();

    livewire(ListReports::class)
        ->assertCanSeeTableRecords([$untriaged, $processed])
        ->filterTable('status', ReportStatus::Diproses->value)
        ->assertCanSeeTableRecords([$processed])
        ->assertCanNotSeeTableRecords([$untriaged]);
});

it('does not offer report creation', function (): void {
    expect(App\Filament\Resources\Reports\ReportResource::canCreate())->toBeFalse();
});
```

In `ResourcePagesTest.php`, import all three resources. Add `ReportResource::class`, `NewsletterSubscriberResource::class` and `UserResource::class` to the **index** dataset, but add only `UserResource::class` to the **create** dataset.

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter="ReportResourceTest|ResourcePagesTest"
```

Expected: FAIL — `Class "App\Filament\Resources\Reports\Pages\ListReports" not found`.

- [ ] **Step 3: Generate all three resources**

```bash
php artisan make:filament-resource Report --generate --no-interaction
php artisan make:filament-resource NewsletterSubscriber --generate --no-interaction
php artisan make:filament-resource User --generate --no-interaction
```

- [ ] **Step 4: Configure the report resource**

In `ReportResource.php`, add `declare(strict_types=1);`, `$navigationGroup = 'Operasional';`, and:

```php
    public static function canCreate(): bool
    {
        return false;
    }
```

Delete `app/Filament/Resources/Reports/Pages/CreateReport.php` and remove its entry from the resource's `getPages()` array.

In `app/Filament/Resources/Reports/Tables/ReportsTable.php`, add status and type filters and a quick status action:

```php
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')->options(ReportType::class),
                SelectFilter::make('status')->options(ReportStatus::class),
            ])
```

with `use App\Enums\ReportStatus;`, `use App\Enums\ReportType;` and `use Filament\Tables\Filters\SelectFilter;`. Make sure the `type` and `status` columns render as badges.

In `app/Filament/Resources/Reports/Schemas/ReportForm.php`, make every field except `status` `->disabled()` — reports are submitted by visitors and the admin only triages them — and make `status` a select over `ReportStatus::class`.

- [ ] **Step 5: Configure the newsletter subscriber resource**

In `NewsletterSubscriberResource.php`, add `declare(strict_types=1);`, `$navigationGroup = 'Operasional';`, and:

```php
    public static function canCreate(): bool
    {
        return false;
    }
```

Delete its `Pages/CreateNewsletterSubscriber.php` and `Pages/EditNewsletterSubscriber.php`, and remove both from `getPages()`, leaving only the index page.

- [ ] **Step 6: Configure the user resource**

In `UserResource.php`, add `declare(strict_types=1);` and `$navigationGroup = 'Operasional';`.

In `app/Filament/Resources/Users/Schemas/UserForm.php`, make the password field hash on save and be optional when editing (adding `use Illuminate\Support\Facades\Hash;`):

```php
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
```

- [ ] **Step 7: Run the tests**

```bash
php artisan test --compact --filter="ReportResourceTest|ResourcePagesTest"
```

Expected: PASS. The index dataset now covers all 11 resources; the create dataset covers the 9 that allow creation.

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add report, subscriber and user resources"
```

---

### Task 13: Dashboard widgets

**Files:**
- Create: `app/Filament/Widgets/CatalogStatsWidget.php`
- Create: `app/Filament/Widgets/LatestReportsWidget.php`
- Test: `tests/Feature/Filament/DashboardTest.php`

**Interfaces:**
- Consumes: `Phone::published()`, `App\Models\Brand`, `App\Models\Report`, `App\Enums\ReportStatus`, `App\Models\NewsletterSubscriber`.
- Produces: two widgets auto-discovered from `app/Filament/Widgets` by the panel provider.

**Deliberately no charts:** `price_snapshots` holds one row per listing today, so a trend line would render flat and imply a tracker exists.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Filament/DashboardTest.php`:

```php
<?php

declare(strict_types=1);

use App\Filament\Widgets\CatalogStatsWidget;
use App\Models\Brand;
use App\Models\Phone;
use App\Models\Report;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('counts published phones, brands and untriaged reports', function (): void {
    Brand::factory()->count(2)->create();
    Phone::factory()->count(3)->create();
    Phone::factory()->unpublished()->create();
    Report::factory()->count(2)->create();
    Report::factory()->processed()->create();

    livewire(CatalogStatsWidget::class)->assertSuccessful();

    expect(Phone::published()->count())->toBe(3)
        ->and(Report::whereNull('status')->count())->toBe(2);
});

it('renders the dashboard with its widgets', function (): void {
    $this->get('/admin')->assertSuccessful();
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
php artisan test --compact --filter=DashboardTest
```

Expected: FAIL — `Class "App\Filament\Widgets\CatalogStatsWidget" not found`.

- [ ] **Step 3: Generate the widgets**

```bash
php artisan make:filament-widget CatalogStatsWidget --stats-overview --no-interaction
php artisan make:filament-widget LatestReportsWidget --table --no-interaction
```

- [ ] **Step 4: Implement the stats widget**

Replace the generated `app/Filament/Widgets/CatalogStatsWidget.php` body. Keep whatever base class and `getStats()` signature the generator produced, and fill it with:

```php
        return [
            Stat::make('HP tayang', (string) Phone::published()->count()),
            Stat::make('Merek', (string) Brand::count()),
            Stat::make('Laporan belum ditangani', (string) Report::query()
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('status')
                    ->orWhere('status', ReportStatus::Baru))
                ->count()),
            Stat::make('Pelanggan newsletter', (string) NewsletterSubscriber::count()),
        ];
```

with imports for `App\Enums\ReportStatus`, `App\Models\Brand`, `App\Models\NewsletterSubscriber`, `App\Models\Phone`, `App\Models\Report` and `Illuminate\Database\Eloquent\Builder`. Add `declare(strict_types=1);`.

- [ ] **Step 5: Implement the latest-reports widget**

In `app/Filament/Widgets/LatestReportsWidget.php`, add `declare(strict_types=1);` and configure its table to show the five newest reports:

```php
            ->query(Report::query()->latest()->limit(5))
            ->columns([
                TextColumn::make('type')->badge(),
                TextColumn::make('subject')->limit(40),
                TextColumn::make('status')->badge()->placeholder('Baru'),
                TextColumn::make('created_at')->dateTime()->label('Masuk'),
            ])
```

with `use App\Models\Report;` and `use Filament\Tables\Columns\TextColumn;`. Set `protected static ?string $heading = 'Laporan terbaru';`.

- [ ] **Step 6: Run the tests**

```bash
php artisan test --compact --filter=DashboardTest
```

Expected: PASS, 2 tests.

- [ ] **Step 7: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add -A
git commit -m "feat: add dashboard stats and latest reports widgets"
```

---

### Task 14: Enforce the frontend boundary and verify the whole panel

**Files:**
- Create: `tests/Feature/Filament/AdminBoundaryTest.php`
- Test: the full suite

**Interfaces:**
- Consumes: everything built in Tasks 1–13.
- Produces: an architecture test that fails if admin code leaks into the public site.

**Why this exists:** Part 2's defining constraint is that the public site does not change. A test enforces that mechanically, so Part 3 starts from a known-clean boundary.

- [ ] **Step 1: Write the boundary test**

Create `tests/Feature/Filament/AdminBoundaryTest.php`:

```php
<?php

declare(strict_types=1);

arch('the public site does not depend on filament')
    ->expect('App\Livewire')
    ->not->toUse('App\Filament');

arch('the support catalogues do not depend on filament')
    ->expect('App\Support')
    ->not->toUse('App\Filament');

arch('admin code does not reach into livewire pages')
    ->expect('App\Filament')
    ->not->toUse('App\Livewire');
```

- [ ] **Step 2: Run the boundary test**

```bash
php artisan test --compact --filter=AdminBoundaryTest
```

Expected: PASS, 3 tests. If the third fails because `FeaturedMatchupSeeder` reads `HomePage::MATCHUPS`, note that the seeder lives in `Database\Seeders`, not `App\Filament`, so it does not violate this rule. If any admin class genuinely imports a Livewire page, remove that import — it is a real leak.

- [ ] **Step 3: Confirm the public site is untouched**

```bash
git diff --stat main -- app/Livewire app/Support app/Data resources/views
```

Expected: empty output. Any diff here is a violation of the plan's core constraint and must be reverted.

- [ ] **Step 4: Run the whole suite**

```bash
composer test
```

Expected: PASS, all tests green — the 141 from Part 1 plus everything added here.

- [ ] **Step 5: Verify the panel by hand against seeded data**

```bash
php artisan migrate:fresh --seed
```

Then sign in at `http://bandingin.test/admin` (recreate the user with `php artisan make:filament-user` after the fresh migrate) and confirm:

- The dashboard shows non-zero counts.
- Phones lists 24 records; opening `a56` shows 19 spec rows, 3 variants and 4 listings.
- Changing a listing price adds a row to its price history count.
- Articles opens, and the rich editor offers the "Tabel perbandingan" block.
- Reports lists rows with a status filter.

- [ ] **Step 6: Confirm the public site still renders**

```bash
for p in / /katalog /hp/a56 /berita /merek /faq /bandingkan; do echo "$(curl -s -o /dev/null -w '%{http_code}' http://bandingin.test$p)  $p"; done
```

Expected: `200` for every path.

- [ ] **Step 7: Record the admin rule**

Use the Boost `record-rule` MCP tool with:

- **glob:** `app/Filament/**`
- **title:** Filament v5 panel conventions
- **note:**

```
The admin panel is Filament v5 (not v4 — v4 targets Livewire 3 and this app runs Livewire 4). Resources live in app/Filament/Resources/<Plural>/ with Pages/, Schemas/ and Tables/ subdirectories; edit the Schemas/*Form.php and Tables/*Table.php classes rather than the resource shell. Generate new resources with `make:filament-resource <Model> --generate` and then adjust.

Navigation groups are Katalog, Konten, Operasional. Registration is disabled; create accounts with `php artisan make:filament-user`.

Phone specs are edited inline on the phone form because all 19 always exist; variants and listings are relation managers. Any field exposing comparable_value must keep its helper text about negated inverted metrics.

Every table showing a related value eager-loads it. Admin code must never import from App\Livewire — tests/Feature/Filament/AdminBoundaryTest.php enforces this.
```

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "test: enforce admin and public site boundary"
```

---

## Done criteria

- `composer test` is green.
- `php artisan migrate:fresh --seed` completes, and the panel edits every catalogue table.
- All 7 public pages still return 200.
- `git diff main -- app/Livewire app/Support app/Data resources/views` is empty.
- `vendor/bin/pint --dirty --format agent` reports no changes.

## What comes next

**Part 3** switches the Livewire pages from the `App\Support\*` catalogues to Eloquent, renders article bodies through `RichContentRenderer` so the comparison block expands, rebuilds the berita-detail view around rendered HTML, and retires the Support classes after inlining their data into the seeders.
