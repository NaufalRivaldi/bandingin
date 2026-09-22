<?php

declare(strict_types=1);

namespace App\Filament\Resources\Stores\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StoresTable
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
                IconColumn::make('is_official')
                    ->label('Toko resmi')
                    ->boolean(),
                TextColumn::make('listings_count')
                    ->label('Jumlah listing')
                    ->counts('listings')
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
                TernaryFilter::make('is_official')
                    ->label('Toko resmi')
                    ->placeholder('Semua toko')
                    ->trueLabel('Resmi')
                    ->falseLabel('Bukan resmi'),
                TernaryFilter::make('has_listings')
                    ->label('Listing harga')
                    ->placeholder('Semua toko')
                    ->trueLabel('Punya listing')
                    ->falseLabel('Belum punya listing')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('listings'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('listings'),
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
