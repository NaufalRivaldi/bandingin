<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reports\Schemas;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Triase')
                    ->columnSpanFull()
                    ->description('Satu-satunya bagian yang bisa diubah admin.')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(ReportStatus::class)
                            ->placeholder('Belum ditangani')
                            ->helperText('Kosong berarti laporan belum ditangani siapa pun.'),
                    ]),
                Section::make('Isi laporan')
                    ->columnSpanFull()
                    ->description('Dikirim pengunjung lewat halaman /lapor — ditampilkan apa adanya.')
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->label('Jenis')
                            ->options(ReportType::class)
                            ->disabled(),
                        TextInput::make('subject')
                            ->label('Subjek')
                            ->disabled(),
                        TextInput::make('email')
                            ->label('Email pelapor')
                            ->placeholder('Tidak diisi pelapor')
                            ->disabled(),
                        TextInput::make('store')
                            ->label('Toko')
                            ->placeholder('Tidak diisi pelapor')
                            ->disabled(),
                        TextInput::make('claimed_price')
                            ->label('Harga yang dilaporkan')
                            ->placeholder('Tidak diisi pelapor')
                            ->disabled(),
                        TextInput::make('product_url')
                            ->label('Tautan produk')
                            ->placeholder('Tidak diisi pelapor')
                            ->disabled(),
                        Textarea::make('note')
                            ->label('Catatan')
                            ->rows(4)
                            ->placeholder('Tidak diisi pelapor')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
