<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AdSettings extends Settings
{
    public bool $enabled;

    public int $frequency;

    public int $cooldown_minutes;

    public static function group(): string
    {
        return 'ads';
    }
}
