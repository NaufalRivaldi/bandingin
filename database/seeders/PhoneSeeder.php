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

    /** @param  array<string, int>  $definitions  Spec key => spec_definitions.id */
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

    /** @param  array<string, int>  $stores  Store name => stores.id */
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
