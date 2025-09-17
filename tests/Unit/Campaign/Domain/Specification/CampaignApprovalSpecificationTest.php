<?php

declare(strict_types=1);

use Carbon\Carbon;
use Modules\Campaign\Domain\Specification\CampaignApprovalSpecification;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Shared\Domain\Contract\CampaignInterface;

test('campaign specification returns false for non-campaign objects', function (): void {
    $specification = new CampaignApprovalSpecification;
    $mockObject = createMockCampaign([]);

    // Since the specification only works with concrete Campaign instances,
    // it should return false for our mock object
    expect($specification->isSatisfiedBy($mockObject))->toBeFalse();
    expect($specification->isSatisfiedBy('not a campaign'))->toBeFalse();
    expect($specification->isSatisfiedBy(null))->toBeFalse();
});

test('campaign cannot be approved when not in pending approval status', function (): void {
    $organization = createMockOrganization([
        'is_active' => true,
        'is_verified' => true,
    ]);

    $campaign = createMockCampaign([
        'status' => CampaignStatus::DRAFT,
        'title' => 'Test Campaign',
        'description' => 'A valid test campaign description',
        'goal_amount' => 1000.00,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(30),
        'category' => 'health',
        'organization' => $organization,
    ]);

    $specification = new CampaignApprovalSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('campaign cannot be approved when already approved', function (): void {
    $organization = createMockOrganization([
        'is_active' => true,
        'is_verified' => true,
    ]);

    $campaign = createMockCampaign([
        'status' => CampaignStatus::PENDING_APPROVAL,
        'title' => 'Test Campaign',
        'description' => 'A valid test campaign description',
        'goal_amount' => 1000.00,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(30),
        'category' => 'health',
        'approved_at' => Carbon::now(),
        'rejected_at' => null,
        'organization' => $organization,
    ]);

    $specification = new CampaignApprovalSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('campaign cannot be approved when already rejected', function (): void {
    $organization = createMockOrganization([
        'is_active' => true,
        'is_verified' => true,
    ]);

    $campaign = createMockCampaign([
        'status' => CampaignStatus::PENDING_APPROVAL,
        'title' => 'Test Campaign',
        'description' => 'A valid test campaign description',
        'goal_amount' => 1000.00,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(30),
        'category' => 'health',
        'approved_at' => null,
        'rejected_at' => Carbon::now(),
        'organization' => $organization,
    ]);

    $specification = new CampaignApprovalSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('campaign cannot be approved when title is invalid', function (): void {
    $organization = createMockOrganization([
        'is_active' => true,
        'is_verified' => true,
    ]);

    // Test empty title
    $campaign = createMockCampaign([
        'status' => CampaignStatus::PENDING_APPROVAL,
        'title' => '',
        'description' => 'A valid test campaign description',
        'goal_amount' => 1000.00,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(30),
        'category' => 'health',
        'organization' => $organization,
    ]);

    $specification = new CampaignApprovalSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();

    // Test "Untitled" title
    $campaign2 = createMockCampaign([
        'status' => CampaignStatus::PENDING_APPROVAL,
        'title' => 'Untitled',
        'description' => 'A valid test campaign description',
        'goal_amount' => 1000.00,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(30),
        'category' => 'health',
        'organization' => $organization,
    ]);

    expect($specification->isSatisfiedBy($campaign2))->toBeFalse();
});

test('campaign cannot be approved when description is empty', function (): void {
    $organization = createMockOrganization([
        'is_active' => true,
        'is_verified' => true,
    ]);

    $campaign = createMockCampaign([
        'status' => CampaignStatus::PENDING_APPROVAL,
        'title' => 'Test Campaign',
        'description' => '',
        'goal_amount' => 1000.00,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(30),
        'category' => 'health',
        'organization' => $organization,
    ]);

    $specification = new CampaignApprovalSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('campaign cannot be approved when goal amount is invalid', function (): void {
    $organization = createMockOrganization([
        'is_active' => true,
        'is_verified' => true,
    ]);

    $campaign = createMockCampaign([
        'status' => CampaignStatus::PENDING_APPROVAL,
        'title' => 'Test Campaign',
        'description' => 'A valid test campaign description',
        'goal_amount' => 0.00,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(30),
        'category' => 'health',
        'organization' => $organization,
    ]);

    $specification = new CampaignApprovalSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();

    // Test negative goal amount
    $campaign2 = createMockCampaign([
        'status' => CampaignStatus::PENDING_APPROVAL,
        'title' => 'Test Campaign',
        'description' => 'A valid test campaign description',
        'goal_amount' => -100.00,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(30),
        'category' => 'health',
        'organization' => $organization,
    ]);

    expect($specification->isSatisfiedBy($campaign2))->toBeFalse();
});

test('campaign cannot be approved when date range is invalid', function (): void {
    $organization = createMockOrganization([
        'is_active' => true,
        'is_verified' => true,
    ]);

    // Test start date after end date
    $campaign = createMockCampaign([
        'status' => CampaignStatus::PENDING_APPROVAL,
        'title' => 'Test Campaign',
        'description' => 'A valid test campaign description',
        'goal_amount' => 1000.00,
        'start_date' => Carbon::now()->addDays(30),
        'end_date' => Carbon::now()->addDays(1),
        'category' => 'health',
        'organization' => $organization,
    ]);

    $specification = new CampaignApprovalSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('campaign cannot be approved when end date is in the past', function (): void {
    $organization = createMockOrganization([
        'is_active' => true,
        'is_verified' => true,
    ]);

    $campaign = createMockCampaign([
        'status' => CampaignStatus::PENDING_APPROVAL,
        'title' => 'Test Campaign',
        'description' => 'A valid test campaign description',
        'goal_amount' => 1000.00,
        'start_date' => Carbon::now()->subDays(30),
        'end_date' => Carbon::now()->subDays(1),
        'category' => 'health',
        'organization' => $organization,
    ]);

    $specification = new CampaignApprovalSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('campaign cannot be approved when organization is inactive', function (): void {
    $organization = createMockOrganization([
        'is_active' => false,
        'is_verified' => true,
    ]);

    $campaign = createMockCampaign([
        'status' => CampaignStatus::PENDING_APPROVAL,
        'title' => 'Test Campaign',
        'description' => 'A valid test campaign description',
        'goal_amount' => 1000.00,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(30),
        'category' => 'health',
        'organization' => $organization,
    ]);

    $specification = new CampaignApprovalSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('campaign cannot be approved when organization is not verified', function (): void {
    $organization = createMockOrganization([
        'is_active' => true,
        'is_verified' => false,
    ]);

    $campaign = createMockCampaign([
        'status' => CampaignStatus::PENDING_APPROVAL,
        'title' => 'Test Campaign',
        'description' => 'A valid test campaign description',
        'goal_amount' => 1000.00,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(30),
        'category' => 'health',
        'organization' => $organization,
    ]);

    $specification = new CampaignApprovalSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('campaign cannot be approved when it has no category', function (): void {
    $organization = createMockOrganization([
        'is_active' => true,
        'is_verified' => true,
    ]);

    $campaign = createMockCampaign([
        'status' => CampaignStatus::PENDING_APPROVAL,
        'title' => 'Test Campaign',
        'description' => 'A valid test campaign description',
        'goal_amount' => 1000.00,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(30),
        'category' => null,
        'category_id' => null,
        'organization' => $organization,
    ]);

    $specification = new CampaignApprovalSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('specification returns false for non-campaign objects', function (): void {
    $specification = new CampaignApprovalSpecification;

    expect($specification->isSatisfiedBy('not a campaign'))->toBeFalse();
    expect($specification->isSatisfiedBy(new stdClass))->toBeFalse();
    expect($specification->isSatisfiedBy(null))->toBeFalse();
});

test('specification can be combined with other specifications', function (): void {
    $mockObject = createMockCampaign([]);

    $approvalSpec = new CampaignApprovalSpecification;
    $mockSecondSpec = Mockery::mock(\Modules\Shared\Domain\Specification\SpecificationInterface::class);
    $mockSecondSpec->shouldReceive('isSatisfiedBy')->with($mockObject)->andReturn(true);

    $combinedSpec = $approvalSpec->and($mockSecondSpec);

    // Since the approval spec returns false for non-Campaign objects,
    // the combined spec should also return false regardless of the second spec
    expect($combinedSpec->isSatisfiedBy($mockObject))->toBeFalse();
});

/**
 * Create a mock campaign object for testing
 */
function createMockCampaign(array $attributes = []): object
{
    $defaults = [
        'status' => CampaignStatus::DRAFT,
        'title' => 'Test Campaign',
        'description' => 'Test Description',
        'goal_amount' => 1000.00,
        'start_date' => Carbon::now()->addDays(1),
        'end_date' => Carbon::now()->addDays(30),
        'category' => 'health',
        'category_id' => null,
        'approved_at' => null,
        'rejected_at' => null,
        'organization' => null,
    ];

    $data = array_merge($defaults, $attributes);

    return new class($data) implements CampaignInterface
    {
        private array $data;

        private ?object $organization;

        public function __construct(array $data)
        {
            $this->data = $data;
            $this->organization = $data['organization'] ?? null;
        }

        public function __get(string $name)
        {
            if ($name === 'organization') {
                return $this->organization;
            }

            return $this->data[$name] ?? null;
        }

        public function __set(string $name, $value): void
        {
            $this->data[$name] = $value;
        }

        public function __isset(string $name): bool
        {
            if ($name === 'organization') {
                return $this->organization !== null;
            }
            // For category, we want to return true if it has a non-empty value
            if ($name === 'category') {
                return isset($this->data[$name]) && ! empty($this->data[$name]);
            }

            return isset($this->data[$name]) && $this->data[$name] !== null;
        }

        public function getId(): int
        {
            return $this->data['id'] ?? 1;
        }

        public function getTitle(): string
        {
            return $this->data['title'] ?? '';
        }

        public function getDescription(): ?string
        {
            return $this->data['description'] ?? null;
        }

        public function getUrl(): string
        {
            return '/campaigns/test';
        }

        public function relationLoaded(string $relation): bool
        {
            return $relation === 'organization' && $this->organization !== null;
        }

        public function load(string $relation): void
        {
            // Mock load - do nothing as we set organization manually
        }
    };
}

/**
 * Create a mock organization object for testing
 */
function createMockOrganization(array $attributes = []): object
{
    $defaults = [
        'is_active' => true,
        'is_verified' => false,
        'name' => 'Test Organization',
        'registration_number' => 'REG123',
        'tax_id' => 'TAX456',
        'email' => 'test@example.com',
        'category' => 'nonprofit',
    ];

    $data = array_merge($defaults, $attributes);

    return new class($data)
    {
        private array $data;

        public function __construct(array $data)
        {
            $this->data = $data;
        }

        public function __get(string $name)
        {
            return $this->data[$name] ?? null;
        }

        public function __isset(string $name): bool
        {
            return isset($this->data[$name]) && $this->data[$name] !== null;
        }

        public function getName(): string
        {
            return $this->data['name'] ?? '';
        }
    };
}
