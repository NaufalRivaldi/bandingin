<?php

declare(strict_types=1);

namespace App\Filament\Resources\Brands\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BrandsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('bestselling_range')
                    ->label('Rentang terlaris')
                    ->searchable(),
                TextColumn::make('os_update_note')
                    ->label('Update OS')
                    ->searchable(),
                TextColumn::make('phones_count')
                    ->label('Jumlah HP')
                    ->counts('phones')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('has_phones')
                    ->label('Isi katalog')
                    ->placeholder('Semua merek')
                    ->trueLabel('Punya HP')
                    ->falseLabel('Belum punya HP')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('phones'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('phones'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('has_highlights')
                    ->label('Model unggulan')
                    ->placeholder('Semua merek')
                    ->trueLabel('Sudah dipilih')
                    ->falseLabel('Belum dipilih')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('highlightedPhones'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('highlightedPhones'),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->deferFilters()
            ->defaultSort('name')
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
