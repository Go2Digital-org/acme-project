<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Modules\Campaign\Application\Query\GetUserCampaignStatsQuery;
use Modules\Campaign\Application\Query\GetUserCampaignStatsQueryHandler;
use Modules\Campaign\Application\Query\ListUserCampaignsQuery;
use Modules\Campaign\Application\Query\ListUserCampaignsQueryHandler;
use Modules\Campaign\Domain\Service\UserCampaignManagementService;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

class MyCampaignsWebController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private ListUserCampaignsQueryHandler $listCampaignsHandler,
        private GetUserCampaignStatsQueryHandler $statsHandler,
        private UserCampaignManagementService $managementService,
    ) {}

    public function __invoke(Request $request): View
    {

        $userId = $this->getAuthenticatedUserId($request);

        // Get query parameters
        $page = (int) ($request->get('page', 1));
        $perPage = (int) ($request->get('per_page', 12));
        $sortBy = (string) ($request->get('sort_by', 'created_at'));
        $sortOrder = (string) ($request->get('sort_order', 'desc'));

        // Build filters from request
        $filters = array_filter([
            'status' => $request->get('status'),
            'search' => $request->get('search'),
            'filter' => $request->get('filter'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'show_deleted' => $request->boolean('show_deleted'),
        ], fn ($value): bool => $value !== null && $value !== '');

        // Get paginated campaigns
        $query = new ListUserCampaignsQuery(
            userId: $userId,
            page: $page,
            perPage: $perPage,
            filters: $filters,
            sortBy: $sortBy,
            sortOrder: $sortOrder,
        );
        $campaigns = $this->listCampaignsHandler->handle($query);

        // Get user campaign statistics
        $statsQuery = new GetUserCampaignStatsQuery($userId);

        $stats = $this->statsHandler->handle($statsQuery);
        // Get campaigns needing attention
        $needingAttention = $this->managementService->getCampaignsNeedingAttention($userId);

        return view('campaigns.my-campaigns', [
            'campaigns' => $campaigns,
            'stats' => $stats,
            'needingAttention' => $needingAttention,
            'currentFilters' => $filters,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
        ]);
    }
}
