<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScrapeSources\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ScrapeSourcesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('last_run_at')
                    ->label('Terakhir jalan')
                    ->dateTime()
                    ->placeholder('Belum pernah')
                    ->sortable(),
                TextColumn::make('last_error')
                    ->label('Kesalahan terakhir')
                    ->wrap()
                    ->limit(60)
                    ->placeholder('—')
                    ->color('danger'),
                TextColumn::make('feed_url')
                    ->label('URL feed')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Aktif'),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->deferFilters()
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
