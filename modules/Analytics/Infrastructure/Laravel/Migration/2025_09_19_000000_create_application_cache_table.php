<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('application_cache')) {
            Schema::create('application_cache', function (Blueprint $table): void {
                $table->string('cache_key', 255)->primary();
                $table->json('stats_data')->nullable();
                $table->timestamp('calculated_at')->nullable()->index();
                $table->integer('calculation_time_ms')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_cache');
    }
};
