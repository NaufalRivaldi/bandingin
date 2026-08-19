<?php

declare(strict_types=1);

namespace App\Enums;

enum FaqTopic: string
{
    case KartuDuel = 'Kartu Duel';
    case Harga = 'Harga';
    case Skor = 'Skor';
    case Data = 'Data';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
