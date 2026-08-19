<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportStatus: string
{
    case Baru = 'Baru';
    case Diproses = 'Diproses';
    case Selesai = 'Selesai';
    case Ditolak = 'Ditolak';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
