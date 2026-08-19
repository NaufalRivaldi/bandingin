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
