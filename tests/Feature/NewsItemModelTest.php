<?php

declare(strict_types=1);

use App\Models\NewsItem;
use Illuminate\Database\UniqueConstraintViolationException;

it('defaults the source to tek.id', function (): void {
    $item = NewsItem::factory()->create();

    expect($item->fresh()->source)->toBe('tek.id');
});

it('rejects a duplicate external id', function (): void {
    NewsItem::factory()->create(['external_id' => 'b2qbp9uhh']);

    expect(fn () => NewsItem::factory()->create(['external_id' => 'b2qbp9uhh']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('rejects a duplicate url', function (): void {
    NewsItem::factory()->create(['url' => 'https://www.tek.id/gadget/a-b2qbp9uhh']);

    expect(fn () => NewsItem::factory()->create(['url' => 'https://www.tek.id/gadget/a-b2qbp9uhh']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('formats its published date the Indonesian way', function (): void {
    $item = NewsItem::factory()->create(['published_at' => '2026-08-12 09:00:00']);

    expect($item->dateShort())->toBe('12 Agu 2026')
        ->and($item->dateLong())->toBe('12 Agustus 2026');
});

it('scopes to items with a published date', function (): void {
    NewsItem::factory()->count(2)->create();
    NewsItem::factory()->create(['published_at' => null]);

    expect(NewsItem::published()->count())->toBe(2);
});

it('allows a null image url', function (): void {
    $item = NewsItem::factory()->withoutImage()->create();

    expect($item->image_url)->toBeNull();
});
