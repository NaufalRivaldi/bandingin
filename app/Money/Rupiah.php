<?php

declare(strict_types=1);

namespace App\Money;

/**
 * Rupiah has no minor unit, so amounts are whole-integer rupiah everywhere
 * and only formatted at the view edge.
 */
final class Rupiah
{
    public static function format(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
