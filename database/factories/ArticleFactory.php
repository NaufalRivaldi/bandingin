<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ArticleCategory;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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

    public function scraped(): self
    {
        return $this->state(function (): array {
            $externalId = 'b2'.Str::random(7);

            return [
                'source' => 'tek.id',
                'source_url' => 'https://www.tek.id/gadget/'.fake()->slug(4).'-'.$externalId,
                'external_id' => $externalId,
                'image_url' => 'https://static.tek.id/2026/09/21/'.fake()->slug(2).'.jpeg',
            ];
        });
    }
}
