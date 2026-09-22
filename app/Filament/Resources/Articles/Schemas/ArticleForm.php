<?php

declare(strict_types=1);

namespace App\Filament\Resources\Articles\Schemas;

use App\Enums\ArticleCategory;
use App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks\PhoneComparisonBlock;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Meta')
                    ->columnSpanFull()
                    ->description('Judul, alamat, dan jadwal tayang artikel.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->placeholder('Harga Galaxy A56 turun jelang rilis A57'),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->placeholder('harga-galaxy-a56-turun')
                            ->helperText('Dipakai di URL /berita/{slug} — mengubahnya memutus tautan lama.'),
                        Select::make('category')
                            ->label('Kategori')
                            ->options(ArticleCategory::class)
                            ->required()
                            ->placeholder('Pilih kategori'),
                        TextInput::make('read_minutes')
                            ->label('Lama baca (menit)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('4')
                            ->helperText('Perkiraan kasar: sekitar 200 kata per menit.'),
                        DateTimePicker::make('published_at')
                            ->label('Tayang sejak')
                            ->placeholder('Kosongkan untuk draf')
                            ->helperText('Kosong berarti draf.'),
                        Repeater::make('tags')
                            ->label('Tag')
                            ->simple(TextInput::make('item')
                                ->required()
                                ->placeholder('Samsung'))
                            ->defaultItems(1)
                            ->reorderable()
                            ->addActionLabel('Tambah tag'),
                    ]),
                Section::make('Isi')
                    ->columnSpanFull()
                    ->description('Ringkasan tampil di kartu daftar berita; isi artikel tampil di halaman detail.')
                    ->schema([
                        Textarea::make('excerpt')
                            ->label('Ringkasan')
                            ->required()
                            ->rows(3)
                            ->placeholder('Dua kalimat yang menjelaskan inti berita.'),
                        RichEditor::make('body')
                            ->label('Isi artikel')
                            ->required()
                            ->customBlocks([
                                PhoneComparisonBlock::class,
                            ])
                            ->helperText('Blok "Perbandingan HP" menyisipkan kartu duel di tengah artikel.'),
                    ]),
            ]);
    }
}
