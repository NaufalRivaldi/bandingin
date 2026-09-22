<?php

declare(strict_types=1);

namespace App\Filament\Resources\SpecDefinitions\Tables;

use App\Models\SpecDefinition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SpecDefinitionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')
                    ->label('Key')
                    ->searchable(),
                TextColumn::make('label')
                    ->label('Label')
                    ->searchable(),
                TextColumn::make('group')
                    ->label('Grup')
                    ->badge()
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('sort')
                    ->label('Urutan')
                    ->numeric()
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
                SelectFilter::make('group')
                    ->label('Grup')
                    ->options(self::groupOptions())
                    ->multiple(),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->deferFilters()
            ->defaultSort('sort')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Groups are free text on the definition, so the options come from the
     * rows that exist rather than a fixed list.
     *
     * @return array<string, string>
     */
    private static function groupOptions(): array
    {
        return SpecDefinition::query()
            ->whereNotNull('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group', 'group')
            ->all();
    }
}
