<?php

declare(strict_types=1);

use App\Filament\Imports\PhoneImporter;
use App\Filament\Resources\Phones\Pages\EditPhone;
use App\Filament\Resources\Phones\Pages\ListPhones;
use App\Models\Brand;
use App\Models\Phone;
use App\Models\SpecDefinition;
use App\Models\Store;
use App\Models\User;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/**
 * Runs a CSV row through the importer the same way the queued job does, so the
 * columns, record matching and relation syncing are all exercised together.
 */
function importRow(array $row): Phone
{
    $import = Import::create([
        'completed_at' => now(),
        'file_name' => 'phones.csv',
        'file_path' => 'phones.csv',
        'importer' => PhoneImporter::class,
        'processed_rows' => 0,
        'total_rows' => 1,
        'successful_rows' => 0,
        'user_id' => User::factory()->create()->id,
    ]);

    $columnMap = collect(PhoneImporter::getColumns())
        ->mapWithKeys(fn ($column): array => [$column->getName() => $column->getName()])
        ->all();

    (new PhoneImporter($import, $columnMap, []))($row);

    return Phone::where('slug', $row['slug'])->sole();
}

function phoneRow(array $overrides = []): array
{
    return [
        'slug' => 'galaxy-a56-5g',
        'name' => 'Galaxy A56 5G',
        'brand' => 'Samsung',
        'price' => '5499000',
        'tag' => '',
        'nfc' => '1',
        'ip68' => '0',
        'dimensions' => '162,2 x 77,5 x 7,4 mm',
        'network' => '5G Dual SIM',
        'released_label' => 'Maret 2025',
        'released_at' => '2025-03-01',
        'pros' => 'Update OS enam tahun|Layar AMOLED 120 Hz',
        'cons' => 'Pengisian daya 45W kalah cepat',
        'published_at' => '2025-03-05 09:00:00',
        'score_performa' => '72',
        'score_layar' => '80',
        'score_kamera' => '78',
        'score_baterai' => '74',
        'score_nilai' => '70',
        ...$overrides,
    ];
}

beforeEach(function (): void {
    Brand::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);
    Store::factory()->create(['name' => 'Tokopedia', 'slug' => 'tokopedia']);
    Store::factory()->create(['name' => 'Shopee', 'slug' => 'shopee']);
});

it('creates a phone from a row', function (): void {
    $phone = importRow(phoneRow());

    expect($phone->name)->toBe('Galaxy A56 5G')
        ->and($phone->price)->toBe(5499000)
        ->and($phone->brand->name)->toBe('Samsung')
        ->and($phone->nfc)->toBeTrue()
        ->and($phone->ip68)->toBeFalse()
        ->and($phone->pros)->toBe(['Update OS enam tahun', 'Layar AMOLED 120 Hz'])
        ->and($phone->score_performa)->toBe(72)
        ->and($phone->published_at)->not->toBeNull();
});

it('updates the existing phone when the slug matches instead of duplicating', function (): void {
    importRow(phoneRow());
    $phone = importRow(phoneRow(['price' => '4999000', 'name' => 'Galaxy A56 5G (2025)']));

    expect(Phone::where('slug', 'galaxy-a56-5g')->count())->toBe(1)
        ->and($phone->price)->toBe(4999000)
        ->and($phone->name)->toBe('Galaxy A56 5G (2025)');
});

it('leaves a phone as a draft when published_at is blank', function (): void {
    expect(importRow(phoneRow(['published_at' => '']))->published_at)->toBeNull();
});

it('resolves the brand by slug as well as by name', function (): void {
    expect(importRow(phoneRow(['brand' => 'samsung']))->brand->name)->toBe('Samsung');
});

it('fails the row when the brand does not exist', function (): void {
    expect(fn () => importRow(phoneRow(['brand' => 'Vivo'])))
        ->toThrow(RowImportFailedException::class, 'Merek "Vivo" tidak ditemukan.');
});

