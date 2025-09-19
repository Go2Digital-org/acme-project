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
        if (! Schema::hasTable('campaigns')) {
            return;
        }

        // First, drop the existing generated column if it exists
        if (Schema::hasColumn('campaigns', 'goal_percentage')) {
            Schema::table('campaigns', function (Blueprint $table): void {
                $table->dropColumn('goal_percentage');
            });
        }

        // Now recreate it with a larger precision to handle values > 999.99
        // Using decimal(8,2) to allow for percentages up to 999,999.99%
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->decimal('goal_percentage', 8, 2)
                ->storedAs('(case when (goal_amount > 0) then LEAST(((current_amount / goal_amount) * 100), 999999.99) else 0 end)')
                ->after('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('campaigns')) {
            return;
        }

        // Drop the new column if it exists
        if (Schema::hasColumn('campaigns', 'goal_percentage')) {
            Schema::table('campaigns', function (Blueprint $table): void {
                $table->dropColumn('goal_percentage');
            });
        }

        // Recreate the original column with decimal(5,2)
        Schema::table('campaigns', function (Blueprint $table): void {
            $table->decimal('goal_percentage', 5, 2)
                ->storedAs('(case when (goal_amount > 0) then ((current_amount / goal_amount) * 100) else 0 end)')
                ->after('deleted_at');
        });
    }
};
