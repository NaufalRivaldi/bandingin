<?php

declare(strict_types=1);

namespace App\Filament\Resources\SpecDefinitions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SpecDefinitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Definisi')
                    ->columnSpanFull()
                    ->description('Satu baris spesifikasi yang bisa diisi tiap HP.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('key')
                            ->label('Key')
                            ->required()
                            ->disabledOn('edit')
                            ->placeholder('baterai')
                            ->helperText('PhoneSeeder dan CompareDuel mencari spesifikasi lewat key ini — mengubahnya memutus keduanya.'),
                        TextInput::make('label')
                            ->label('Label')
                            ->required()
                            ->placeholder('Baterai')
                            ->helperText('Nama baris seperti yang dibaca pengunjung.'),
                        TextInput::make('group')
                            ->label('Grup')
                            ->placeholder('Performa')
                            ->helperText('Mengelompokkan baris di tabel spesifikasi. Boleh dikosongkan.'),
                        TextInput::make('sort')
                            ->label('Urutan')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->placeholder('10')
                            ->helperText('Makin kecil makin atas.'),
                    ]),
            ]);
    }
}
