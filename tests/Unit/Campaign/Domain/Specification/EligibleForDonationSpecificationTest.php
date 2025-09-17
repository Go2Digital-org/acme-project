<?php

declare(strict_types=1);

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Specification\EligibleForDonationSpecification;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;

function createCampaignMock(array $attributes = []): Campaign
{
    // Create a mock that bypasses Eloquent's attribute setting mechanisms
    $campaign = new class extends Campaign
    {
        public function __construct()
        {
            // Don't call parent constructor to avoid Eloquent initialization
        }

        public function setAttribute($key, $value)
        {
            // Override to prevent the setAttribute calls that were causing errors
            $this->attributes[$key] = $value;

            return $this;
        }
    };

    // Set properties directly on the anonymous class
    $campaign->status = $attributes['status'] ?? CampaignStatus::ACTIVE;
    $campaign->start_date = $attributes['start_date'] ?? now()->subDays(5);
    $campaign->end_date = $attributes['end_date'] ?? now()->addDays(10);
    $campaign->goal_amount = $attributes['goal_amount'] ?? 1000.00;
    $campaign->current_amount = $attributes['current_amount'] ?? 500.00;

    return $campaign;
}

test('campaign is eligible for donation when all conditions are met', function (): void {
    $campaign = createCampaignMock();

    $specification = new EligibleForDonationSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeTrue();
});

test('campaign is not eligible for donation when status cannot accept donations', function (): void {
    $campaign = createCampaignMock(['status' => CampaignStatus::DRAFT]);

    $specification = new EligibleForDonationSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('campaign is not eligible for donation when end date has passed', function (): void {
    $campaign = createCampaignMock([
        'start_date' => now()->subDays(15),
        'end_date' => now()->subDays(1),
    ]);

    $specification = new EligibleForDonationSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('campaign is not eligible for donation when goal amount has been reached', function (): void {
    $campaign = createCampaignMock([
        'goal_amount' => 1000.00,
        'current_amount' => 1000.00,
    ]);

    $specification = new EligibleForDonationSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('campaign is not eligible for donation when goal amount has been exceeded', function (): void {
    $campaign = createCampaignMock([
        'goal_amount' => 1000.00,
        'current_amount' => 1500.00,
    ]);

    $specification = new EligibleForDonationSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('campaign is not eligible for donation when it has not started yet', function (): void {
    $campaign = createCampaignMock([
        'start_date' => now()->addDays(5),
        'end_date' => now()->addDays(15),
    ]);

    $specification = new EligibleForDonationSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();
});

test('campaign is not eligible for donation when goal amount is zero or negative', function (): void {
    $campaign = createCampaignMock(['goal_amount' => 0.00]);

    $specification = new EligibleForDonationSpecification;

    expect($specification->isSatisfiedBy($campaign))->toBeFalse();

    // Test negative goal amount with a new mock
    $campaign2 = createCampaignMock(['goal_amount' => -100.00]);

    expect($specification->isSatisfiedBy($campaign2))->toBeFalse();
});

test('specification returns false for non-campaign objects', function (): void {
    $specification = new EligibleForDonationSpecification;

    expect($specification->isSatisfiedBy('not a campaign'))->toBeFalse();
    expect($specification->isSatisfiedBy(new stdClass))->toBeFalse();
    expect($specification->isSatisfiedBy(null))->toBeFalse();
});

test('specification can be combined with other specifications using AND logic', function (): void {
    $campaign = createCampaignMock();

    $donationSpec = new EligibleForDonationSpecification;
    $mockSecondSpec = Mockery::mock(\Modules\Shared\Domain\Specification\SpecificationInterface::class);
    $mockSecondSpec->shouldReceive('isSatisfiedBy')->with($campaign)->andReturn(true);

    $combinedSpec = $donationSpec->and($mockSecondSpec);

    expect($combinedSpec->isSatisfiedBy($campaign))->toBeTrue();
});

test('specification can be combined with other specifications using OR logic', function (): void {
    $campaign = createCampaignMock(['status' => CampaignStatus::DRAFT]);

    $donationSpec = new EligibleForDonationSpecification;
    $mockSecondSpec = Mockery::mock(\Modules\Shared\Domain\Specification\SpecificationInterface::class);
    $mockSecondSpec->shouldReceive('isSatisfiedBy')->with($campaign)->andReturn(true);

    $combinedSpec = $donationSpec->or($mockSecondSpec);

    expect($combinedSpec->isSatisfiedBy($campaign))->toBeTrue();
});

test('specification can be negated using NOT logic', function (): void {
    $campaign = createCampaignMock();

    $donationSpec = new EligibleForDonationSpecification;
    $negatedSpec = $donationSpec->not();

    // Should return opposite of the original specification
    expect($negatedSpec->isSatisfiedBy($campaign))->toBeFalse();
});
