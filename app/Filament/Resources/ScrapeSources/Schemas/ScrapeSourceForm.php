<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScrapeSources\Schemas;

use App\Enums\ArticleCategory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ScrapeSourceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sumber')
                    ->columnSpanFull()
                    ->description('Feed yang diambil otomatis setiap jam oleh news:import.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->placeholder('tek.id — Gadget'),
                        TextInput::make('feed_url')
                            ->label('URL feed')
                            ->required()
                            ->url()
                            ->unique(ignoreRecord: true)
                            ->placeholder('https://www.tek.id/gadget/rss'),
                        Select::make('category')
                            ->label('Kategori artikel')
                            ->options(ArticleCategory::class)
                            ->required()
                            ->helperText('Artikel dari feed ini masuk ke kategori tersebut.'),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->helperText('Matikan untuk menjeda tanpa menghapus sumbernya.'),
                    ]),
            ]);
    }
}
