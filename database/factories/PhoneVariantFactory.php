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