it('fails the row when a required column is blank', function (): void {
    expect(fn () => importRow(phoneRow(['name' => ''])))->toThrow(ValidationException::class);
});

it('writes specs against their definitions', function (): void {
    SpecDefinition::factory()->create(['key' => 'baterai', 'label' => 'Kapasitas', 'sort' => 1]);
    SpecDefinition::factory()->create(['key' => 'berat', 'label' => 'Berat', 'sort' => 2]);

    $phone = importRow(phoneRow([
        'spec_baterai' => '5.000 mAh',
        'spec_baterai_cmp' => '5000',
        'spec_berat' => '198 g',
        'spec_berat_cmp' => '-198',
    ]))->load('specs.definition');

    expect($phone->display('baterai'))->toBe('5.000 mAh')
        ->and($phone->comparable('baterai'))->toBe(5000)
        ->and($phone->comparable('berat'))->toBe(-198);
});

it('replaces specs that are no longer in the CSV', function (): void {
    SpecDefinition::factory()->create(['key' => 'baterai', 'label' => 'Kapasitas', 'sort' => 1]);
    SpecDefinition::factory()->create(['key' => 'berat', 'label' => 'Berat', 'sort' => 2]);

    importRow(phoneRow([
        'spec_baterai' => '5.000 mAh', 'spec_baterai_cmp' => '5000',
        'spec_berat' => '198 g', 'spec_berat_cmp' => '-198',
    ]));

    $phone = importRow(phoneRow([
        'spec_baterai' => '5.500 mAh', 'spec_baterai_cmp' => '5500',
        'spec_berat' => '', 'spec_berat_cmp' => '',
    ]))->load('specs.definition');

    expect($phone->specs)->toHaveCount(1)
        ->and($phone->display('baterai'))->toBe('5.500 mAh');
});

it('creates variants in the order they are listed', function (): void {
    $phone = importRow(phoneRow([
        'variants' => '8/256 GB|5499000|default;12/256 GB|5999000',
    ]));

    expect($phone->variants)->toHaveCount(2)
        ->and($phone->variants->first()->label)->toBe('8/256 GB')
        ->and($phone->variants->first()->is_default)->toBeTrue()
        ->and($phone->variants->last()->price)->toBe(5999000)
        ->and($phone->variants->last()->is_default)->toBeFalse();
});

it('creates listings and resolves their stores', function (): void {
    $phone = importRow(phoneRow([
        'listings' => 'Tokopedia|5299000|https://tokopedia.link/a56|Garansi resmi|Flash sale;Shopee|5349000|||',
    ]));

    $tokopedia = $phone->listings->firstWhere('price', 5299000);

    expect($phone->listings)->toHaveCount(2)
        ->and($tokopedia->store->name)->toBe('Tokopedia')
        ->and($tokopedia->product_url)->toBe('https://tokopedia.link/a56')
        ->and($tokopedia->warranty_tag)->toBe('Garansi resmi')
        ->and($phone->listings->firstWhere('price', 5349000)->product_url)->toBeNull();
});

it('fails the row when a listing names an unknown store', function (): void {
    expect(fn () => importRow(phoneRow(['listings' => 'Blibli|5299000'])))
        ->toThrow(RowImportFailedException::class, 'Toko "Blibli" tidak ditemukan.');
});

it('prunes listings and variants that are no longer in the CSV', function (): void {
    importRow(phoneRow([
        'variants' => '8/256 GB|5499000|default;12/256 GB|5999000',
        'listings' => 'Tokopedia|5299000;Shopee|5349000',
    ]));

    $phone = importRow(phoneRow([
        'variants' => '8/256 GB|5399000|default',
        'listings' => 'Tokopedia|5199000',
    ]));

    expect($phone->variants)->toHaveCount(1)
        ->and($phone->variants->first()->price)->toBe(5399000)
        ->and($phone->listings)->toHaveCount(1)
        ->and($phone->listings->first()->price)->toBe(5199000);
});

