<?php

declare(strict_types=1);

use App\Filament\Resources\Phones\Pages\EditPhone;
use App\Filament\Resources\Phones\RelationManagers\ListingsRelationManager;
use App\Filament\Resources\Phones\RelationManagers\VariantsRelationManager;
use App\Models\Phone;
use App\Models\PhoneListing;
use App\Models\PhoneVariant;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('lists a phone variants', function (): void {
    $phone = Phone::factory()->create();
    $variant = PhoneVariant::factory()->create(['phone_id' => $phone->id, 'label' => '12/512 GB']);

    Livewire::test(VariantsRelationManager::class, [
        'ownerRecord' => $phone,
        'pageClass' => EditPhone::class,
    ])->assertCanSeeTableRecords([$variant]);
});

it('lists a phone listings', function (): void {
    $phone = Phone::factory()->create();
    $listing = PhoneListing::factory()->create(['phone_id' => $phone->id]);

    Livewire::test(ListingsRelationManager::class, [
        'ownerRecord' => $phone,
        'pageClass' => EditPhone::class,
    ])->assertCanSeeTableRecords([$listing]);
});

it('records a price snapshot when a listing price is edited in the panel', function (): void {
    $phone = Phone::factory()->create();
    $listing = PhoneListing::factory()->create(['phone_id' => $phone->id, 'price' => 4_999_000]);

    expect($listing->snapshots()->count())->toBe(1);

    Livewire::test(ListingsRelationManager::class, [
        'ownerRecord' => $phone,
        'pageClass' => EditPhone::class,
    ])
        ->callTableAction('edit', $listing, data: ['price' => 4_499_000])
        ->assertHasNoTableActionErrors();

    expect($listing->fresh()->price)->toBe(4_499_000)
        ->and($listing->snapshots()->orderBy('id')->pluck('price')->all())
        ->toBe([4_999_000, 4_499_000]);
});
