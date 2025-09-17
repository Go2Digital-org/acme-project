<?php

declare(strict_types=1);

use Modules\Campaign\Domain\ValueObject\UserCampaignStats;

describe('UserCampaignStats Value Object', function (): void {
    describe('Constructor', function (): void {
        it('creates stats with all properties', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 10,
                activeCampaigns: 3,
                completedCampaigns: 5,
                draftCampaigns: 2,
                totalAmountRaised: 15000.0,
                totalGoalAmount: 20000.0,
                totalDonations: 125,
                averageSuccessRate: 75.5
            );

            expect($stats->totalCampaigns)->toBe(10)
                ->and($stats->activeCampaigns)->toBe(3)
                ->and($stats->completedCampaigns)->toBe(5)
                ->and($stats->draftCampaigns)->toBe(2)
                ->and($stats->totalAmountRaised)->toBe(15000.0)
                ->and($stats->totalGoalAmount)->toBe(20000.0)
                ->and($stats->totalDonations)->toBe(125)
                ->and($stats->averageSuccessRate)->toBe(75.5);
        });

        it('creates stats with zero values', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 0,
                activeCampaigns: 0,
                completedCampaigns: 0,
                draftCampaigns: 0,
                totalAmountRaised: 0.0,
                totalGoalAmount: 0.0,
                totalDonations: 0,
                averageSuccessRate: 0.0
            );

            expect($stats->totalCampaigns)->toBe(0)
                ->and($stats->totalAmountRaised)->toBe(0.0)
                ->and($stats->averageSuccessRate)->toBe(0.0);
        });
    });

    describe('Progress Calculations', function (): void {
        it('calculates progress percentage correctly', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 5,
                activeCampaigns: 2,
                completedCampaigns: 3,
                draftCampaigns: 0,
                totalAmountRaised: 7500.0,
                totalGoalAmount: 10000.0,
                totalDonations: 50,
                averageSuccessRate: 75.0
            );

            expect($stats->getProgressPercentage())->toBe(75.0);
        });

        it('handles zero goal amount in progress calculation', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 5,
                activeCampaigns: 2,
                completedCampaigns: 3,
                draftCampaigns: 0,
                totalAmountRaised: 1000.0,
                totalGoalAmount: 0.0,
                totalDonations: 50,
                averageSuccessRate: 75.0
            );

            expect($stats->getProgressPercentage())->toBe(0.0);
        });

        it('caps progress percentage at 100%', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 5,
                activeCampaigns: 2,
                completedCampaigns: 3,
                draftCampaigns: 0,
                totalAmountRaised: 12000.0,
                totalGoalAmount: 10000.0,
                totalDonations: 50,
                averageSuccessRate: 120.0
            );

            expect($stats->getProgressPercentage())->toBe(100.0);
        });

        it('handles very small amounts in progress calculation', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 1,
                activeCampaigns: 1,
                completedCampaigns: 0,
                draftCampaigns: 0,
                totalAmountRaised: 1.0,
                totalGoalAmount: 1000.0,
                totalDonations: 1,
                averageSuccessRate: 0.1
            );

            expect($stats->getProgressPercentage())->toBe(0.1);
        });
    });

    describe('Formatting Methods', function (): void {
        it('formats total raised correctly', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 5,
                activeCampaigns: 2,
                completedCampaigns: 3,
                draftCampaigns: 0,
                totalAmountRaised: 15750.50,
                totalGoalAmount: 20000.0,
                totalDonations: 125,
                averageSuccessRate: 78.75
            );

            expect($stats->getFormattedTotalRaised())->toBe('€15.751');
        });

        it('formats total goal correctly', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 5,
                activeCampaigns: 2,
                completedCampaigns: 3,
                draftCampaigns: 0,
                totalAmountRaised: 15000.0,
                totalGoalAmount: 25750.25,
                totalDonations: 125,
                averageSuccessRate: 75.0
            );

            expect($stats->getFormattedTotalGoal())->toBe('€25.750');
        });

        it('formats success rate correctly', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 5,
                activeCampaigns: 2,
                completedCampaigns: 3,
                draftCampaigns: 0,
                totalAmountRaised: 15000.0,
                totalGoalAmount: 20000.0,
                totalDonations: 125,
                averageSuccessRate: 78.456
            );

            expect($stats->getFormattedSuccessRate())->toBe('78.5%');
        });

        it('formats zero amounts correctly', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 0,
                activeCampaigns: 0,
                completedCampaigns: 0,
                draftCampaigns: 0,
                totalAmountRaised: 0.0,
                totalGoalAmount: 0.0,
                totalDonations: 0,
                averageSuccessRate: 0.0
            );

            expect($stats->getFormattedTotalRaised())->toBe('€0')
                ->and($stats->getFormattedTotalGoal())->toBe('€0')
                ->and($stats->getFormattedSuccessRate())->toBe('0.0%');
        });

        it('formats large amounts correctly', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 100,
                activeCampaigns: 25,
                completedCampaigns: 75,
                draftCampaigns: 0,
                totalAmountRaised: 1250000.0,
                totalGoalAmount: 2000000.0,
                totalDonations: 5000,
                averageSuccessRate: 85.5
            );

            expect($stats->getFormattedTotalRaised())->toBe('€1.250.000')
                ->and($stats->getFormattedTotalGoal())->toBe('€2.000.000');
        });
    });

    describe('Status Checking Methods', function (): void {
        it('detects active campaigns correctly', function (): void {
            $statsWithActive = new UserCampaignStats(
                totalCampaigns: 5,
                activeCampaigns: 3,
                completedCampaigns: 2,
                draftCampaigns: 0,
                totalAmountRaised: 15000.0,
                totalGoalAmount: 20000.0,
                totalDonations: 125,
                averageSuccessRate: 75.0
            );

            $statsWithoutActive = new UserCampaignStats(
                totalCampaigns: 5,
                activeCampaigns: 0,
                completedCampaigns: 5,
                draftCampaigns: 0,
                totalAmountRaised: 15000.0,
                totalGoalAmount: 20000.0,
                totalDonations: 125,
                averageSuccessRate: 100.0
            );

            expect($statsWithActive->hasActiveCampaigns())->toBeTrue()
                ->and($statsWithoutActive->hasActiveCampaigns())->toBeFalse();
        });

        it('detects draft campaigns correctly', function (): void {
            $statsWithDrafts = new UserCampaignStats(
                totalCampaigns: 7,
                activeCampaigns: 2,
                completedCampaigns: 3,
                draftCampaigns: 2,
                totalAmountRaised: 15000.0,
                totalGoalAmount: 20000.0,
                totalDonations: 125,
                averageSuccessRate: 75.0
            );

            $statsWithoutDrafts = new UserCampaignStats(
                totalCampaigns: 5,
                activeCampaigns: 2,
                completedCampaigns: 3,
                draftCampaigns: 0,
                totalAmountRaised: 15000.0,
                totalGoalAmount: 20000.0,
                totalDonations: 125,
                averageSuccessRate: 75.0
            );

            expect($statsWithDrafts->hasDrafts())->toBeTrue()
                ->and($statsWithoutDrafts->hasDrafts())->toBeFalse();
        });
    });

    describe('Calculated Properties', function (): void {
        it('calculates total published campaigns correctly', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 10,
                activeCampaigns: 3,
                completedCampaigns: 5,
                draftCampaigns: 2,
                totalAmountRaised: 15000.0,
                totalGoalAmount: 20000.0,
                totalDonations: 125,
                averageSuccessRate: 75.0
            );

            expect($stats->getTotalPublishedCampaigns())->toBe(8); // 10 total - 2 drafts
        });

        it('handles zero drafts in published calculation', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 8,
                activeCampaigns: 3,
                completedCampaigns: 5,
                draftCampaigns: 0,
                totalAmountRaised: 15000.0,
                totalGoalAmount: 20000.0,
                totalDonations: 125,
                averageSuccessRate: 75.0
            );

            expect($stats->getTotalPublishedCampaigns())->toBe(8);
        });

        it('handles all drafts scenario', function (): void {
            $stats = new UserCampaignStats(
                totalCampaigns: 5,
                activeCampaigns: 0,
                completedCampaigns: 0,
                draftCampaigns: 5,
                totalAmountRaised: 0.0,
                totalGoalAmount: 10000.0,
                totalDonations: 0,
                averageSuccessRate: 0.0
            );

            expect($stats->getTotalPublishedCampaigns())->toBe(0);
        });
    });
});
