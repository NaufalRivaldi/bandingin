<?php

declare(strict_types=1);

use App\Filament\Widgets\CatalogStatsWidget;
use App\Filament\Widgets\LatestReportsWidget;
use App\Models\Brand;
use App\Models\Phone;
use App\Models\Report;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('counts published phones, brands and untriaged reports', function (): void {
    Brand::factory()->count(2)->create();
    Phone::factory()->count(3)->create();
    Phone::factory()->unpublished()->create();
    Report::factory()->count(2)->create();
    Report::factory()->processed()->create();

    Livewire::test(CatalogStatsWidget::class)
        ->assertSuccessful()
        ->assertSee('HP tayang')
        ->assertSee('Laporan belum ditangani');

    expect(Phone::published()->count())->toBe(3)
        ->and(Report::whereNull('status')->count())->toBe(2);
});

it('shows the newest reports first', function (): void {
    $older = Report::factory()->create(['subject' => 'Laporan lama']);
    $older->forceFill(['created_at' => now()->subWeek()])->save();

    Report::factory()->create(['subject' => 'Laporan baru']);

    Livewire::test(LatestReportsWidget::class)
        ->assertSuccessful()
        ->assertSeeInOrder(['Laporan baru', 'Laporan lama']);
});

it('renders the dashboard with its widgets', function (): void {
    $this->get('/admin')->assertSuccessful();
});
