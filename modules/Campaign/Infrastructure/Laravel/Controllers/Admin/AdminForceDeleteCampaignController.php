<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Controllers\Admin;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Infrastructure\Laravel\Requests\Admin\AdminForceDeleteCampaignRequest;

final class AdminForceDeleteCampaignController extends Controller
{
    public function __construct(
        private readonly CampaignRepositoryInterface $campaignRepository,
    ) {}

    /**
     * Handle the incoming request to permanently delete a campaign.
     *
     * This is a dangerous administrative action that cannot be undone.
     * It removes the campaign and all associated data permanently.
     */
    public function __invoke(AdminForceDeleteCampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        try {
            $campaignTitle = $campaign->title;
            $campaignId = $campaign->id;
            $campaignCreatorId = $campaign->user_id;
            $campaignOrganizationId = $campaign->organization_id;
            $adminUserId = auth()->id();

            // Additional safety check - ensure campaign exists
            if (! $campaign->exists) {
                Log::warning('Admin attempted to force delete non-existent campaign', [
                    'campaign_id' => $campaignId,
                    'admin_user_id' => $adminUserId,
                    'title' => $campaignTitle,
                ]);

                return redirect()
                    ->back()
                    ->with('error', __('Campaign not found or has already been deleted.'));
            }

            // Audit log before deletion - critical for compliance
            Log::critical('Admin force delete campaign initiated', [
                'action' => 'admin_force_delete_campaign',
                'campaign_id' => $campaignId,
                'campaign_title' => $campaignTitle,
                'campaign_creator_id' => $campaignCreatorId,
                'campaign_organization_id' => $campaignOrganizationId,
                'admin_user_id' => $adminUserId,
                'admin_email' => auth()->user()?->email,
                'timestamp' => now()->toISOString(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Perform the permanent deletion
            $forceDeleted = $this->campaignRepository->forceDelete($campaignId);

            if ($forceDeleted) {
                Log::critical('Admin force delete campaign completed successfully', [
                    'action' => 'admin_force_delete_campaign_success',
                    'campaign_id' => $campaignId,
                    'campaign_title' => $campaignTitle,
                    'campaign_creator_id' => $campaignCreatorId,
                    'campaign_organization_id' => $campaignOrganizationId,
                    'admin_user_id' => $adminUserId,
                    'admin_email' => auth()->user()?->email,
                    'timestamp' => now()->toISOString(),
                    'ip_address' => request()->ip(),
                    'result' => 'success',
                ]);

                return redirect()
                    ->route('admin.campaigns.index')
                    ->with('success', __('Campaign ":title" has been permanently deleted. This action cannot be undone.', ['title' => $campaignTitle]));
            }

            // Force deletion failed
            Log::error('Admin force delete campaign failed - repository returned false', [
                'action' => 'admin_force_delete_campaign_failed',
                'campaign_id' => $campaignId,
                'campaign_title' => $campaignTitle,
                'admin_user_id' => $adminUserId,
                'admin_email' => auth()->user()?->email,
                'timestamp' => now()->toISOString(),
                'result' => 'failed_repository_operation',
            ]);

            return redirect()
                ->back()
                ->with('error', __('Failed to permanently delete campaign. Please try again.'));

        } catch (Exception $e) {
            // Log the exception with full context for debugging
            Log::error('Admin force delete campaign failed with exception', [
                'action' => 'admin_force_delete_campaign_exception',
                'campaign_id' => $campaign->id ?? 'unknown',
                'campaign_title' => $campaign->title ?? 'unknown',
                'admin_user_id' => auth()->id(),
                'admin_email' => auth()->user()->email ?? 'unknown',
                'error' => $e->getMessage(),
                'exception_class' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'timestamp' => now()->toISOString(),
                'ip_address' => request()->ip(),
                'result' => 'exception',
            ]);

            return redirect()
                ->back()
                ->with('error', __('Failed to permanently delete campaign due to an error. Please contact support if this persists.'));
        }
    }
}
