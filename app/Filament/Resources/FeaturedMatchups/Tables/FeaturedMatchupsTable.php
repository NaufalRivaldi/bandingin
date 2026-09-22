<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeaturedMatchups\Tables;

use App\Models\Brand;
use App\Models\Phone;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
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
            ->filters([
                Filter::make('phone')
                    ->label('Memuat HP')
                    ->schema([
                        Select::make('phone_id')
                            ->label('HP')
                            ->options(fn (): array => Phone::orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->placeholder('Pilih HP'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['phone_id'] ?? null, fn (Builder $query, string $phoneId): Builder => $query
                            ->where(fn (Builder $sides): Builder => $sides
                                ->where('left_phone_id', $phoneId)
                                ->orWhere('right_phone_id', $phoneId))))
                    ->indicateUsing(fn (array $data): array => filled($data['phone_id'] ?? null)
                        ? ['HP: '.(Phone::find($data['phone_id'])?->name ?? $data['phone_id'])]
                        : []),
                Filter::make('brand')
                    ->label('Memuat merek')
                    ->schema([
                        Select::make('brand_id')
                            ->label('Merek')
                            ->options(fn (): array => Brand::orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->placeholder('Pilih merek'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['brand_id'] ?? null, fn (Builder $query, string $brandId): Builder => $query
                            ->where(fn (Builder $sides): Builder => $sides
                                ->whereHas('leftPhone', fn (Builder $phone): Builder => $phone->where('brand_id', $brandId))
                                ->orWhereHas('rightPhone', fn (Builder $phone): Builder => $phone->where('brand_id', $brandId)))))
                    ->indicateUsing(fn (array $data): array => filled($data['brand_id'] ?? null)
                        ? ['Merek: '.(Brand::find($data['brand_id'])?->name ?? $data['brand_id'])]
                        : []),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->deferFilters()
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
