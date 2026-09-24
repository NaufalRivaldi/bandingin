<?php

declare(strict_types=1);

use App\Filament\Pages\AdSettingsPage;
use App\Models\User;
use App\Settings\AdSettings;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('fills the form from the stored settings', function (): void {
    enableAds(frequency: 7, cooldownMinutes: 45);

    Livewire::test(AdSettingsPage::class)
        ->assertOk()
        ->assertSchemaStateSet([
            'enabled' => true,
            'frequency' => 7,
            'cooldown_minutes' => 45,
        ]);
});

it('saves the knobs back to the settings', function (): void {
    Livewire::test(AdSettingsPage::class)
        ->fillForm([
            'enabled' => true,
            'frequency' => 2,
            'cooldown_minutes' => 10,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(AdSettings::class))
        ->enabled->toBeTrue()
        ->frequency->toBe(2)
        ->cooldown_minutes->toBe(10);
});

it('rejects a frequency below one', function (): void {
    Livewire::test(AdSettingsPage::class)
        ->fillForm(['enabled' => true, 'frequency' => 0, 'cooldown_minutes' => 10])
        ->call('save')
        ->assertHasFormErrors(['frequency']);
});
