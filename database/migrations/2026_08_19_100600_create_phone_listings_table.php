<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_listings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('phone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price');
            $table->string('product_url')->nullable();
            $table->string('note')->nullable();
            $table->string('warranty_tag')->nullable();
            $table->timestamps();

            $table->unique(['phone_id', 'store_id']);
            $table->index(['phone_id', 'price']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_listings');
    }
};
