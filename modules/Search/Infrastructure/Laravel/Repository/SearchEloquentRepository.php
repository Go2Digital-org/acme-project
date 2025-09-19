<?php

declare(strict_types=1);

namespace Modules\Search\Infrastructure\Laravel\Repository;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Search\Domain\Model\SearchQuery;
use Modules\Search\Domain\Model\SearchResult;
use Modules\Search\Domain\Repository\SearchRepositoryInterface;
use Modules\Search\Domain\Service\SearchEngineInterface;

class SearchEloquentRepository implements SearchRepositoryInterface
{
    public function __construct(
        private readonly SearchEngineInterface $searchEngine,
    ) {}

    public function search(SearchQuery $query): SearchResult
    {
        return $this->searchEngine->search($query);
    }

    /**
     * @return array<int, array{text: string, id: mixed, type: string}>
     */
    public function getSuggestions(string $query, string $index, int $limit = 10): array
    {
        try {
            return $this->searchEngine->suggest($index, $query, $limit);
        } catch (Exception $e) {
            Log::warning('Failed to get search suggestions', [
                'query' => $query,
                'index' => $index,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getPopularSearches(int $limit = 10): array
    {
        $searches = DB::table('search_queries')
            ->select('query', DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('query')
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        return $searches->map(fn ($item): array => [
            'term' => $item->query,
            'count' => (int) $item->count,
        ])->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function getRecentSearches(?int $userId, int $limit = 10): array
    {
        if (! $userId) {
            return [];
        }

        $searches = DB::table('search_queries')
            ->select('query', 'created_at')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $searches->map(fn ($item): array => [
            'query' => $item->query,
            'timestamp' => strtotime((string) $item->created_at),
        ])->toArray();
    }

    public function saveSearch(string $query, ?int $userId, int $resultCount): void
    {
        DB::table('search_queries')->insert([
            'query' => $query,
            'user_id' => $userId,
            'result_count' => $resultCount,
            'created_at' => now(),
        ]);
    }

    public function clearSearchHistory(?int $userId): void
    {
        if ($userId) {
            DB::table('search_queries')
                ->where('user_id', $userId)
                ->delete();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getSearchStats(): array
    {
        $total = DB::table('search_queries')->count();
        $today = DB::table('search_queries')
            ->whereDate('created_at', today())
            ->count();
        $uniqueUsers = DB::table('search_queries')
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
        $avgResults = DB::table('search_queries')
            ->avg('result_count') ?? 0;

        return [
            'total' => $total,
            'today' => $today,
            'unique_users' => $uniqueUsers,
            'avg_results' => (float) $avgResults,
        ];
    }
}
