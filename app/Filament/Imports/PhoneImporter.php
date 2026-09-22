<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Actions\Phones\SyncPhoneRelations;
use App\Models\Brand;
use App\Models\Phone;
use App\Models\SpecDefinition;
use App\Models\Store;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

/**
 * Imports the whole catalogue from one CSV: one row per phone, carrying its
 * specs, variants and listings inline.
 *
 * Records are matched on slug, so re-importing the same file updates rather
 * than duplicates. Brands, stores and spec definitions are looked up, never
 * created — a row naming one that does not exist fails with a message saying
 * which, rather than planting a half-empty record the public site would render.
 *
 * Two separators run through the format: ";" between records, "|" between the
 * fields of a record.
 */
class PhoneImporter extends Importer
{
    /** Separates the fields within one packed record, and the items of a list. */
    private const string FIELD_SEPARATOR = '|';

    /** Separates packed records from each other. */
    private const string RECORD_SEPARATOR = ';';

    protected static ?string $model = Phone::class;

    /** @var Collection<string, Brand>|null */
    private ?Collection $brands = null;

    /** @var Collection<string, Store>|null */
    private ?Collection $stores = null;

    /** @return array<ImportColumn> */
    public static function getColumns(): array
    {
        return [
            ...self::coreColumns(),
            ...self::scoreColumns(),
            ...self::specColumns(),
            ...self::relationColumns(),
        ];
    }

    public function resolveRecord(): Phone
    {
        return Phone::firstOrNew(['slug' => $this->data['slug']]);
    }

    /**
     * The relation rows are packed into single cells, so they cannot be filled
     * onto the phone and are written here instead, once it has an id.
     */
    protected function afterSave(): void
    {
        app(SyncPhoneRelations::class)->handle(
            $this->record,
            $this->parseSpecs(),
            $this->parseVariants(),
            $this->parseListings(),
        );
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Impor ponsel selesai, '.Number::format($import->successful_rows).' '.str('baris')->plural($import->successful_rows).' berhasil.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('baris')->plural($failedRowsCount).' gagal.';
        }

