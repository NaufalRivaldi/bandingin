<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('price');
            $table->string('tag')->nullable();
            $table->boolean('nfc')->default(false);
            $table->boolean('ip68')->default(false);
            $table->string('dimensions');
            $table->string('network');
            $table->string('released_label');
            $table->date('released_at');
            $table->json('pros');
            $table->json('cons');
            $table->unsignedTinyInteger('score_performa');
            $table->unsignedTinyInteger('score_layar');
            $table->unsignedTinyInteger('score_kamera');
            $table->unsignedTinyInteger('score_baterai');
            $table->unsignedTinyInteger('score_nilai');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phones');
    }
};
