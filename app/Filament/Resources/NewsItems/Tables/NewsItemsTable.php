<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsItems\Tables;

use App\News\TekChannel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NewsItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_url')
                    ->label('Gambar')
                    ->square()
                    ->toggleable(),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->wrap()
                    ->limit(80)
                    ->url(fn ($record): string => $record->url, shouldOpenInNewTab: true),
                TextColumn::make('channel')
                    ->label('Kanal')
                    ->badge()
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Sumber')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->label('Terbit')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Diambil')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label('Kanal')
                    ->options(array_combine(
                        array_column(TekChannel::cases(), 'value'),
                        array_column(TekChannel::cases(), 'value'),
                    )),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->deferFilters()
            ->defaultSort('published_at', 'desc')
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
