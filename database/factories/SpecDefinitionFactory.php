<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SpecDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SpecDefinition>
 */
class SpecDefinitionFactory extends Factory
{
    protected $model = SpecDefinition::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->word(),
            'label' => fake()->word(),
            'group' => 'Performa',
            'sort' => 0,
        ];
    }
}
