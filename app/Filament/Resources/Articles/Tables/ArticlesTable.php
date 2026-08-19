<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Tables;

use App\Enums\ArticleCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('category')
                    ->badge()
                    ->searchable(),
                TextColumn::make('read_minutes')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label('Tayang')
                    ->dateTime()
                    ->placeholder('Draf')
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
                SelectFilter::make('category')
                    ->options(ArticleCategory::class),
                TernaryFilter::make('published_at')
                    ->label('Status')
                    ->nullable()
                    ->trueLabel('Tayang')
                    ->falseLabel('Draf'),
            ])
            ->defaultSort('published_at', 'desc')
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
