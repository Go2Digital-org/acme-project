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
        // Only proceed if campaigns table exists
        if (! Schema::hasTable('campaigns')) {
            return;
        }

        Schema::table('campaigns', function (Blueprint $table): void {
            $table->unsignedInteger('views_count')->default(0)->after('donations_count');
            $table->unsignedInteger('shares_count')->default(0)->after('views_count');

            // Add indexes for performance
            $table->index(['views_count'], 'campaigns_views_count_index');
            $table->index(['shares_count'], 'campaigns_shares_count_index');
            $table->index(['status', 'views_count'], 'campaigns_status_views_index');
            $table->index(['status', 'shares_count'], 'campaigns_status_shares_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only proceed if campaigns table exists
        if (! Schema::hasTable('campaigns')) {
            return;
        }

        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropIndex('campaigns_views_count_index');
            $table->dropIndex('campaigns_shares_count_index');
            $table->dropIndex('campaigns_status_views_index');
            $table->dropIndex('campaigns_status_shares_index');

            $table->dropColumn(['views_count', 'shares_count']);
        });
    }
};
