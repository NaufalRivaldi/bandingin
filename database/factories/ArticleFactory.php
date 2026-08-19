<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ArticleCategory;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(4),
            'title' => fake()->sentence(),
            'category' => ArticleCategory::Rilis,
            'excerpt' => fake()->paragraph(),
            'body' => '<p>'.fake()->paragraph().'</p>',
            'tags' => ['Xiaomi'],
            'read_minutes' => fake()->numberBetween(3, 12),
            'published_at' => now(),
        ];
    }

    public function draft(): self
    {
        return $this->state(fn (): array => ['published_at' => null]);
    }
}
