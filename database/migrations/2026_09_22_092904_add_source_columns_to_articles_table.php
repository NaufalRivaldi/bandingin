<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            // Null source means a human wrote it. The importer is scoped to
            // rows where this is set and can never touch the others.
            $table->string('source')->nullable()->after('published_at');
            $table->string('source_url')->nullable()->unique()->after('source');
            $table->string('external_id')->nullable()->unique()->after('source_url');
            $table->string('image_url')->nullable()->after('excerpt');

            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropIndex(['source']);
            $table->dropUnique(['source_url']);
            $table->dropUnique(['external_id']);
            $table->dropColumn(['source', 'source_url', 'external_id', 'image_url']);
        });
    }
};
