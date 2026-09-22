<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Tables;

use App\Enums\ArticleCategory;
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

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(60),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->searchable(),
                TextColumn::make('read_minutes')
                    ->label('Lama baca')
                    ->numeric()
                    ->suffix(' mnt')
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
                    ->label('Kategori')
                    ->options(ArticleCategory::class)
                    ->multiple(),
                TernaryFilter::make('published_at')
                    ->label('Status')
                    ->nullable()
                    ->trueLabel('Tayang')
                    ->falseLabel('Draf'),
                Filter::make('published_between')
                    ->label('Periode tayang')
                    ->schema([
                        DatePicker::make('published_from')
                            ->label('Tayang dari')
                            ->placeholder('1 Jan 2025'),
                        DatePicker::make('published_until')
                            ->label('Tayang sampai')
                            ->placeholder('31 Des 2025'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['published_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('published_at', '>=', $date))
                        ->when($data['published_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('published_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['published_from'] ?? null)) {
                            $indicators[] = 'Tayang dari '.$data['published_from'];
                        }

                        if (filled($data['published_until'] ?? null)) {
                            $indicators[] = 'Tayang sampai '.$data['published_until'];
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->deferFilters()
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