it('leaves existing relations alone when the columns are blank', function (): void {
    importRow(phoneRow([
        'variants' => '8/256 GB|5499000|default',
        'listings' => 'Tokopedia|5299000',
    ]));

    $phone = importRow(phoneRow(['variants' => '', 'listings' => '']));

    expect($phone->variants)->toHaveCount(1)
        ->and($phone->listings)->toHaveCount(1);
});

it('records a price snapshot when an imported listing price changes', function (): void {
    importRow(phoneRow(['listings' => 'Tokopedia|5299000']));
    $phone = importRow(phoneRow(['listings' => 'Tokopedia|5199000']));

    expect($phone->listings->first()->snapshots()->pluck('price')->all())
        ->toBe([5299000, 5199000]);
});

it('offers every mapped column in the example CSV', function (): void {
    SpecDefinition::factory()->create(['key' => 'baterai', 'label' => 'Kapasitas', 'sort' => 1]);

    $headers = collect(PhoneImporter::getColumns())
        ->map(fn ($column): string => $column->getExampleHeader())
        ->all();

    expect($headers)->toContain('slug', 'brand', 'variants', 'listings', 'spec_baterai', 'spec_baterai_cmp');
});

it('serves a downloadable example CSV listing every column', function (): void {
    SpecDefinition::factory()->create(['key' => 'baterai', 'label' => 'Kapasitas', 'sort' => 1]);

    $action = Livewire::test(ListPhones::class)
        ->instance()
        ->getAction('import')
        ->getModalAction('downloadExample');

    $response = $action->call();

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    // The stream is written with a UTF-8 BOM, which sits on the first header.
    $csv = ltrim($csv, "\u{FEFF}");
    $headers = str_getcsv(strtok($csv, "\n"));

    expect($headers)->toContain('slug', 'name', 'brand', 'price', 'variants', 'listings')
        ->and($headers)->toContain('spec_baterai', 'spec_baterai_cmp')
        ->and($csv)->toContain('galaxy-a56-5g');
});

it('imports a single hotlinked product photo', function (): void {
    expect(importRow(phoneRow(['image_urls' => 'https://contoh.com/a56.jpg']))->images())
        ->toBe(['https://contoh.com/a56.jpg']);
});

it('imports a gallery of photos in order', function (): void {
    $phone = importRow(phoneRow([
        'image_urls' => 'https://contoh.com/depan.jpg|https://contoh.com/belakang.jpg|https://contoh.com/samping.jpg',
    ]));

    expect($phone->images())->toBe([
        'https://contoh.com/depan.jpg',
        'https://contoh.com/belakang.jpg',
        'https://contoh.com/samping.jpg',
    ])->and($phone->primaryImage())->toBe('https://contoh.com/depan.jpg');
});

it('leaves the gallery empty when the column is blank', function (): void {
    $phone = importRow(phoneRow(['image_urls' => '']));

    expect($phone->images())->toBe([])
        ->and($phone->primaryImage())->toBeNull();
});

it('replaces the whole gallery on re-import', function (): void {
    importRow(phoneRow(['image_urls' => 'https://contoh.com/a.jpg|https://contoh.com/b.jpg']));
    $phone = importRow(phoneRow(['image_urls' => 'https://contoh.com/c.jpg']));

    expect($phone->images())->toBe(['https://contoh.com/c.jpg']);
});

it('fails the row when any photo url is not a url', function (): void {
    expect(fn () => importRow(phoneRow(['image_urls' => 'https://contoh.com/a.jpg|bukan-url'])))
        ->toThrow(ValidationException::class);
});

it('lets the phone form save a gallery and save without one', function (): void {
    $phone = Phone::factory()->create(['image_urls' => null]);

    Livewire::test(EditPhone::class, ['record' => $phone->getRouteKey()])
        ->assertFormSet(['image_urls' => []])
        ->fillForm(['image_urls' => [['url' => 'https://contoh.com/a.jpg'], ['url' => 'https://contoh.com/b.jpg']]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($phone->refresh()->images())
        ->toBe(['https://contoh.com/a.jpg', 'https://contoh.com/b.jpg']);
});
