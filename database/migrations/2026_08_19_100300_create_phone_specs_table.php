<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phone_specs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('phone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spec_definition_id')->constrained()->cascadeOnDelete();
            $table->string('display_value');
            $table->bigInteger('comparable_value');
            $table->timestamps();

            $table->unique(['phone_id', 'spec_definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phone_specs');
    }
};
