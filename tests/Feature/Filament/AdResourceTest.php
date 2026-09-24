<?php

declare(strict_types=1);

use App\Filament\Resources\Ads\Pages\ManageAds;
use App\Models\Ad;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('lists the ads', function (): void {
    $ad = Ad::factory()->create(['name' => 'Tokopedia campaign']);

    Livewire::test(ManageAds::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$ad]);
});

it('creates an ad', function (): void {
    Livewire::test(ManageAds::class)
        ->callAction('create', [
            'name' => 'Shopee campaign',
            'url' => 'https://sponsor.test/offer',
            'weight' => 4,
            'is_active' => true,
        ])
        ->assertHasNoActionErrors();

    expect(Ad::where('name', 'Shopee campaign')->sole())
        ->url->toBe('https://sponsor.test/offer')
        ->weight->toBe(4)
        ->is_active->toBeTrue();
});

it('rejects a destination that is not a url', function (): void {
    Livewire::test(ManageAds::class)
        ->callAction('create', [
            'name' => 'Broken campaign',
            'url' => 'not-a-url',
            'weight' => 1,
        ])
        ->assertHasActionErrors(['url']);
});
