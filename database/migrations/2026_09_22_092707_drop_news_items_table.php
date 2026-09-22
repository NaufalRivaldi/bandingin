<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('news_items');
    }

    /**
     * Recreates the shape the link-out feature used. The rows are not
     * recoverable — the owner chose to start fresh rather than backfill.
     */
    public function down(): void
    {
        Schema::create('news_items', function (Blueprint $table): void {
            $table->id();
            $table->string('external_id')->unique();
            $table->string('source')->default('tek.id');
            $table->string('channel');
            $table->string('title');
            $table->text('excerpt');
            $table->string('url')->unique();
            $table->string('image_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['channel', 'published_at']);
            $table->index('published_at');
        });
    }
};
