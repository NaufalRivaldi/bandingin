<?php

declare(strict_types=1);

namespace App\Filament\Resources\Phones\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PhonesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('brand'))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brand.name')
                    ->label('Merek')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('price')
                    ->label('Harga resmi')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('tag')
                    ->label('Label')
                    ->badge()
                    ->placeholder('—'),
                IconColumn::make('nfc')
                    ->label('NFC')
                    ->boolean(),
                IconColumn::make('ip68')
                    ->label('IP68')
                    ->boolean(),
                TextColumn::make('released_at')
                    ->label('Rilis')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('published_at')
                    ->label('Tayang')
                    ->dateTime()
                    ->placeholder('Draf')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('published_at')
                    ->label('Status tayang')
                    ->nullable()
                    ->trueLabel('Tayang')
                    ->falseLabel('Draf'),
                TernaryFilter::make('nfc'),
                TernaryFilter::make('ip68'),
            ])
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
