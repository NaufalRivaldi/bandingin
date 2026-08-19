<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneListings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PhoneListingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['phone', 'store']))
            ->columns([
                TextColumn::make('phone.name')
                    ->label('HP')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('store.name')
                    ->label('Toko')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('note')
                    ->label('Catatan')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('warranty_tag')
                    ->label('Garansi')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('snapshots_count')
                    ->label('Riwayat harga')
                    ->counts('snapshots')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('store')
                    ->relationship('store', 'name')
                    ->preload(),
                SelectFilter::make('phone')
                    ->relationship('phone', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('price')
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
