<?php

declare(strict_types=1);

use App\Filament\Resources\SpecDefinitions\SpecDefinitionResource;
use App\Filament\Resources\Stores\StoreResource;
use App\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

/**
 * Every resource that has a create page. Each dataset row is a single-element
 * array so Pest passes one class per test rather than spreading them as args.
 *
 * @return array<string, array{class-string}>
 */
function creatableResources(): array
{
    return [
        'spec definitions' => [SpecDefinitionResource::class],
        'stores' => [StoreResource::class],
    ];
}

/** @return array<string, array{class-string}> */
function listableResources(): array
{
    return creatableResources();
}

it('renders the list page', function (string $resource): void {
    $this->get($resource::getUrl('index'))->assertSuccessful();
})->with(listableResources());

it('renders the create page', function (string $resource): void {
    $this->get($resource::getUrl('create'))->assertSuccessful();
})->with(creatableResources());
