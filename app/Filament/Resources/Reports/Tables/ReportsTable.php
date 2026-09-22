<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reports\Tables;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->sortable(),
                TextColumn::make('subject')
                    ->label('Subjek')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('email')
                    ->label('Pelapor')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->placeholder('Belum ditangani')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Masuk')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Jenis')
                    ->options(ReportType::class)
                    ->multiple(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ReportStatus::class)
                    ->multiple(),
                TernaryFilter::make('status_triaged')
                    ->label('Triase')
                    ->placeholder('Semua laporan')
                    ->trueLabel('Sudah ditangani')
                    ->falseLabel('Belum ditangani')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('status'),
                        false: fn (Builder $query): Builder => $query->whereNull('status'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                Filter::make('received_between')
                    ->label('Tanggal masuk')
                    ->schema([
                        DatePicker::make('received_from')
                            ->label('Masuk dari')
                            ->placeholder('1 Jan 2025'),
                        DatePicker::make('received_until')
                            ->label('Masuk sampai')
                            ->placeholder('31 Des 2025'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['received_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['received_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['received_from'] ?? null)) {
                            $indicators[] = 'Masuk dari '.$data['received_from'];
                        }

                        if (filled($data['received_until'] ?? null)) {
                            $indicators[] = 'Masuk sampai '.$data['received_until'];
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->deferFilters()
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
