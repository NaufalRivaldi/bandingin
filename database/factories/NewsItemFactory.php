<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NewsItem;
use App\News\TekChannel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NewsItem>
 */
class NewsItemFactory extends Factory
{
    protected $model = NewsItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $externalId = 'b2'.Str::random(7);
        $channel = fake()->randomElement(TekChannel::cases());

        return [
            'external_id' => $externalId,
            'source' => 'tek.id',
            'channel' => $channel->value,
            'title' => fake()->sentence(),
            'excerpt' => fake()->paragraph(),
            'url' => 'https://www.tek.id/'.$channel->path().'/'.fake()->slug(4).'-'.$externalId,
            'image_url' => 'https://static.tek.id/2026/09/21/'.fake()->slug(2).'.jpeg',
            'published_at' => now()->subHours(fake()->numberBetween(1, 200)),
        ];
    }

    public function withoutImage(): self
    {
        return $this->state(fn (): array => ['image_url' => null]);
    }
}
