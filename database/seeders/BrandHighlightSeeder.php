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
 * Models with no PhoneCatalog entry get a minimal DRAFT phone row — name, price
 * and the catalogue's spec blurb — so the Merek page can render its card while
 * the catalogue ignores them. An editor fills in the rest and publishes later.
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

    /** @param  array{name: string, price: string, spec: string}  $model */
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
            // Stubs: name and price only, no specs or scores. They are drafts so the
            // catalogue and search never show a half-empty card; the Merek page reads
            // the highlights relation directly, which does not filter on published.
            'published_at' => null,
        ]);
    }
}
