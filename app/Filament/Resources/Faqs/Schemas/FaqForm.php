<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs\Schemas;

use App\Enums\FaqTopic;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Penempatan')
                    ->columnSpanFull()
                    ->description('Menentukan tab dan posisi pertanyaan di halaman /faq.')
                    ->columns(2)
                    ->schema([
                        Select::make('topic')
                            ->label('Topik')
                            ->options(FaqTopic::class)
                            ->required()
                            ->placeholder('Pilih topik'),
                        TextInput::make('sort')
                            ->label('Urutan')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->placeholder('10')
                            ->helperText('Makin kecil makin atas di dalam topiknya.'),
                    ]),
                Section::make('Isi')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('question')
                            ->label('Pertanyaan')
                            ->required()
                            ->placeholder('Dari mana harga di Bandingin berasal?'),
                        Textarea::make('answer')
                            ->label('Jawaban')
                            ->required()
                            ->rows(4)
                            ->placeholder('Jawab langsung dalam dua sampai tiga kalimat.'),
                    ]),
            ]);
    }
}
