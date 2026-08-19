<?php

declare(strict_types=1);

use App\Enums\ReportStatus;
use App\Models\Report;

it('lists the four triage statuses', function (): void {
    expect(ReportStatus::values())->toBe(['Baru', 'Diproses', 'Selesai', 'Ditolak']);
});

it('defaults to no status when submitted from the site', function (): void {
    expect(Report::factory()->create()->status)->toBeNull();
});

it('casts an assigned status to the enum', function (): void {
    $report = Report::factory()->processed()->create();

    expect($report->fresh()->status)->toBe(ReportStatus::Diproses);
});

it('filters reports awaiting triage', function (): void {
    Report::factory()->create();
    Report::factory()->processed()->create();

    $awaiting = Report::whereNull('status')
        ->orWhere('status', ReportStatus::Baru)
        ->count();

    expect($awaiting)->toBe(1);
});
