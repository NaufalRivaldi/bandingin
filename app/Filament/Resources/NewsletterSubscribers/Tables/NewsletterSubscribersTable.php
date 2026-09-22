<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NewsletterSubscribersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Berlangganan')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('subscribed_between')
                    ->label('Tanggal berlangganan')
                    ->schema([
                        DatePicker::make('subscribed_from')
                            ->label('Berlangganan dari')
                            ->placeholder('1 Jan 2025'),
                        DatePicker::make('subscribed_until')
                            ->label('Berlangganan sampai')
                            ->placeholder('31 Des 2025'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['subscribed_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
                        ->when($data['subscribed_until'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (filled($data['subscribed_from'] ?? null)) {
                            $indicators[] = 'Berlangganan dari '.$data['subscribed_from'];
                        }

                        if (filled($data['subscribed_until'] ?? null)) {
                            $indicators[] = 'Berlangganan sampai '.$data['subscribed_until'];
                        }

                        return $indicators;
                    }),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->deferFilters()
            ->defaultSort('created_at', 'desc')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
