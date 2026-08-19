<?php

declare(strict_types=1);

namespace App\Enums;

enum ArticleCategory: string
{
    case Rilis = 'Rilis';
    case Harga = 'Harga';
    case Panduan = 'Panduan';
    case Ulasan = 'Ulasan';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