        return $body;
    }

    /** @return array<ImportColumn> */
    private static function coreColumns(): array
    {
        return [
            ImportColumn::make('slug')
                ->label('Slug')
                ->helperText('Penentu data sama. Slug yang sudah ada akan diperbarui.')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('galaxy-a56-5g'),
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Galaxy A56 5G'),
            ImportColumn::make('brand')
                ->helperText('Nama atau slug merek yang sudah terdaftar.')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->fillRecordUsing(static function (Phone $record, string $state, PhoneImporter $importer): void {
                    $record->brand_id = $importer->resolveBrand($state)->id;
                })
                ->example('Samsung'),
            ImportColumn::make('image_urls')
                ->label('Foto produk')
                ->helperText('Tautan gambar, dipisah dengan "'.self::FIELD_SEPARATOR.'". Foto pertama dipakai di kartu katalog.')
                ->array(self::FIELD_SEPARATOR)
                ->rules(['array'])
                ->nestedRecursiveRules(['url', 'max:255'])
                ->example('https://contoh.com/a56-depan.jpg|https://contoh.com/a56-belakang.jpg'),
            ImportColumn::make('price')
                ->helperText('Rupiah penuh, angka saja.')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'min:0'])
                ->example('5499000'),
            ImportColumn::make('tag')
                ->rules(['nullable', 'string', 'max:255'])
                ->example('Terlaris'),
            ImportColumn::make('nfc')
                ->boolean()
                ->rules(['boolean'])
                ->example('1'),
            ImportColumn::make('ip68')
                ->boolean()
                ->rules(['boolean'])
                ->example('0'),
            ImportColumn::make('dimensions')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('162,2 x 77,5 x 7,4 mm'),
            ImportColumn::make('network')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('5G Dual SIM · Wi-Fi 6 · NFC'),
            ImportColumn::make('released_label')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->example('Maret 2025'),
            ImportColumn::make('released_at')
                ->requiredMapping()
                ->rules(['required', 'date'])
                ->example('2025-03-01'),
            ImportColumn::make('pros')
                ->helperText('Dipisah dengan "'.self::FIELD_SEPARATOR.'".')
                ->array(self::FIELD_SEPARATOR)
                ->rules(['array'])
                ->example('Update OS enam tahun|Layar AMOLED 120 Hz'),
            ImportColumn::make('cons')
                ->helperText('Dipisah dengan "'.self::FIELD_SEPARATOR.'".')
                ->array(self::FIELD_SEPARATOR)
                ->rules(['array'])
                ->example('Pengisian daya 45W kalah cepat'),
            ImportColumn::make('published_at')
                ->helperText('Kosongkan untuk menyimpan sebagai draf.')
                ->rules(['nullable', 'date'])
                ->example('2025-03-05 09:00:00'),
        ];
    }

    /** @return array<ImportColumn> */
    private static function scoreColumns(): array
    {
        $scores = [
            'score_performa' => 'Performa',
            'score_layar' => 'Layar',
            'score_kamera' => 'Kamera',
            'score_baterai' => 'Baterai',
            'score_nilai' => 'Nilai per Rupiah',
        ];

        return collect($scores)
            ->map(static fn (string $label, string $name): ImportColumn => ImportColumn::make($name)
                ->label('Skor '.$label)
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'min:0', 'max:100'])
                ->example('80'))
            ->values()
            ->all();
    }

    /**
     * Two columns per spec definition, generated from the table so that adding
     * a spec stays a spec_definitions row rather than a code change here.
     *
     * @return array<ImportColumn>
     */
    private static function specColumns(): array
    {
        return self::specDefinitions()
            ->flatMap(static fn (SpecDefinition $definition): array => [
                ImportColumn::make(self::specDisplayColumn($definition->key))
                    ->label($definition->label)
                    ->rules(['nullable', 'string', 'max:255'])
                    ->fillRecordUsing(self::notAPhoneColumn()),
                ImportColumn::make(self::specComparableColumn($definition->key))
                    ->label($definition->label.' (pembanding)')
                    ->helperText('Selalu "makin tinggi makin baik" — metrik terbalik seperti berat disimpan negatif.')
                    ->numeric()
                    ->rules(['nullable', 'integer'])
                    ->fillRecordUsing(self::notAPhoneColumn()),
            ])
            ->all();
    }

    /** @return array<ImportColumn> */
    private static function relationColumns(): array
    {
        return [
            ImportColumn::make('variants')
                ->label('Varian')
                ->helperText('label|harga|default, antar varian dipisah ";". Kosongkan untuk membiarkan varian yang ada.')
                ->rules(['nullable', 'string'])
                ->fillRecordUsing(self::notAPhoneColumn())
                ->example('8/256 GB|5499000|default;12/256 GB|5999000'),
            ImportColumn::make('listings')
                ->label('Harga toko')
                ->helperText('toko|harga|url|garansi|catatan, antar toko dipisah ";". Kosongkan untuk membiarkan harga toko yang ada.')
                ->rules(['nullable', 'string'])
                ->fillRecordUsing(self::notAPhoneColumn())
                ->example('Tokopedia|5299000|https://tokopedia.link/a56|Garansi resmi|Flash sale;Shopee|5349000|https://shopee.link/a56||'),
        ];
    }

    /** @throws RowImportFailedException */
    public function resolveBrand(string $name): Brand
    {
        $this->brands ??= Brand::all()->keyBy(static fn (Brand $brand): string => mb_strtolower($brand->name));

        $key = mb_strtolower(trim($name));

        return $this->brands->get($key)
            ?? $this->brands->first(static fn (Brand $brand): bool => $brand->slug === $key)
            ?? throw new RowImportFailedException('Merek "'.$name.'" tidak ditemukan.');
    }

    /** @throws RowImportFailedException */
    private function resolveStore(string $name): Store
    {
        $this->stores ??= Store::all()->keyBy(static fn (Store $store): string => mb_strtolower($store->name));

        $key = mb_strtolower(trim($name));

        return $this->stores->get($key)
            ?? $this->stores->first(static fn (Store $store): bool => $store->slug === $key)
            ?? throw new RowImportFailedException('Toko "'.$name.'" tidak ditemukan.');
    }

    /**
     * Null when no spec column was mapped, so a CSV without spec columns leaves
     * existing specs alone instead of deleting all nineteen.
     *
     * @return array<string, array{display: string, comparable: int}>|null
     */
    private function parseSpecs(): ?array
    {
        $specs = [];
        $mapped = false;

        foreach (self::specDefinitions() as $definition) {
            $display = $this->data[self::specDisplayColumn($definition->key)] ?? null;
            $comparable = $this->data[self::specComparableColumn($definition->key)] ?? null;

            if ($display === null && $comparable === null) {
                continue;
            }

            $mapped = true;

            if (blank($display)) {
                continue;
            }

            $specs[(string) $definition->id] = [
                'display' => (string) $display,
                'comparable' => (int) ($comparable ?? 0),
            ];
        }

        return $mapped ? $specs : null;
    }

    /** @return list<array{label: string, price: int, is_default: bool, sort: int}>|null */
    private function parseVariants(): ?array
    {
        $records = $this->packedRecords('variants');

        if ($records === null) {
            return null;
        }

        $variants = [];

        foreach ($records as $sort => $fields) {
            if (count($fields) < 2) {
                throw new RowImportFailedException('Varian "'.implode(self::FIELD_SEPARATOR, $fields).'" butuh minimal label dan harga.');
            }

            $variants[] = [
                'label' => $fields[0],
                'price' => (int) $fields[1],
                'is_default' => mb_strtolower($fields[2] ?? '') === 'default',
                'sort' => $sort + 1,
            ];
        }

        return $variants;
    }

    /** @return list<array{store_id: int, price: int, product_url: ?string, warranty_tag: ?string, note: ?string}>|null */
    private function parseListings(): ?array
    {
        $records = $this->packedRecords('listings');

        if ($records === null) {
            return null;
        }

        $listings = [];

        foreach ($records as $fields) {
            if (count($fields) < 2) {
                throw new RowImportFailedException('Harga toko "'.implode(self::FIELD_SEPARATOR, $fields).'" butuh minimal nama toko dan harga.');
            }

            $listings[] = [
                'store_id' => $this->resolveStore($fields[0])->id,
                'price' => (int) $fields[1],
                'product_url' => blank($fields[2] ?? null) ? null : $fields[2],
                'warranty_tag' => blank($fields[3] ?? null) ? null : $fields[3],
                'note' => blank($fields[4] ?? null) ? null : $fields[4],
            ];
        }

        return $listings;
    }

    /**
     * Splits a packed cell into records of fields. Returns null when the column
     * is unmapped or blank, which means "leave this relation untouched".
     *
     * @return list<list<string>>|null
     */
    private function packedRecords(string $column): ?array
    {
        $value = $this->data[$column] ?? null;

        if (blank($value)) {
            return null;
        }

        // trim() must be wrapped: Collection::map passes the key as a second
        // argument, which trim() would read as its character list.
        return collect(explode(self::RECORD_SEPARATOR, (string) $value))
            ->map(static fn (string $record): string => trim($record))
            ->filter(static fn (string $record): bool => filled($record))
            ->map(static fn (string $record): array => array_map(trim(...), explode(self::FIELD_SEPARATOR, $record)))
            ->values()
            ->all();
    }

    /** @return Collection<int, SpecDefinition> */
    private static function specDefinitions(): Collection
    {
        return SpecDefinition::orderBy('sort')->get();
    }

    /**
     * Marks a column as carrying relation data rather than a phones column, so
     * Filament does not try to set it on the model. afterSave() reads it instead.
     */
    private static function notAPhoneColumn(): \Closure
    {
        return static function (): void {};
    }

    private static function specDisplayColumn(string $key): string
    {
        return 'spec_'.$key;
    }

    private static function specComparableColumn(string $key): string
    {
        return 'spec_'.$key.'_cmp';
    }
}
