<?php

declare(strict_types=1);

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\FeaturedMatchups\FeaturedMatchupResource;
use App\Filament\Resources\NewsletterSubscribers\NewsletterSubscriberResource;
use App\Filament\Resources\PhoneListings\PhoneListingResource;
use App\Filament\Resources\Phones\PhoneResource;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\SpecDefinitions\SpecDefinitionResource;
use App\Filament\Resources\Stores\StoreResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Support\Icons\Heroicon;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

/**
 * Resources whose records are created inside the panel.
 *
 * Each dataset row is a single-element array so Pest passes one class per
 * test rather than spreading them all as arguments to one test.
 *
 * @return array<string, array{class-string}>
 */
function creatableResources(): array
{
    return [
        'spec definitions' => [SpecDefinitionResource::class],
        'stores' => [StoreResource::class],
        'brands' => [BrandResource::class],
        'phones' => [PhoneResource::class],
        'phone listings' => [PhoneListingResource::class],
        'articles' => [ArticleResource::class],
        'faqs' => [FaqResource::class],
        'featured matchups' => [FeaturedMatchupResource::class],
        'users' => [UserResource::class],
    ];
}

/**
 * Every resource, including the two fed by the public site rather than the
 * panel — reports arrive from /lapor and subscribers from the footer form.
 *
 * @return array<string, array{class-string}>
 */
function listableResources(): array
{
    return creatableResources() + [
        'reports' => [ReportResource::class],
        'newsletter subscribers' => [NewsletterSubscriberResource::class],
    ];
}

it('renders the list page', function (string $resource): void {
    $this->get($resource::getUrl('index'))->assertSuccessful();
})->with(listableResources());

it('renders the create page', function (string $resource): void {
    $this->get($resource::getUrl('create'))->assertSuccessful();
})->with(creatableResources());

it('covers every registered resource', function (): void {
    $registered = collect(Filament\Facades\Filament::getPanel('admin')->getResources());
    $covered = collect(listableResources())->flatten();

    expect($covered->sort()->values()->all())
        ->toBe($registered->sort()->values()->all());
});

it('gives every resource its own navigation icon', function (): void {
    $icons = collect(Filament\Facades\Filament::getPanel('admin')->getResources())
        ->mapWithKeys(fn (string $resource): array => [$resource => $resource::getNavigationIcon()]);

    expect($icons->filter(fn (mixed $icon): bool => $icon === Heroicon::OutlinedRectangleStack)->all())
        ->toBe([], 'A resource is still on the generator default icon.')
        ->and($icons->duplicates()->all())
        ->toBe([], 'Two resources share a navigation icon.');
});
