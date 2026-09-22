<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ArticleCategory;
use App\Models\ScrapeSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ScrapeSource>
 */
class ScrapeSourceFactory extends Factory
{
    protected $model = ScrapeSource::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $slug = fake()->unique()->slug(1);

        return [
            'name' => 'tek.id — '.ucfirst($slug),
            'feed_url' => 'https://www.tek.id/'.$slug.'/rss',
            'category' => ArticleCategory::Rilis,
            'is_active' => true,
            'last_run_at' => null,
            'last_error' => null,
        ];
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
