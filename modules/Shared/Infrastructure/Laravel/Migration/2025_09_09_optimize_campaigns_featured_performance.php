<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $isMySql = Schema::getConnection()->getDriverName() === 'mysql';

        Schema::table('campaigns', function (Blueprint $table) use ($isMySql): void {
            // Add goal_percentage as a generated column if it doesn't exist
            // This eliminates the need for runtime calculations
            if (! Schema::hasColumn('campaigns', 'goal_percentage')) {
                if ($isMySql) {
                    // MySQL supports stored computed columns
                    DB::statement('
                        ALTER TABLE campaigns 
                        ADD COLUMN goal_percentage DECIMAL(5,2) AS (
                            CASE 
                                WHEN goal_amount > 0 THEN (current_amount / goal_amount * 100)
                                ELSE 0
                            END
                        ) STORED
                    ');
                } else {
                    // SQLite doesn't support stored computed columns, add regular column
                    $table->decimal('goal_percentage', 5, 2)->nullable();
                }

                // Index the computed column for fast filtering
                $table->index('goal_percentage', 'idx_campaigns_goal_percentage');
            }
        });

        Schema::table('campaigns', function (Blueprint $table): void {
            // Optimized index for the featured campaigns UNION query
            // Priority 1: Featured campaigns
            if (! $this->indexExists('campaigns', 'idx_campaigns_featured_active')) {
                $table->index(
                    ['is_featured', 'status', 'deleted_at', 'current_amount'],
                    'idx_campaigns_featured_active'
                );
            }

            // Priority 2: Near-goal campaigns (70-99% completion)
            // Uses the goal_percentage computed column
            if (! $this->indexExists('campaigns', 'idx_campaigns_near_goal')) {
                $table->index(
                    ['status', 'is_featured', 'goal_percentage', 'current_amount', 'deleted_at'],
                    'idx_campaigns_near_goal'
                );
            }

            // Covering index for the full featured campaigns query
            // Includes all columns needed to avoid table lookups
            if (! $this->indexExists('campaigns', 'idx_campaigns_featured_covering')) {
                $table->index(
                    ['deleted_at', 'status', 'is_featured', 'goal_percentage', 'current_amount', 'id'],
                    'idx_campaigns_featured_covering'
                );
            }
        });

        // Create a view for frequently accessed featured campaigns - database specific
        if ($isMySql) {
            DB::statement("
                CREATE OR REPLACE VIEW v_featured_campaigns AS
                SELECT 
                    c.*,
                    CASE 
                        WHEN c.is_featured = 1 THEN 1
                        WHEN c.goal_percentage BETWEEN 70 AND 99.99 THEN 2
                        ELSE 3
                    END as priority
                FROM campaigns c
                WHERE c.deleted_at IS NULL
                    AND c.status = 'active'
                ORDER BY priority, c.current_amount DESC
            ");

            // Force update statistics for MySQL
            DB::statement('ANALYZE TABLE campaigns');
        } else {
            // SQLite version - drop first, then create
            try {
                DB::statement('DROP VIEW IF EXISTS v_featured_campaigns');
            } catch (Exception) {
                // View might not exist, continue
            }

            DB::statement("
                CREATE VIEW v_featured_campaigns AS
                SELECT 
                    c.*,
                    CASE 
                        WHEN c.is_featured = 1 THEN 1
                        WHEN c.goal_percentage BETWEEN 70 AND 99.99 THEN 2
                        ELSE 3
                    END as priority
                FROM campaigns c
                WHERE c.deleted_at IS NULL
                    AND c.status = 'active'
                ORDER BY priority, c.current_amount DESC
            ");

            // SQLite: use ANALYZE without table name
            try {
                DB::statement('ANALYZE');
            } catch (Exception) {
                // ANALYZE might not be available, continue
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the view first
        DB::statement('DROP VIEW IF EXISTS v_featured_campaigns');

        Schema::table('campaigns', function (Blueprint $table): void {
            $this->dropIndexIfExists($table, 'campaigns', 'idx_campaigns_featured_active');
            $this->dropIndexIfExists($table, 'campaigns', 'idx_campaigns_near_goal');
            $this->dropIndexIfExists($table, 'campaigns', 'idx_campaigns_featured_covering');
            $this->dropIndexIfExists($table, 'campaigns', 'idx_campaigns_goal_percentage');
        });

        // Drop the computed column if it was added by this migration
        if (Schema::hasColumn('campaigns', 'goal_percentage')) {
            Schema::table('campaigns', function (Blueprint $table): void {
                $table->dropColumn('goal_percentage');
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $isMySql = Schema::getConnection()->getDriverName() === 'mysql';

        if ($isMySql) {
            $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);

            return count($indexes) > 0;
        }
        // SQLite: use PRAGMA index_info to check if index exists
        try {
            $info = DB::select("PRAGMA index_info({$index})");

            return count($info) > 0;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Drop an index if it exists
     */
    private function dropIndexIfExists(Blueprint $table, string $tableName, string $index): void
    {
        if ($this->indexExists($tableName, $index)) {
            $table->dropIndex($index);
        }
    }
};
