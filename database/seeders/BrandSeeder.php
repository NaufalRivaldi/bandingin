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
