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
        // Create table for persistent query result caching if it doesn't exist
        if (! Schema::hasTable('query_cache')) {
            Schema::create('query_cache', function (Blueprint $table): void {
                $table->id();
                $table->string('cache_key', 255)->unique();
                $table->string('query_hash', 64)->index();
                $table->longText('query_result');
                $table->integer('result_count')->default(0);
                $table->integer('hit_count')->default(0);
                $table->timestamp('expires_at')->index();
                $table->timestamps();

                // Composite index for efficient cache lookups
                $table->index(['cache_key', 'expires_at']);
                $table->index(['query_hash', 'expires_at']);
            });
        }

        // Add indexes to support the EloquentDashboardRepository queries
        Schema::table('donations', function (Blueprint $table): void {
            // Composite index for dashboard summary queries
            if (! $this->indexExists('donations', 'idx_donations_dashboard_summary')) {
                $table->index(
                    ['user_id', 'status', 'deleted_at', 'donated_at', 'amount'],
                    'idx_donations_dashboard_summary'
                );
            }

            // Index for campaign-based aggregations
            if (! $this->indexExists('donations', 'idx_donations_campaign_aggregation')) {
                $table->index(
                    ['campaign_id', 'status', 'deleted_at', 'amount'],
                    'idx_donations_campaign_aggregation'
                );
            }
        });

        // Add views for dashboard metrics - database specific
        $isMySql = Schema::getConnection()->getDriverName() === 'mysql';

        if ($isMySql) {
            // MySQL version with advanced date functions
            DB::statement("
                CREATE OR REPLACE VIEW v_user_donation_metrics AS
                SELECT 
                    d.user_id,
                    COUNT(DISTINCT CASE WHEN d.status = 'completed' THEN d.campaign_id END) as campaigns_supported,
                    SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END) as total_donated,
                    SUM(CASE WHEN d.status = 'completed' 
                        AND DATE(d.donated_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) 
                        THEN d.amount ELSE 0 END) as last_30_days_total,
                    MAX(CASE WHEN d.status = 'completed' THEN d.donated_at END) as last_donation_date
                FROM donations d
                WHERE d.deleted_at IS NULL
                GROUP BY d.user_id
            ");

            DB::statement("
                CREATE OR REPLACE VIEW v_organization_leaderboard AS
                SELECT 
                    u.organization_id,
                    u.id as user_id,
                    u.name,
                    SUM(d.amount) as total_donations,
                    COUNT(DISTINCT d.campaign_id) as campaigns_supported,
                    RANK() OVER (PARTITION BY u.organization_id ORDER BY SUM(d.amount) DESC) as organization_rank
                FROM users u
                INNER JOIN donations d ON u.id = d.user_id
                WHERE d.status = 'completed'
                    AND d.deleted_at IS NULL
                    AND u.status = 'active'
                GROUP BY u.organization_id, u.id, u.name
            ");

            // Force update statistics for MySQL
            DB::statement('ANALYZE TABLE donations');
            DB::statement('ANALYZE TABLE users');
            DB::statement('ANALYZE TABLE campaigns');
        } else {
            // SQLite version - drop first, then create, with compatible date functions
            try {
                DB::statement('DROP VIEW IF EXISTS v_user_donation_metrics');
            } catch (Exception) {
                // View might not exist, continue
            }

            DB::statement("
                CREATE VIEW v_user_donation_metrics AS
                SELECT 
                    d.user_id,
                    COUNT(DISTINCT CASE WHEN d.status = 'completed' THEN d.campaign_id END) as campaigns_supported,
                    SUM(CASE WHEN d.status = 'completed' THEN d.amount ELSE 0 END) as total_donated,
                    SUM(CASE WHEN d.status = 'completed' 
                        AND DATE(d.donated_at) >= DATE('now', '-30 days') 
                        THEN d.amount ELSE 0 END) as last_30_days_total,
                    MAX(CASE WHEN d.status = 'completed' THEN d.donated_at END) as last_donation_date
                FROM donations d
                WHERE d.deleted_at IS NULL
                GROUP BY d.user_id
            ");

            try {
                DB::statement('DROP VIEW IF EXISTS v_organization_leaderboard');
            } catch (Exception) {
                // View might not exist, continue
            }

            DB::statement("
                CREATE VIEW v_organization_leaderboard AS
                SELECT 
                    u.organization_id,
                    u.id as user_id,
                    u.name,
                    SUM(d.amount) as total_donations,
                    COUNT(DISTINCT d.campaign_id) as campaigns_supported,
                    RANK() OVER (PARTITION BY u.organization_id ORDER BY SUM(d.amount) DESC) as organization_rank
                FROM users u
                INNER JOIN donations d ON u.id = d.user_id
                WHERE d.status = 'completed'
                    AND d.deleted_at IS NULL
                    AND u.status = 'active'
                GROUP BY u.organization_id, u.id, u.name
            ");

            // SQLite doesn't support ANALYZE TABLE syntax, but has ANALYZE
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
        // Drop views
        DB::statement('DROP VIEW IF EXISTS v_user_donation_metrics');
        DB::statement('DROP VIEW IF EXISTS v_organization_leaderboard');

        // Drop query cache table
        Schema::dropIfExists('query_cache');

        // Drop indexes
        Schema::table('donations', function (Blueprint $table): void {
            $this->dropIndexIfExists($table, 'donations', 'idx_donations_dashboard_summary');
            $this->dropIndexIfExists($table, 'donations', 'idx_donations_campaign_aggregation');
        });
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
        // SQLite: try to drop and catch exception to check if index exists
        // This is a workaround since SQLite doesn't have SHOW INDEX
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
