<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\Specification;

use Modules\Shared\Domain\Specification\CompositeSpecification;
use Modules\Shared\Domain\Specification\SpecificationFactory;
use Modules\Shared\Domain\Specification\SpecificationInterface;
use Tests\UnitTestCase;

class SpecificationFactoryTest extends UnitTestCase
{
    private SpecificationFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new SpecificationFactory;
    }

    public function test_create_campaign_publishing_specification_returns_composite_spec(): void
    {
        $spec = $this->factory->createCampaignPublishingSpecification();

        $this->assertInstanceOf(SpecificationInterface::class, $spec);
    }

    public function test_create_organization_admin_specification_returns_composite_spec(): void
    {
        $spec = $this->factory->createOrganizationAdminSpecification();

        $this->assertInstanceOf(SpecificationInterface::class, $spec);
    }

    public function test_create_campaign_manager_specification_returns_composite_spec(): void
    {
        $spec = $this->factory->createCampaignManagerSpecification();

        $this->assertInstanceOf(SpecificationInterface::class, $spec);
    }

    public function test_create_donation_processing_specification_returns_composite_spec(): void
    {
        $spec = $this->factory->createDonationProcessingSpecification();

        $this->assertInstanceOf(SpecificationInterface::class, $spec);
    }

    public function test_create_super_admin_specification_returns_specification(): void
    {
        $spec = $this->factory->createSuperAdminSpecification();

        $this->assertInstanceOf(SpecificationInterface::class, $spec);
    }

    public function test_create_verified_organization_member_specification_returns_composite_spec(): void
    {
        $spec = $this->factory->createVerifiedOrganizationMemberSpecification();

        $this->assertInstanceOf(SpecificationInterface::class, $spec);
    }

    public function test_create_campaign_moderation_specification_returns_composite_spec(): void
    {
        $spec = $this->factory->createCampaignModerationSpecification();

        $this->assertInstanceOf(SpecificationInterface::class, $spec);
    }

    public function test_create_restricted_campaign_access_specification_returns_composite_spec(): void
    {
        $spec = $this->factory->createRestrictedCampaignAccessSpecification();

        $this->assertInstanceOf(SpecificationInterface::class, $spec);
    }

    public function test_create_public_campaign_view_specification_returns_composite_spec(): void
    {
        $spec = $this->factory->createPublicCampaignViewSpecification();

        $this->assertInstanceOf(SpecificationInterface::class, $spec);
    }

    public function test_create_custom_specification_with_single_spec_returns_same_spec(): void
    {
        $mockSpec = $this->createMockSpecification(true);

        $result = $this->factory->createCustomSpecification([$mockSpec]);

        $this->assertSame($mockSpec, $result);
    }

    public function test_create_custom_specification_with_multiple_specs_and_and_operator(): void
    {
        $spec1 = $this->createMockSpecification(true);
        $spec2 = $this->createMockSpecification(false);

        $result = $this->factory->createCustomSpecification([$spec1, $spec2], 'and');

        $this->assertInstanceOf(SpecificationInterface::class, $result);
        $this->assertFalse($result->isSatisfiedBy('test'));
    }

    public function test_create_custom_specification_with_multiple_specs_and_or_operator(): void
    {
        $spec1 = $this->createMockSpecification(true);
        $spec2 = $this->createMockSpecification(false);

        $result = $this->factory->createCustomSpecification([$spec1, $spec2], 'or');

        $this->assertInstanceOf(SpecificationInterface::class, $result);
        $this->assertTrue($result->isSatisfiedBy('test'));
    }

    public function test_create_custom_specification_with_three_specs_using_and(): void
    {
        $spec1 = $this->createMockSpecification(true);
        $spec2 = $this->createMockSpecification(true);
        $spec3 = $this->createMockSpecification(false);

        $result = $this->factory->createCustomSpecification([$spec1, $spec2, $spec3], 'and');

        $this->assertFalse($result->isSatisfiedBy('test'));
    }

    public function test_create_custom_specification_with_three_specs_using_or(): void
    {
        $spec1 = $this->createMockSpecification(false);
        $spec2 = $this->createMockSpecification(false);
        $spec3 = $this->createMockSpecification(true);

        $result = $this->factory->createCustomSpecification([$spec1, $spec2, $spec3], 'or');

        $this->assertTrue($result->isSatisfiedBy('test'));
    }

    public function test_create_custom_specification_throws_exception_with_empty_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one specification is required');

        $this->factory->createCustomSpecification([]);
    }

    public function test_create_custom_specification_throws_exception_with_invalid_operator(): void
    {
        $spec = $this->createMockSpecification(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid operator: invalid. Use \'and\' or \'or\'.');

        $this->factory->createCustomSpecification([$spec], 'invalid');
    }

    public function test_create_not_specification_returns_negated_spec(): void
    {
        $mockSpec = $this->createMockSpecification(true);

        $result = $this->factory->createNotSpecification($mockSpec);

        $this->assertInstanceOf(SpecificationInterface::class, $result);
        $this->assertFalse($result->isSatisfiedBy('test'));
    }

    public function test_create_not_specification_with_false_spec_returns_true(): void
    {
        $mockSpec = $this->createMockSpecification(false);

        $result = $this->factory->createNotSpecification($mockSpec);

        $this->assertTrue($result->isSatisfiedBy('test'));
    }

    /**
     * Create a mock specification for testing.
     */
    private function createMockSpecification(bool $result): SpecificationInterface
    {
        return new class($result) extends CompositeSpecification
        {
            public function __construct(private readonly bool $result) {}

            public function isSatisfiedBy(mixed $candidate): bool
            {
                return $this->result;
            }
        };
    }
}
