<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ReportStatus;
use App\Models\Brand;
use App\Models\NewsletterSubscriber;
use App\Models\Phone;
use App\Models\Report;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class CatalogStatsWidget extends StatsOverviewWidget
{
    /** @return list<Stat> */
    protected function getStats(): array
    {
        $awaiting = self::reportsAwaitingTriage();

        return [
            Stat::make('HP tayang', (string) Phone::published()->count())
                ->description('Tampil di katalog publik'),
            Stat::make('Merek', (string) Brand::count()),
            Stat::make('Laporan belum ditangani', (string) $awaiting)
                ->description($awaiting > 0 ? 'Perlu triase' : 'Semua sudah ditangani')
                ->color($awaiting > 0 ? 'warning' : 'success'),
            Stat::make('Pelanggan newsletter', (string) NewsletterSubscriber::count()),
        ];
    }

    /** Untriaged means no status yet, or explicitly still "Baru". */
    private static function reportsAwaitingTriage(): int
    {
        return Report::query()
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('status')
                ->orWhere('status', ReportStatus::Baru))
            ->count();
    }
}
