<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('phone_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('price');
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['phone_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_variants');
    }
};
