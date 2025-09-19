<?php

declare(strict_types=1);

namespace Modules\Organization\Infrastructure\Laravel\Http\Resources;

use Illuminate\Http\Request;
use Modules\Organization\Application\ReadModel\OrganizationDashboardReadModel;
use Modules\Shared\Infrastructure\Laravel\Http\Resources\BaseApiResource;

class OrganizationDashboardResource extends BaseApiResource
{
    /**
     * Transform the resource into an array optimized for dashboard views.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        /** @var OrganizationDashboardReadModel $dashboard */
        $dashboard = $this->resource;
        $data = $dashboard->getData();

        $response = [
            'organization_id' => $dashboard->getOrganizationId(),
            'version' => $dashboard->getVersion(),
            'generated_at' => $dashboard->getGeneratedAt(),
        ];

        // Basic organization info
        if ($this->shouldIncludeField($request, 'organization')) {
            $response['organization'] = [
                'name' => $data['name'] ?? 'Unknown',
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'is_verified' => $data['is_verified'] ?? false,
                'logo_url' => $data['logo_url'] ?? null,
            ];
        }

        // Employee statistics (optimized aggregates)
        if ($this->shouldIncludeField($request, 'employees')) {
            $response['employees'] = [
                'total' => $data['total_employees'] ?? 0,
                'active' => $data['active_employees'] ?? 0,
                'new_this_month' => $data['new_employees_this_month'] ?? 0,
                'by_department' => $data['employees_by_department'] ?? [],
            ];
        }

        // Campaign statistics (pre-aggregated)
        if ($this->shouldIncludeField($request, 'campaigns')) {
            $response['campaigns'] = [
                'total' => $data['total_campaigns'] ?? 0,
                'active' => $data['active_campaigns'] ?? 0,
                'completed' => $data['completed_campaigns'] ?? 0,
                'draft' => $data['draft_campaigns'] ?? 0,
                'created_this_month' => $data['campaigns_created_this_month'] ?? 0,
                'by_category' => $data['campaigns_by_category'] ?? [],
                'average_duration' => round($data['average_campaign_duration'] ?? 0, 1),
            ];
        }

        // Financial overview (pre-calculated)
        if ($this->shouldIncludeField($request, 'financial')) {
            $response['financial'] = [
                'total_goal' => $this->transformMoney($data['total_fundraising_goal'] ?? 0.0, true),
                'total_raised' => $this->transformMoney($data['total_amount_raised'] ?? 0.0, true),
                'raised_this_month' => $this->transformMoney($data['total_amount_raised_this_month'] ?? 0.0, true),
                'raised_this_year' => $this->transformMoney($data['total_amount_raised_this_year'] ?? 0.0, true),
                'corporate_matching' => $this->transformMoney($data['total_corporate_match_amount'] ?? 0.0, true),
            ];
        }

        // Donation statistics (pre-aggregated)
        if ($this->shouldIncludeField($request, 'donations')) {
            $response['donations'] = [
                'total' => $data['total_donations'] ?? 0,
                'unique_donors' => $data['total_unique_donors'] ?? 0,
                'this_month' => $data['donations_this_month'] ?? 0,
                'new_donors_this_month' => $data['new_donors_this_month'] ?? 0,
                'recurring' => $data['recurring_donations'] ?? 0,
                'retention_rate' => round($data['donor_retention_rate'] ?? 0.0, 1),
            ];
        }

        // Performance metrics (cached calculations)
        if ($this->shouldIncludeField($request, 'performance')) {
            $response['performance'] = [
                'success_rate' => round($data['average_campaign_success_rate'] ?? 0.0, 1),
                'creation_velocity' => round($data['campaign_creation_velocity'] ?? 0.0, 2),
                'fundraising_velocity' => $this->transformMoney($data['fundraising_velocity'] ?? 0.0, true),
            ];
        }

        // Engagement metrics (if available)
        if ($this->shouldIncludeField($request, 'engagement')) {
            $response['engagement'] = [
                'total_views' => $data['total_campaign_views'] ?? 0,
                'total_shares' => $data['total_campaign_shares'] ?? 0,
                'total_bookmarks' => $data['total_campaign_bookmarks'] ?? 0,
            ];
        }

        // Top performers (limited list for API efficiency)
        if ($this->shouldIncludeField($request, 'top_performers')) {
            $response['top_performers'] = [
                'campaigns' => array_slice($data['top_performing_campaigns'] ?? [], 0, 5),
                'employees' => array_slice($data['most_productive_employees'] ?? [], 0, 5),
            ];
        }

        // Recent activity (limited for API efficiency)
        if ($this->shouldIncludeField($request, 'recent_activity')) {
            $response['recent_activity'] = [
                'campaigns' => array_slice($data['recent_campaigns'] ?? [], 0, 5),
                'donations' => array_slice($data['recent_donations'] ?? [], 0, 10),
                'upcoming_deadlines' => array_slice($data['upcoming_deadlines'] ?? [], 0, 5),
            ];
        }

        // Trends (chart data - only if specifically requested)
        if ($this->shouldIncludeRelation($request, 'trends')) {
            $response['trends'] = [
                'monthly_fundraising' => $data['monthly_fundraising_trend'] ?? [],
                'campaign_creation' => $data['monthly_campaign_creation_trend'] ?? [],
            ];
        }

        return $response;
    }

    /**
     * Add caching headers for dashboard data.
     */
    public function toResponse($request)
    {
        return parent::toResponse($request)->withHeaders([
            'Cache-Control' => 'private, max-age=300, s-maxage=300',
            'Vary' => 'Accept, Accept-Encoding, Authorization, X-Organization-Id',
            'X-Data-Freshness' => 'cached',
        ]);
    }

    /**
     * Add additional metadata for dashboard response.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function with($request): array
    {
        return array_merge(parent::with($request), [
            'meta' => [
                'optimized_for' => 'dashboard',
                'cache_strategy' => 'read_model',
                'data_freshness' => 'up_to_5_minutes',
                'available_fields' => [
                    'organization', 'employees', 'campaigns', 'financial',
                    'donations', 'performance', 'engagement', 'top_performers',
                    'recent_activity',
                ],
                'available_includes' => ['trends'],
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }
}
