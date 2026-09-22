<?php

declare(strict_types=1);

namespace App\Models\Concerns;

/**
 * Indonesian date presentation for any model with a nullable `published_at`.
 *
 * Extracted so the month table lives in exactly one place rather than being
 * copied into every model that presents a date.
 */
trait FormatsIndonesianDate
{
    /** Indonesian month names, indexed by month number. */
    private const array MONTHS = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    /** "12 Agu 2026" — the compact form used on cards. */
    public function dateShort(): string
    {
        if ($this->published_at === null) {
            return '';
        }

        $month = self::MONTHS[(int) $this->published_at->format('n')];

        return $this->published_at->format('j').' '.mb_substr($month, 0, 3).' '.$this->published_at->format('Y');
    }

    /** "12 Agustus 2026" — the long form used on the article page. */
    public function dateLong(): string
    {
        if ($this->published_at === null) {
            return '';
        }

        $month = self::MONTHS[(int) $this->published_at->format('n')];

        return $this->published_at->format('j').' '.$month.' '.$this->published_at->format('Y');
    }
}
