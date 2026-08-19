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
