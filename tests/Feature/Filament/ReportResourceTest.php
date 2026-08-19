<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Filament\Resources\Reports\Pages\ListReports;
use App\Filament\Resources\Reports\ReportResource;
use App\Models\Report;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('filters reports by status', function (): void {
    $untriaged = Report::factory()->create();
    $processed = Report::factory()->processed()->create();

    Livewire::test(ListReports::class)
        ->assertCanSeeTableRecords([$untriaged, $processed])
        ->filterTable('status', ReportStatus::Diproses->value)
        ->assertCanSeeTableRecords([$processed])
        ->assertCanNotSeeTableRecords([$untriaged]);
});

it('does not offer report creation', function (): void {
    expect(ReportResource::canCreate())->toBeFalse();
});

it('triages a report through the panel', function (): void {
    $report = Report::factory()->create();

    Livewire::test(ListReports::class)
        ->callTableAction('edit', $report, data: ['status' => ReportStatus::Selesai->value])
        ->assertHasNoTableActionErrors();

    expect($report->fresh()->status)->toBe(ReportStatus::Selesai);
});
