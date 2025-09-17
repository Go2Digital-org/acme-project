<?php

declare(strict_types=1);

namespace Modules\Organization\Domain\Service;

use DomainException;
use Exception;
use InvalidArgumentException;
use Modules\Organization\Domain\Event\OrganizationActivatedEvent;
use Modules\Organization\Domain\Event\OrganizationCreatedEvent;
use Modules\Organization\Domain\Event\OrganizationDeactivatedEvent;
use Modules\Organization\Domain\Event\OrganizationVerifiedEvent;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Domain\Repository\OrganizationRepositoryInterface;
use Modules\Organization\Domain\Specification\OrganizationVerificationSpecification;
use Modules\Shared\Domain\Event\EventBusInterface;
use Psr\Log\LoggerInterface;

/**
 * Domain service for organization management with event publishing
 *
 * This service demonstrates how to properly publish domain events
 * after state changes in domain operations.
 */
class OrganizationManagementService
{
    public function __construct(
        private readonly OrganizationRepositoryInterface $organizationRepository,
        private readonly EventBusInterface $eventBus,
        private readonly LoggerInterface $logger,
        private readonly OrganizationVerificationSpecification $verificationSpecification
    ) {}

    /**
     * Create a new organization and publish creation event
     */
    public function createOrganization(
        string $name,
        string $email,
        ?string $category = null,
        ?string $description = null
    ): Organization {
        $this->logger->info('Creating new organization', [
            'name' => $name,
            'email' => $email,
            'category' => $category,
        ]);

        // Create the organization
        $organization = $this->organizationRepository->create([
            'name' => $name,
            'email' => $email,
            'category' => $category,
            'description' => $description,
            'status' => 'pending_verification',
            'verified_at' => null,
            'created_at' => now(),
        ]);

        // Publish domain event after successful creation
        $organizationName = is_array($organization->name)
            ? ($organization->name['en'] ?? (reset($organization->name) ?: ''))
            : (string) $organization->name;

        $event = new OrganizationCreatedEvent(
            organizationId: $organization->id,
            name: $organizationName,
            category: $organization->category ? (string) $organization->category : null
        );

        $this->eventBus->publishAsync($event);

        $this->logger->info('Organization created successfully', [
            'organization_id' => $organization->id,
            'name' => $organization->name,
        ]);

        return $organization;
    }

    /**
     * Verify an organization and publish verification event
     */
    public function verifyOrganization(int $organizationId, int $verifiedByUserId): Organization
    {
        $organization = $this->findOrganizationOrFail($organizationId);

        // Use specification to check eligibility for verification
        if (! $this->verificationSpecification->isSatisfiedBy($organization)) {
            throw new DomainException("Organization {$organizationId} does not meet verification requirements");
        }

        $this->logger->info('Verifying organization', [
            'organization_id' => $organizationId,
            'verified_by' => $verifiedByUserId,
        ]);

        // Update organization status
        $this->organizationRepository->updateById($organizationId, [
            'status' => 'verified',
            'verified_at' => now(),
            'verified_by' => $verifiedByUserId,
        ]);

        // Refresh the model
        $organization = $this->findOrganizationOrFail($organizationId, 'after verification update');

        // Publish domain event after successful verification
        $event = new OrganizationVerifiedEvent(
            organizationId: $organization->id,
            verifiedBy: $verifiedByUserId,
            verifiedAt: now()
        );

        $this->eventBus->publishAsync($event);

        $this->logger->info('Organization verified successfully', [
            'organization_id' => $organization->id,
            'verified_by' => $verifiedByUserId,
        ]);

        return $organization;
    }

    /**
     * Activate an organization and publish activation event
     */
    public function activateOrganization(int $organizationId): Organization
    {
        $organization = $this->findOrganizationOrFail($organizationId);

        if (! $organization->is_verified) {
            throw new DomainException("Cannot activate unverified organization {$organizationId}");
        }

        if ($organization->isActive()) {
            throw new DomainException("Organization {$organizationId} is already active");
        }

        $this->logger->info('Activating organization', [
            'organization_id' => $organizationId,
        ]);

        // Update organization status
        $this->organizationRepository->updateById($organizationId, [
            'status' => 'active',
            'activated_at' => now(),
        ]);

        // Refresh the model
        $organization = $this->findOrganizationOrFail($organizationId, 'after activation update');

        // Publish domain event after successful activation
        $event = new OrganizationActivatedEvent(
            organizationId: $organization->id,
            activatedAt: now()
        );

        $this->eventBus->publish($event); // Synchronous for immediate processing

        $this->logger->info('Organization activated successfully', [
            'organization_id' => $organization->id,
        ]);

        return $organization;
    }

    /**
     * Deactivate an organization and publish deactivation event
     */
    public function deactivateOrganization(int $organizationId, string $reason = 'Administrative action'): Organization
    {
        $organization = $this->findOrganizationOrFail($organizationId);

        if (! $organization->isActive()) {
            throw new DomainException("Organization {$organizationId} is not currently active");
        }

        $this->logger->info('Deactivating organization', [
            'organization_id' => $organizationId,
            'reason' => $reason,
        ]);

        // Update organization status
        $this->organizationRepository->updateById($organizationId, [
            'status' => 'inactive',
            'deactivated_at' => now(),
            'deactivation_reason' => $reason,
        ]);

        // Refresh the model
        $organization = $this->findOrganizationOrFail($organizationId, 'after deactivation update');

        // Publish domain event after successful deactivation
        $event = new OrganizationDeactivatedEvent(
            organizationId: $organization->id,
            reason: $reason,
            deactivatedAt: now()
        );

        $this->eventBus->publishAsync($event); // Async for background processing

        $this->logger->warning('Organization deactivated', [
            'organization_id' => $organization->id,
            'reason' => $reason,
        ]);

        return $organization;
    }

    /**
     * Bulk process organization status changes with event publishing
     *
     * @param  array<int>  $organizationIds
     * @return array<Organization>
     */
    public function bulkUpdateStatus(array $organizationIds, string $status, ?string $reason = null): array
    {
        $this->logger->info('Bulk updating organization status', [
            'organization_count' => count($organizationIds),
            'new_status' => $status,
            'reason' => $reason,
        ]);

        $updatedOrganizations = [];

        foreach ($organizationIds as $organizationId) {
            try {
                $organization = match ($status) {
                    'active' => $this->activateOrganization($organizationId),
                    'inactive' => $this->deactivateOrganization($organizationId, $reason ?? 'Bulk operation'),
                    default => throw new InvalidArgumentException("Unsupported status: {$status}")
                };

                $updatedOrganizations[] = $organization;

            } catch (Exception $exception) {
                $this->logger->error('Failed to update organization status', [
                    'organization_id' => $organizationId,
                    'status' => $status,
                    'error' => $exception->getMessage(),
                ]);

                // Continue with other organizations
                continue;
            }
        }

        $this->logger->info('Bulk status update completed', [
            'requested_count' => count($organizationIds),
            'successful_count' => count($updatedOrganizations),
            'status' => $status,
        ]);

        return $updatedOrganizations;
    }

    /**
     * Find organization by ID or throw exception
     */
    private function findOrganizationOrFail(int $organizationId, ?string $context = null): Organization
    {
        $organization = $this->organizationRepository->findById($organizationId);

        if (! $organization instanceof Organization) {
            $message = "Organization with ID {$organizationId} not found";
            if ($context) {
                $message .= " {$context}";
            }
            throw new InvalidArgumentException($message);
        }

        return $organization;
    }
}
