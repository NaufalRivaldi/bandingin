<?php

declare(strict_types=1);

namespace App\Filament\Resources\Faqs\Schemas;

use App\Enums\FaqTopic;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('topic')
                    ->label('Topik')
                    ->options(FaqTopic::class)
                    ->required(),
                TextInput::make('sort')
                    ->label('Urutan')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('question')
                    ->label('Pertanyaan')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('answer')
                    ->label('Jawaban')
                    ->required()
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }
}
