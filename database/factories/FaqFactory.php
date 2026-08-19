<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FaqTopic;
use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    protected $model = Faq::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'topic' => FaqTopic::Harga,
            'question' => fake()->sentence().'?',
            'answer' => fake()->paragraph(),
            'sort' => 0,
        ];
    }
}
