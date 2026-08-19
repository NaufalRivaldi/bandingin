<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\FeaturedMatchup;
use App\Models\Phone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeaturedMatchup>
 */
class FeaturedMatchupFactory extends Factory
{
    protected $model = FeaturedMatchup::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'left_phone_id' => Phone::factory(),
            'right_phone_id' => Phone::factory(),
            'sort' => 0,
        ];
    }
}
