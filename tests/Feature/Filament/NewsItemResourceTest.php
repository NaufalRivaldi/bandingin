<?php

declare(strict_types=1);

use App\Filament\Resources\NewsItems\NewsItemResource;
use App\Filament\Resources\NewsItems\Pages\ListNewsItems;
use App\Models\NewsItem;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('lists ingested news items', function (): void {
    $item = NewsItem::factory()->create(['title' => 'realme 16 Pro Harry Potter Edition']);

    Livewire::test(ListNewsItems::class)->assertCanSeeTableRecords([$item]);
});

it('filters by channel', function (): void {
    $gadget = NewsItem::factory()->create(['channel' => 'Gadget']);
    $culture = NewsItem::factory()->create(['channel' => 'Culture']);

    Livewire::test(ListNewsItems::class)
        ->filterTable('channel', 'Gadget')
        ->assertCanSeeTableRecords([$gadget])
        ->assertCanNotSeeTableRecords([$culture]);
});

it('sorts newest first by default', function (): void {
    $older = NewsItem::factory()->create(['published_at' => now()->subWeek()]);
    $newer = NewsItem::factory()->create(['published_at' => now()]);

    Livewire::test(ListNewsItems::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

it('cannot be created by hand', function (): void {
    expect(NewsItemResource::canCreate())->toBeFalse();
});

it('exposes only an index page', function (): void {
    expect(array_keys(NewsItemResource::getPages()))->toBe(['index']);
});

it('opens the index page', function (): void {
    $this->get(NewsItemResource::getUrl('index'))->assertSuccessful();
});
