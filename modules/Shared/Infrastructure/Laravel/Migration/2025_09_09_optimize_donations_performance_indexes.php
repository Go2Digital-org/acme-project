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
        if (Schema::hasTable('donations')) {
            Schema::table('donations', function (Blueprint $table): void {
                // Composite index for the leaderboard query that joins users with donations
                // Optimizes: JOIN users ON users.id = donations.user_id
                // WHERE donations.status = 'completed' AND users.organization_id = X
                // GROUP BY users.id ORDER BY SUM(amount) DESC
                if (! $this->indexExists('donations', 'idx_donations_leaderboard')) {
                    $table->index(
                        ['user_id', 'status', 'deleted_at', 'amount'],
                        'idx_donations_leaderboard'
                    );
                }

                // Covering index for user ranking calculations
                // Optimizes subquery counting users with higher donation totals
                if (! $this->indexExists('donations', 'idx_donations_user_ranking')) {
                    $table->index(
                        ['status', 'deleted_at', 'user_id', 'amount'],
                        'idx_donations_user_ranking'
                    );
                }
            });
        }

        // Add additional index on users table for organization-based queries
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                // Composite index for organization leaderboard queries
                // Optimizes JOIN between users and donations filtered by organization
                if (! $this->indexExists('users', 'idx_users_org_status_id')) {
                    $table->index(
                        ['organization_id', 'status', 'id'],
                        'idx_users_org_status_id'
                    );
                }
            });
        }

        // Force update statistics for query optimizer
        $isMySql = Schema::getConnection()->getDriverName() === 'mysql';

        if ($isMySql) {
            if (Schema::hasTable('donations')) {
                DB::statement('ANALYZE TABLE donations');
            }
            if (Schema::hasTable('users')) {
                DB::statement('ANALYZE TABLE users');
            }
        } else {
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
        if (Schema::hasTable('donations')) {
            Schema::table('donations', function (Blueprint $table): void {
                $this->dropIndexIfExists($table, 'donations', 'idx_donations_leaderboard');
                $this->dropIndexIfExists($table, 'donations', 'idx_donations_user_ranking');
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                $this->dropIndexIfExists($table, 'users', 'idx_users_org_status_id');
            });
        }
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        // First check if the table exists
        if (! Schema::hasTable($table)) {
            return false;
        }

        $isMySql = Schema::getConnection()->getDriverName() === 'mysql';

        if ($isMySql) {
            try {
                $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]);

                return count($indexes) > 0;
            } catch (Exception) {
                return false;
            }
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
