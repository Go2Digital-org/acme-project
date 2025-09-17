<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Service;

use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Modules\Donation\Application\Command\CancelDonationCommand;
use Modules\Donation\Application\Command\CancelDonationCommandHandler;
use Modules\Donation\Application\Command\CreateDonationCommand;
use Modules\Donation\Application\Command\CreateDonationCommandHandler;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Repository\DonationRepositoryInterface;
use Modules\Shared\Domain\Contract\UserInterface;

final readonly class DonationService
{
    public function __construct(
        private CreateDonationCommandHandler $createHandler,
        private CancelDonationCommandHandler $cancelHandler,
        private DonationRepositoryInterface $repository,
    ) {}

    /**
     * Store a new donation.
     *
     * @param  array<string, mixed>  $data
     */
    public function storeDonation(array $data): Donation
    {
        /** @var UserInterface|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new InvalidArgumentException('User must be authenticated');
        }

        $command = new CreateDonationCommand(
            campaignId: (int) $data['campaign_id'],
            employeeId: $data['anonymous'] ?? false ? null : $user->getId(),
            amount: (float) $data['amount'],
            currency: $data['currency'] ?? 'EUR',
            paymentMethod: $data['payment_method'] ?? 'stripe',
            paymentGateway: $data['payment_gateway'] ?? null,
            anonymous: $data['anonymous'] ?? false,
            recurring: $data['recurring'] ?? false,
            recurringFrequency: $data['recurring_frequency'] ?? null,
            notes: $data['notes'] ?? null,
        );

        return $this->createHandler->handle($command);
    }

    /**
     * Cancel a donation.
     */
    public function cancelDonation(int $donationId): Donation
    {
        /** @var UserInterface|null $user */
        $user = Auth::user();

        if (! $user) {
            throw new InvalidArgumentException('User must be authenticated');
        }

        // Verify user can cancel this donation
        $donation = $this->repository->findById($donationId);

        if (! $donation instanceof Donation) {
            throw new InvalidArgumentException('Donation not found');
        }

        if (! $this->canManageDonation($donationId, $user)) {
            throw new InvalidArgumentException('User not authorized to cancel this donation');
        }

        $command = new CancelDonationCommand(
            donationId: $donationId,
            employeeId: $user->getId(),
        );

        return $this->cancelHandler->handle($command);
    }

    /**
     * Check if user can manage a donation.
     */
    public function canManageDonation(int $donationId, ?UserInterface $user = null): bool
    {
        /** @var UserInterface|null $user */
        $user ??= Auth::user();

        if (! $user) {
            return false;
        }

        $donation = $this->repository->findById($donationId);

        if (! $donation instanceof Donation) {
            return false;
        }
        // User can manage if they are the donor, campaign creator, or belong to same organization
        if ($donation->user_id === $user->getId()) {
            return true;
        }
        if ($donation->campaign?->user_id === $user->getId()) {
            return true;
        }

        return property_exists($user, 'organization_id') &&
            $donation->campaign?->organization_id === $user->organization_id;
    }
}
