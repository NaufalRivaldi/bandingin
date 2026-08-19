<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Schemas;

use App\Enums\ArticleCategory;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Judul')
                    ->required(),
                TextInput::make('slug')
                    ->required()
                    ->helperText('Dipakai di URL /berita/{slug}.'),
                Select::make('category')
                    ->label('Kategori')
                    ->options(ArticleCategory::class)
                    ->required(),
                TextInput::make('read_minutes')
                    ->label('Lama baca (menit)')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                DateTimePicker::make('published_at')
                    ->label('Tayang sejak')
                    ->helperText('Kosong berarti draf.'),
                Repeater::make('tags')
                    ->label('Tag')
                    ->simple(TextInput::make('item')->required())
                    ->defaultItems(1)
                    ->reorderable(),
                Textarea::make('excerpt')
                    ->label('Ringkasan')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),
                RichEditor::make('body')
                    ->label('Isi artikel')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
