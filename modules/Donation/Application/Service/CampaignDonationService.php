<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Service;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Domain\Service\CampaignDonationServiceInterface;

class CampaignDonationService implements CampaignDonationServiceInterface
{
    public function __construct(
        private readonly DonationRepositoryInterface $donationRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getDonationsForCampaign(int $campaignId, Request $request): array
    {
        $perPage = $request->integer('per_page', 15);
        $page = $request->integer('page', 1);
        $status = $request->string('status')->toString();
        $paymentMethod = $request->string('payment_method')->toString();
        $sortBy = $request->string('sort_by', 'donated_at')->toString();
        $sortDirection = $request->string('sort_direction', 'desc')->toString();
        $anonymous = $request->boolean('anonymous');

        $filters = array_filter([
            'campaign_id' => $campaignId,
            'status' => $status !== '' ? $status : null,
            'payment_method' => $paymentMethod !== '' ? $paymentMethod : null,
            'anonymous' => $anonymous,
        ], fn ($value): bool => $value !== null);

        // Use paginate method since findByCampaign only takes campaignId
        $donations = $this->donationRepository->paginate($page, $perPage, $filters, $sortBy, $sortDirection);

        // Transform using donation status service, handling anonymous donations
        /** @var Collection<int, array<string, mixed>> $transformedDonations */
        $transformedDonations = collect($donations->items())->map(function (Donation $donation) use ($request): array {
            $isCurrentUser = $request->user()?->id === $donation->user_id;

            return [
                'id' => $donation->id,
                'amount' => $donation->amount,
                'currency' => $donation->currency ?? 'USD',
                'status' => $donation->status->getLabel(),
                'payment_method' => $donation->payment_method?->getLabel() ?? 'Unknown',
                'donated_at' => $donation->donated_at->toISOString(),
                'is_anonymous' => $donation->anonymous,
                'is_recurring' => $donation->recurring,
                // Hide employee info for anonymous donations (unless viewing own)
                'employee' => ($donation->anonymous && ! $isCurrentUser)
                    ? ['name' => 'Anonymous Donor']
                    : [
                        'id' => $donation->user?->id,
                        'name' => $donation->user->name ?? 'Unknown',
                        'department' => $donation->user->department ?? null,
                    ],
                'message' => $donation->notes,
                'matching_multiplier' => $donation->matching_multiplier ?? 1.0,
            ];
        })->toArray();

        return [
            'donations' => $transformedDonations,
            'pagination' => [
                'current_page' => $donations->currentPage(),
                'last_page' => $donations->lastPage(),
                'per_page' => $donations->perPage(),
                'total' => $donations->total(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getCampaignDonationAnalytics(int $campaignId): array
    {
        $donations = collect($this->donationRepository->findByCampaign($campaignId));

        $totalDonations = $donations->count();
        $totalAmount = $donations->where('status', 'completed')->sum('amount');
        $completedDonations = $donations->where('status', 'completed')->count();
        $pendingDonations = $donations->where('status', 'pending')->count();
        $failedDonations = $donations->where('status', 'failed')->count();

        // Payment methods breakdown
        $paymentMethods = $donations->groupBy('payment_method')
            ->map(fn ($group): int => $group->count())
            ->toArray();

        // Monthly trends (last 12 months)
        $monthlyTrends = $donations->where('status', 'completed')
            ->groupBy(fn ($donation) => $donation->donated_at->format('Y-m'))
            ->map(fn ($group): array => [
                'count' => $group->count(),
                'amount' => $group->sum('amount'),
            ])
            ->sortKeys()
            ->take(12)
            ->toArray();

        // Top donations (excluding anonymous ones for privacy)
        $topDonations = $donations->where('status', 'completed')
            ->where('is_anonymous', false)
            ->sortByDesc('amount')
            ->take(5)
            ->map(fn ($donation): array => [
                'amount' => $donation->amount,
                'employee_name' => $donation->user->name ?? 'Unknown',
                'donated_at' => $donation->donated_at->toDateString(),
            ])
            ->values()
            ->toArray();

        return [
            'overview' => [
                'total_donations' => $totalDonations,
                'total_amount' => $totalAmount,
                'completed_donations' => $completedDonations,
                'pending_donations' => $pendingDonations,
                'failed_donations' => $failedDonations,
                'average_donation' => $totalDonations > 0 ? round($totalAmount / $totalDonations, 2) : 0,
            ],
            'payment_methods' => $paymentMethods,
            'monthly_trends' => $monthlyTrends,
            'top_donations' => $topDonations,
        ];
    }
}
