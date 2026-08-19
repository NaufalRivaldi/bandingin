<?php

declare(strict_types=1);

namespace App\Filament\Resources\Phones\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListingsRelationManager extends RelationManager
{
    protected static string $relationship = 'listings';

    protected static ?string $title = 'Harga toko';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('store_id')
                    ->label('Toko')
                    ->relationship('store', 'name')
                    ->required(),
                TextInput::make('price')
                    ->label('Harga')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->helperText('Mengubah harga otomatis mencatat riwayat harga baru.'),
                TextInput::make('product_url')
                    ->label('Tautan produk')
                    ->url()
                    ->maxLength(2000),
                TextInput::make('note')
                    ->label('Catatan')
                    ->maxLength(255),
                TextInput::make('warranty_tag')
                    ->label('Garansi')
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('price')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('store'))
            ->columns([
                TextColumn::make('store.name')
                    ->label('Toko')
                    ->sortable(),
                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('note')
                    ->label('Catatan')
                    ->limit(40),
                TextColumn::make('warranty_tag')
                    ->label('Garansi')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('snapshots_count')
                    ->label('Riwayat harga')
                    ->counts('snapshots'),
            ])
            ->defaultSort('price')
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
