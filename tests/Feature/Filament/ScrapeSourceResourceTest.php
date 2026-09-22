<?php

declare(strict_types=1);

use App\Enums\ArticleCategory;
use App\Filament\Resources\ScrapeSources\Pages\ListScrapeSources;
use App\Filament\Resources\ScrapeSources\ScrapeSourceResource;
use App\Models\ScrapeSource;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('lists the configured sources', function (): void {
    $source = ScrapeSource::factory()->create(['name' => 'tek.id — Gadget']);

    Livewire::test(ListScrapeSources::class)->assertCanSeeTableRecords([$source]);
});

it('filters to active sources', function (): void {
    $active = ScrapeSource::factory()->create();
    $paused = ScrapeSource::factory()->inactive()->create();

    Livewire::test(ListScrapeSources::class)
        ->filterTable('is_active', true)
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$paused]);
});

it('shows which sources have failed', function (): void {
    $broken = ScrapeSource::factory()->create(['last_error' => 'feed tidak valid']);

    Livewire::test(ListScrapeSources::class)
        ->assertCanSeeTableRecords([$broken])
        ->assertSee('feed tidak valid');
});

it('creates a source from the panel', function (): void {
    $this->get(ScrapeSourceResource::getUrl('create'))->assertSuccessful();
});

it('opens the edit page', function (): void {
    $source = ScrapeSource::factory()->create();

    $this->get(ScrapeSourceResource::getUrl('edit', ['record' => $source]))->assertSuccessful();
});

it('offers every article category as a target', function (): void {
    $source = ScrapeSource::factory()->create(['category' => ArticleCategory::Panduan]);

    expect($source->fresh()->category)->toBe(ArticleCategory::Panduan);
});
