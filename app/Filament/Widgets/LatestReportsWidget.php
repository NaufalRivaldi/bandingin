<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Report;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestReportsWidget extends TableWidget
{
    protected static ?string $heading = 'Laporan terbaru';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Report::query()->latest())
            ->paginated(false)
            ->recordUrl(fn (Report $record): string => route('filament.admin.resources.reports.edit', $record))
            ->columns([
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge(),
                TextColumn::make('subject')
                    ->label('Subjek')
                    ->limit(40),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->placeholder('Belum ditangani'),
                TextColumn::make('created_at')
                    ->label('Masuk')
                    ->dateTime(),
            ]);
    }
}
