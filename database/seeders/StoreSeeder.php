<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class StoreSeeder extends Seeder
{
    /**
     * Names match PhoneSeeder::SELLER_OFFSETS; PhoneSeeder resolves
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
