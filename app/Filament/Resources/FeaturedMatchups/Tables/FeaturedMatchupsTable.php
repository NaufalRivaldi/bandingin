<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeaturedMatchups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FeaturedMatchupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['leftPhone', 'rightPhone']))
            ->columns([
                TextColumn::make('leftPhone.name')
                    ->label('HP kiri')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rightPhone.name')
                    ->label('HP kanan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sort')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
            ])
            ->defaultSort('sort')
            ->reorderable('sort')
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
