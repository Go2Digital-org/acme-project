<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Listeners;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Log;

/**
 * Listener to monitor and log slow database queries for performance optimization.
 */
class QueryPerformanceListener
{
    /**
     * Threshold in milliseconds for considering a query as slow.
     */
    private const SLOW_QUERY_THRESHOLD_MS = 500;

    /**
     * Handle the query executed event.
     */
    public function handle(QueryExecuted $event): void
    {
        $timeMs = $event->time;

        // Log slow queries
        if ($timeMs >= self::SLOW_QUERY_THRESHOLD_MS) {
            $this->logSlowQuery($event, $timeMs);
        }

        // In development, also track queries that could be optimized
        if (app()->environment('local', 'development')) {
            $this->analyzeQueryPattern($event, $timeMs);
        }
    }

    /**
     * Log slow query details for investigation.
     */
    private function logSlowQuery(QueryExecuted $event, float $timeMs): void
    {
        $sql = $event->sql;
        $bindings = $event->bindings;
        $connection = $event->connectionName;

        // Format the query with bindings for readability
        $formattedQuery = $this->formatQuery($sql, $bindings);

        Log::warning('Slow Query Detected', [
            'time_ms' => $timeMs,
            'time_seconds' => round($timeMs / 1000, 2),
            'connection' => $connection,
            'query' => $formattedQuery,
            'raw_sql' => $sql,
            'bindings' => $bindings,
            'backtrace' => $this->getSimplifiedBacktrace(),
        ]);
    }

    /**
     * Analyze query patterns for potential optimization opportunities.
     */
    private function analyzeQueryPattern(QueryExecuted $event, float $timeMs): void
    {
        $sql = strtolower($event->sql);
        $warnings = [];

        // Check for N+1 query patterns
        if ($this->isSelectQuery($sql)) {
            // Check for missing indexes
            if (str_contains($sql, 'where') && ! str_contains($sql, 'index') && $timeMs > 100) {
                $warnings[] = 'Query may benefit from indexing';
            }

            // Check for SELECT * usage
            if (str_contains($sql, 'select *') || str_contains($sql, 'select `campaigns`.*')) {
                $warnings[] = 'Consider selecting only required columns instead of SELECT *';
            }

            // Check for missing limits on collection queries
            if (! str_contains($sql, 'limit') && ! str_contains($sql, 'count(')) {
                $warnings[] = 'Consider adding LIMIT to prevent loading large datasets';
            }

            // Check for complex joins
            $joinCount = substr_count($sql, 'join');
            if ($joinCount > 3) {
                $warnings[] = "Query has {$joinCount} joins - consider optimizing or splitting";
            }
        }

        // Log warnings if any
        if ($warnings !== [] && app()->environment('local')) {
            Log::info('Query Optimization Opportunities', [
                'time_ms' => $timeMs,
                'warnings' => $warnings,
                'query' => $this->formatQuery($event->sql, $event->bindings),
            ]);
        }
    }

    /**
     * Format query with bindings for better readability.
     */
    /**
     * @param  array<string, mixed>  $bindings
     */
    private function formatQuery(string $sql, array $bindings): string
    {
        $formatted = $sql;

        foreach ($bindings as $binding) {
            if (is_null($binding)) {
                $value = 'NULL';
            } elseif (is_bool($binding)) {
                $value = $binding ? 'TRUE' : 'FALSE';
            } elseif (is_numeric($binding)) {
                $value = (string) $binding;
            } else {
                $value = "'" . str_replace("'", "\\'", (string) $binding) . "'";
            }
            $formatted = preg_replace('/\?/', $value, $formatted, 1) ?? $formatted;
        }

        return $formatted;
    }

    /**
     * Check if the query is a SELECT statement.
     */
    private function isSelectQuery(string $sql): bool
    {
        return str_starts_with($sql, 'select');
    }

    /**
     * Get simplified backtrace for debugging.
     */
    /**
     * @return array<int, array<string, int|string>>
     */
    private function getSimplifiedBacktrace(): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
        $simplified = [];

        foreach ($trace as $index => $frame) {
            if ($index < 3) {
                continue; // Skip framework internals
            }

            if (isset($frame['file']) && ! str_contains($frame['file'], 'vendor')) {
                $simplified[] = [
                    'file' => str_replace(base_path() . '/', '', $frame['file']),
                    'line' => $frame['line'] ?? 0,
                    'function' => $frame['function'],
                ];
            }

            if (count($simplified) >= 3) {
                break;
            }
        }

        return $simplified;
    }
}
