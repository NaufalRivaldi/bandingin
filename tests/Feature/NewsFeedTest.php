<?php

declare(strict_types=1);

use App\Livewire\NewsFeed;
use App\Models\NewsItem;
use Livewire\Livewire;

it('lists the newest published items', function (): void {
    $newest = NewsItem::factory()->create([
        'title' => 'realme 16 Pro Harry Potter Edition',
        'published_at' => now(),
    ]);

    Livewire::test(NewsFeed::class)->assertSee($newest->title);
});

it('hides items with no published date', function (): void {
    NewsItem::factory()->create(['title' => 'Belum terbit', 'published_at' => null]);

    Livewire::test(NewsFeed::class)->assertDontSee('Belum terbit');
});

it('caps the list at eight items', function (): void {
    NewsItem::factory()->count(12)->create();

    $component = Livewire::test(NewsFeed::class)->instance();

    expect($component->items())->toHaveCount(8);
});

it('orders newest first', function (): void {
    $older = NewsItem::factory()->create(['published_at' => now()->subWeek()]);
    $newer = NewsItem::factory()->create(['published_at' => now()]);

    $component = Livewire::test(NewsFeed::class)->instance();

    expect($component->items()->first()->id)->toBe($newer->id)
        ->and($component->items()->last()->id)->toBe($older->id);
});

it('links off-site without passing seo equity', function (): void {
    NewsItem::factory()->create(['url' => 'https://www.tek.id/gadget/realme-b2qbp9uhh']);

    Livewire::test(NewsFeed::class)
        ->assertSee('https://www.tek.id/gadget/realme-b2qbp9uhh')
        ->assertSee('rel="noopener nofollow"', escape: false)
        ->assertSee('target="_blank"', escape: false);
});

it('credits the source', function (): void {
    NewsItem::factory()->create();

    Livewire::test(NewsFeed::class)->assertSee('tek.id');
});

it('renders an item that has no image', function (): void {
    $item = NewsItem::factory()->withoutImage()->create(['title' => 'Tanpa gambar']);

    Livewire::test(NewsFeed::class)->assertSee($item->title);
});

it('renders nothing when there are no items', function (): void {
    Livewire::test(NewsFeed::class)->assertDontSee('Kabar Teknologi');
});
