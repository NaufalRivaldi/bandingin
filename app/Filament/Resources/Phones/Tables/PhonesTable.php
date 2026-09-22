<?php

declare(strict_types=1);

namespace App\Filament\Resources\Phones\Tables;

use App\Models\Phone;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
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
                    ->label('Merek')
                    ->relationship('brand', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                SelectFilter::make('price_band')
                    ->label('Rentang harga')
                    ->options(self::priceBandOptions())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->inPriceBands([$data['value']])
                        : $query),
                TernaryFilter::make('published_at')
                    ->label('Status tayang')
                    ->nullable()
                    ->trueLabel('Tayang')
                    ->falseLabel('Draf'),
                TernaryFilter::make('tag')
                    ->label('Label promo')
                    ->nullable()
                    ->trueLabel('Berlabel')
                    ->falseLabel('Tanpa label'),
                TernaryFilter::make('nfc')
                    ->label('NFC'),
                TernaryFilter::make('ip68')
                    ->label('IP68'),
                Filter::make('released_at')
                    ->label('Tanggal rilis')
                    ->schema([
                        DatePicker::make('released_from')
                            ->label('Rilis dari')
                            ->placeholder('1 Jan 2025'),
                        DatePicker::make('released_until')
                            ->label('Rilis sampai')
                            ->placeholder('31 Des 2025'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['released_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('released_at', '>=', $date))
                        ->when($data['released_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('released_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['released_from'] ?? null)) {
                            $indicators[] = 'Rilis dari '.$data['released_from'];
                        }

                        if (filled($data['released_until'] ?? null)) {
                            $indicators[] = 'Rilis sampai '.$data['released_until'];
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(3)
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

    /**
     * Band labels come from Phone::PRICE_BANDS so the admin filter and the
     * public catalogue can never drift apart.
     *
     * @return array<string, string>
     */
    private static function priceBandOptions(): array
    {
        return array_map(fn (array $band): string => $band['label'], Phone::PRICE_BANDS);
    }
}
