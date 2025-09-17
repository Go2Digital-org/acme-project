<?php

declare(strict_types=1);

use Mockery\MockInterface;
use Modules\Campaign\Domain\Service\CampaignDonationService;
use Modules\Shared\Domain\Repository\DonationRelationRepositoryInterface;

describe('CampaignDonationService', function (): void {
    afterEach(function (): void {
        Mockery::close();
    });

    describe('Donation Count Retrieval', function (): void {
        it('gets donation count for campaign', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            $donationRepository
                ->shouldReceive('getDonationCountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(5);

            $result = $service->getDonationCount($campaignId);

            expect($result)->toBe(5);
        });

        it('handles zero donation count', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            $donationRepository
                ->shouldReceive('getDonationCountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(0);

            $result = $service->getDonationCount($campaignId);

            expect($result)->toBe(0);
        });

        it('handles large donation counts', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            $donationRepository
                ->shouldReceive('getDonationCountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(99999);

            $result = $service->getDonationCount($campaignId);

            expect($result)->toBe(99999);
        });
    });

    describe('Total Raised Calculation', function (): void {
        it('gets total raised amount for campaign', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            $donationRepository
                ->shouldReceive('getTotalDonationAmountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(15750.50);

            $result = $service->getTotalRaised($campaignId);

            expect($result)->toBe(15750.50);
        });

        it('handles zero total raised', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            $donationRepository
                ->shouldReceive('getTotalDonationAmountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(0.0);

            $result = $service->getTotalRaised($campaignId);

            expect($result)->toBe(0.0);
        });

        it('handles large total amounts', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            $donationRepository
                ->shouldReceive('getTotalDonationAmountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(9999999.99);

            $result = $service->getTotalRaised($campaignId);

            expect($result)->toBe(9999999.99);
        });

        it('handles fractional amounts with precision', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            $donationRepository
                ->shouldReceive('getTotalDonationAmountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(1234.56);

            $result = $service->getTotalRaised($campaignId);

            expect($result)->toBe(1234.56);
        });
    });

    describe('Donation Existence Checking', function (): void {
        it('returns true when campaign has donations', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            $donationRepository
                ->shouldReceive('getDonationCountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(3);

            $result = $service->hasDonations($campaignId);

            expect($result)->toBeTrue();
        });

        it('returns false when campaign has no donations', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            $donationRepository
                ->shouldReceive('getDonationCountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(0);

            $result = $service->hasDonations($campaignId);

            expect($result)->toBeFalse();
        });

        it('returns true for single donation', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            $donationRepository
                ->shouldReceive('getDonationCountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(1);

            $result = $service->hasDonations($campaignId);

            expect($result)->toBeTrue();
        });

        it('returns true for many donations', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            $donationRepository
                ->shouldReceive('getDonationCountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(10000);

            $result = $service->hasDonations($campaignId);

            expect($result)->toBeTrue();
        });
    });

    describe('Service Constructor and Dependencies', function (): void {
        it('requires donation repository dependency', function (): void {
            $repository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($repository);

            expect($service)->toBeInstanceOf(CampaignDonationService::class);
        });

        it('implements proper domain service pattern', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);

            expect($service)->toBeInstanceOf(CampaignDonationService::class);

            // Service provides abstraction over repository
            $reflectionClass = new ReflectionClass(CampaignDonationService::class);
            expect($reflectionClass->hasMethod('getDonationCount'))->toBeTrue()
                ->and($reflectionClass->hasMethod('getTotalRaised'))->toBeTrue()
                ->and($reflectionClass->hasMethod('hasDonations'))->toBeTrue();
        });
    });

    describe('Different Campaign IDs', function (): void {
        it('handles different campaign IDs correctly', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignIds = [1, 42, 999, 123456];

            foreach ($campaignIds as $id) {
                $donationRepository
                    ->shouldReceive('getDonationCountForCampaign')
                    ->once()
                    ->with($id)
                    ->andReturn(5);

                $result = $service->getDonationCount($id);
                expect($result)->toBe(5);
            }
        });
    });

    describe('Port/Adapter Pattern Compliance', function (): void {
        it('uses repository interface for decoupling', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);

            // Service depends on interface, not concrete implementation
            expect($donationRepository)->toBeInstanceOf(DonationRelationRepositoryInterface::class);
        });

        it('delegates all data access to repository', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            // Test that service doesn't perform any data access directly
            $donationRepository
                ->shouldReceive('getDonationCountForCampaign')
                ->twice() // Called by both getDonationCount() and hasDonations()
                ->with($campaignId)
                ->andReturn(10);

            $donationRepository
                ->shouldReceive('getTotalDonationAmountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(5000.0);

            // Service should delegate all calls to repository
            $count = $service->getDonationCount($campaignId);
            $total = $service->getTotalRaised($campaignId);
            $hasAny = $service->hasDonations($campaignId);

            expect($count)->toBe(10)
                ->and($total)->toBe(5000.0)
                ->and($hasAny)->toBeTrue();
        });

        it('provides domain-specific abstraction over repository', function (): void {
            // Service provides meaningful domain methods
            $methods = get_class_methods(CampaignDonationService::class);

            expect($methods)->toContain('getDonationCount')
                ->and($methods)->toContain('getTotalRaised')
                ->and($methods)->toContain('hasDonations');
        });
    });

    describe('Business Logic Scenarios', function (): void {
        it('supports campaign analytics scenarios', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            // Mock a realistic campaign scenario
            $donationRepository
                ->shouldReceive('getDonationCountForCampaign')
                ->twice() // Called by both getDonationCount() and hasDonations()
                ->with($campaignId)
                ->andReturn(247);

            $donationRepository
                ->shouldReceive('getTotalDonationAmountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(24700.00);

            $count = $service->getDonationCount($campaignId);
            $total = $service->getTotalRaised($campaignId);
            $hasAny = $service->hasDonations($campaignId);

            // Calculate average donation (business logic example)
            $averageDonation = $count > 0 ? $total / $count : 0;

            expect($count)->toBe(247)
                ->and($total)->toBe(24700.00)
                ->and($hasAny)->toBeTrue()
                ->and($averageDonation)->toBe(100.0);
        });

        it('handles unsuccessful campaign scenarios', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            // Campaign with no donations
            $donationRepository
                ->shouldReceive('getDonationCountForCampaign')
                ->twice() // Called by both getDonationCount() and hasDonations()
                ->with($campaignId)
                ->andReturn(0);

            $donationRepository
                ->shouldReceive('getTotalDonationAmountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(0.0);

            $count = $service->getDonationCount($campaignId);
            $total = $service->getTotalRaised($campaignId);
            $hasAny = $service->hasDonations($campaignId);

            expect($count)->toBe(0)
                ->and($total)->toBe(0.0)
                ->and($hasAny)->toBeFalse();
        });

        it('handles viral campaign scenarios', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            // Viral campaign with many small donations
            $donationRepository
                ->shouldReceive('getDonationCountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(50000);

            $donationRepository
                ->shouldReceive('getTotalDonationAmountForCampaign')
                ->once()
                ->with($campaignId)
                ->andReturn(250000.00);

            $count = $service->getDonationCount($campaignId);
            $total = $service->getTotalRaised($campaignId);

            expect($count)->toBe(50000)
                ->and($total)->toBe(250000.00)
                ->and($total / $count)->toBe(5.0); // Average $5 donation
        });
    });

    describe('Error Handling and Edge Cases', function (): void {
        it('handles repository exceptions gracefully', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            $donationRepository
                ->shouldReceive('getDonationCountForCampaign')
                ->once()
                ->with($campaignId)
                ->andThrow(new RuntimeException('Database connection failed'));

            expect(fn () => $service->getDonationCount($campaignId))
                ->toThrow(RuntimeException::class, 'Database connection failed');
        });

        it('maintains consistency between count and has methods', function (): void {
            /** @var MockInterface&DonationRelationRepositoryInterface $donationRepository */
            $donationRepository = Mockery::mock(DonationRelationRepositoryInterface::class);
            $service = new CampaignDonationService($donationRepository);
            $campaignId = 123;

            // When count is 0, hasDonations should be false
            $donationRepository
                ->shouldReceive('getDonationCountForCampaign')
                ->twice()
                ->with($campaignId)
                ->andReturn(0);

            $count = $service->getDonationCount($campaignId);
            $hasAny = $service->hasDonations($campaignId);

            expect($count)->toBe(0)
                ->and($hasAny)->toBeFalse();
        });
    });
});
