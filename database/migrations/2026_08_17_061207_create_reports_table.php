<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('subject');
            $table->string('email');
            $table->string('store')->nullable();
            $table->string('claimed_price')->nullable();
            $table->string('product_url')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
