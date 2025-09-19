<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Api;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class SearchSuggestionsController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
            'limit' => 'sometimes|integer|min:1|max:10',
        ]);

        $query = $request->get('q');
        $limit = (int) $request->get('limit', 5);
        $employeeOnly = $request->boolean('employee_only', false) || $request->boolean('employeeOnly', false);

        try {
            // Get suggestions based on existing campaign titles and descriptions
            $suggestions = $this->getSuggestions($query, $limit, $employeeOnly, $request);

            return response()->json([
                'success' => true,
                'data' => [
                    'query' => $query,
                    'suggestions' => $suggestions,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get search suggestions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get search suggestions based on campaign titles and descriptions
     */
    /**
     * @return array<string, mixed>
     */
    private function getSuggestions(string $query, int $limit, bool $employeeOnly, Request $request): array
    {
        if ($employeeOnly && ! $request->user()) {
            // Employee-only requested but no user authenticated, return empty
            return [];
        }

        if ($employeeOnly && $request->user()) {
            // Get employee-specific suggestions from their campaigns
            $campaigns = $this->campaignRepository->findByUserId($request->user()->id);
        }

        if (! $employeeOnly) {
            // Get suggestions from active campaigns
            $campaigns = $this->campaignRepository->findActiveCampaigns();
        }

        // Handle case where campaigns is not set (shouldn't happen but for safety)
        if (! isset($campaigns)) {
            return [];
        }

        // Extract unique words from titles and descriptions
        $words = collect();
        $phrases = collect();

        foreach ($campaigns as $campaign) {
            $title = $campaign->getTitle();
            $description = $campaign->getDescription();

            // Add full titles as phrase suggestions
            if (stripos((string) $title, $query) !== false) {
                $phrases->push([
                    'text' => $title,
                    'type' => 'campaign_title',
                    'highlighted' => $this->highlightMatch($title, $query),
                ]);
            }

            // Extract words from titles and descriptions
            $titleWords = str_word_count(strtolower((string) $title), 1);
            $descriptionWords = str_word_count(strtolower($description ?? ''), 1);

            foreach (array_merge($titleWords, $descriptionWords) as $word) {
                if (strlen($word) > 2 && stripos($word, strtolower($query)) === 0) {
                    $words->push($word);
                }
            }
        }

        // Get unique word suggestions
        $wordSuggestions = $words->unique()
            ->filter(fn ($word): bool => stripos((string) $word, strtolower($query)) === 0)
            ->take($limit)
            ->map(fn ($word): array => [
                'text' => ucfirst((string) $word),
                'type' => 'word',
                'highlighted' => $this->highlightMatch(ucfirst((string) $word), $query),
            ])
            ->values()
            ->toArray();

        // Combine phrase and word suggestions
        $suggestions = array_merge(
            $phrases->take(max(1, intval($limit / 2)))->toArray(),
            $wordSuggestions
        );

        // Remove duplicates and limit results
        $uniqueSuggestions = collect($suggestions)
            ->unique('text')
            ->take($limit)
            ->values()
            ->toArray();

        return $uniqueSuggestions;
    }

    /**
     * Highlight matching text in suggestions
     */
    private function highlightMatch(string $text, string $query): string
    {
        return preg_replace(
            '/(' . preg_quote($query, '/') . ')/i',
            '<strong>$1</strong>',
            $text
        ) ?? $text;
    }
}
