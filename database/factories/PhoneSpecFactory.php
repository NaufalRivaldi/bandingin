<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PhoneSpec>
 */
class PhoneSpecFactory extends Factory
{
    protected $model = PhoneSpec::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'phone_id' => Phone::factory(),
            'spec_definition_id' => SpecDefinition::factory(),
            'display_value' => '8 GB',
            'comparable_value' => 8,
        ];
    }
}
