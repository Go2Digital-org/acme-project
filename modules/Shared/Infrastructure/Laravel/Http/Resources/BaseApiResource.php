<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseApiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        // Default implementation - should be overridden by child classes
        /** @phpstan-ignore-next-line return.type */
        return parent::toArray($request);
    }

    /**
     * Create a paginated resource response with optimized structure.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function toResponse($request)
    {
        return parent::toResponse($request)->withHeaders([
            'Cache-Control' => 'public, max-age=300, s-maxage=300',
            'Vary' => 'Accept, Accept-Encoding, Authorization',
            'ETag' => '"' . md5(json_encode($this->resource) . $request->get('page', 1)) . '"',
        ]);
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function with($request)
    {
        return [
            'meta' => [
                'timestamp' => now()->toISOString(),
                'api_version' => 'v1',
            ],
        ];
    }

    /**
     * Check if a field should be included based on request fields parameter.
     */
    protected function shouldIncludeField(Request $request, string $fieldName): bool
    {
        $fields = $request->get('fields', []);

        if (empty($fields)) {
            return true;
        }

        return in_array($fieldName, (array) $fields, true);
    }

    /**
     * Get requested relationship includes from query parameters.
     *
     * @return array<string>
     */
    protected function getRequestedIncludes(Request $request): array
    {
        $include = $request->get('include', '');

        if (empty($include)) {
            return [];
        }

        return explode(',', (string) $include);
    }

    /**
     * Check if relationship should be loaded.
     */
    protected function shouldIncludeRelation(Request $request, string $relationName): bool
    {
        return in_array($relationName, $this->getRequestedIncludes($request), true);
    }

    /**
     * Transform money value with formatting options.
     *
     * @return array<string, mixed>
     */
    protected function transformMoney(float $amount, bool $compact = false): array
    {
        $formatted = number_format($amount, 2);
        $short = $this->formatMoneyShort($amount);

        $data = [
            'raw' => $amount,
            'formatted' => '$' . $formatted,
        ];

        if (! $compact) {
            $data['formatted_short'] = $short;
            $data['currency'] = 'USD';
        }

        return $data;
    }

    /**
     * Format money in short form (e.g., $1.2K, $1.5M).
     */
    private function formatMoneyShort(float $amount): string
    {
        if ($amount >= 1000000) {
            return '$' . number_format($amount / 1000000, 1) . 'M';
        }

        if ($amount >= 1000) {
            return '$' . number_format($amount / 1000, 1) . 'K';
        }

        return '$' . number_format($amount, 0);
    }

    /**
     * Transform date with multiple format options.
     *
     * @return array<string, mixed>|null
     */
    protected function transformDate(mixed $date, bool $compact = false): ?array
    {
        if (! $date) {
            return null;
        }

        $carbon = Carbon::parse($date);

        $data = [
            'iso' => $carbon->toISOString(),
            'formatted' => $carbon->format('M j, Y'),
        ];

        if (! $compact) {
            $data['relative'] = $carbon->diffForHumans();
            $data['timestamp'] = $carbon->timestamp;
        }

        return $data;
    }

    /**
     * Apply conditional loading based on request parameters.
     */
    protected function whenLoadedRelation(string $relationship, ?callable $callback = null): mixed
    {
        if (! $this->resource->relationLoaded($relationship)) {
            return null; // Return null when relationship is not loaded
        }

        return $callback ? $callback($this->resource->{$relationship}) : $this->resource->{$relationship};
    }
}
