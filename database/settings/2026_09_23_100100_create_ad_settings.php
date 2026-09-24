<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('ads.enabled', false);
        $this->migrator->add('ads.frequency', 5);
        $this->migrator->add('ads.cooldown_minutes', 30);
    }
};
