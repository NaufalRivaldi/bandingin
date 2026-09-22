<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneListings\Tables;

use App\Models\PhoneListing;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PhoneListingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['phone.brand', 'store']))
            ->columns([
                TextColumn::make('phone.name')
                    ->label('HP')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone.brand.name')
                    ->label('Merek')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('phone')
                    ->label('HP')
                    ->relationship('phone', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('brand')
                    ->label('Merek')
                    ->relationship('phone.brand', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('store')
                    ->label('Toko')
                    ->relationship('store', 'name')
                    ->multiple()
                    ->preload(),
                SelectFilter::make('warranty_tag')
                    ->label('Garansi')
                    ->options(self::warrantyTagOptions())
                    ->multiple(),
                TernaryFilter::make('product_url')
                    ->label('Tautan produk')
                    ->nullable()
                    ->trueLabel('Ada tautan')
                    ->falseLabel('Tanpa tautan'),
                Filter::make('price_range')
                    ->label('Rentang harga')
                    ->schema([
                        TextInput::make('price_from')
                            ->label('Harga minimum')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('2000000'),
                        TextInput::make('price_until')
                            ->label('Harga maksimum')
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('7000000'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['price_from'] ?? null, fn (Builder $query, string $price): Builder => $query->where('price', '>=', (int) $price))
                        ->when($data['price_until'] ?? null, fn (Builder $query, string $price): Builder => $query->where('price', '<=', (int) $price)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['price_from'] ?? null)) {
                            $indicators[] = 'Harga dari Rp'.number_format((int) $data['price_from'], 0, ',', '.');
                        }

                        if (filled($data['price_until'] ?? null)) {
                            $indicators[] = 'Harga sampai Rp'.number_format((int) $data['price_until'], 0, ',', '.');
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
            ->deferFilters()
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

    /**
     * Garansi is free text on the listing, so the options come from what has
     * actually been entered rather than a lookup table.
     *
     * @return array<string, string>
     */
    private static function warrantyTagOptions(): array
    {
        return PhoneListing::query()
            ->whereNotNull('warranty_tag')
            ->distinct()
            ->orderBy('warranty_tag')
            ->pluck('warranty_tag', 'warranty_tag')
            ->all();
    }
}
