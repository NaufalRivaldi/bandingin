<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('phone_listing_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price');
            $table->timestamp('recorded_at');

            $table->index(['phone_listing_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_snapshots');
    }
};
