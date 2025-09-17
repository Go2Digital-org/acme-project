<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Seeder;

use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Infrastructure\Laravel\Factory\DonationFactory;
use Modules\Donation\Infrastructure\Laravel\Factory\PaymentFactory;
use Modules\Shared\Domain\ValueObject\DonationStatus;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Donation Seeder for Hexagonal Architecture.
 *
 * Creates comprehensive donation history for ACME Corp CSR platform
 */
class DonationSeeder extends Seeder
{
    private readonly Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create();
    }

    public function run(): void
    {
        $this->command->info('Seeding donations for ACME Corp CSR platform...');

        // Ensure we have campaigns and users
        $campaigns = Campaign::all();
        $users = User::all();

        if ($campaigns->isEmpty()) {
            $this->command->warn('No campaigns found. Please run CampaignSeeder first.');

            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('No users found. Please run UserSeeder first.');

            return;
        }

        $this->createDonationsForActiveCampaigns($campaigns, $users);
        $this->createDonationsForCompletedCampaigns($campaigns, $users);
        $this->createRecentDonationActivity($campaigns, $users);
        $this->createVariedDonationPatterns($campaigns, $users);

        // Update all campaign amounts after all donations are created
        $this->command->info('Updating campaign funding amounts...');
        foreach ($campaigns as $campaign) {
            $this->updateCampaignAmount($campaign);
        }

        $this->command->info('Successfully seeded donations for enterprise CSR platform');
    }

    /**
     * @param  Collection<int, Campaign>  $campaigns
     * @param  Collection<int, User>  $users
     */
    private function createDonationsForActiveCampaigns(Collection $campaigns, Collection $users): void
    {
        $this->command->info('Creating donations for active campaigns...');

        $activeCampaigns = $campaigns->where('status', CampaignStatus::ACTIVE->value);

        foreach ($activeCampaigns as $campaign) {
            // Number of donations per campaign varies realistically
            $donationCount = $this->calculateDonationCount($campaign);

            if ($donationCount === 0) {
                continue;
            }

            // Create donations with realistic distribution
            $this->createDonationsForCampaign($campaign, $users, $donationCount);

            // Update campaign current_amount based on completed donations
            $this->updateCampaignAmount($campaign);
        }
    }

    /**
     * @param  Collection<int, Campaign>  $campaigns
     * @param  Collection<int, User>  $users
     */
    private function createDonationsForCompletedCampaigns(Collection $campaigns, Collection $users): void
    {
        $this->command->info('Creating donations for completed campaigns...');

        $completedCampaigns = $campaigns->where('status', CampaignStatus::COMPLETED->value);

        foreach ($completedCampaigns as $campaign) {
            // Completed campaigns should have donations that reach their goal
            $donationCount = $this->faker->numberBetween(15, 50);
            $this->createDonationsForCampaign($campaign, $users, $donationCount, true);

            // Ensure the campaign amount matches goal (it should already from the factory)
            $campaign->update(['current_amount' => $campaign->goal_amount]);
        }
    }

    /**
     * @param  Collection<int, Campaign>  $campaigns
     * @param  Collection<int, User>  $users
     */
    private function createRecentDonationActivity(Collection $campaigns, Collection $users): void
    {
        $this->command->info('Creating recent donation activity...');

        $recentCampaigns = $campaigns->where('status', CampaignStatus::ACTIVE->value)
            ->random(min(10, $campaigns->count()));

        foreach ($recentCampaigns as $campaign) {
            // Recent donations for active campaigns
            $donationCount = $this->faker->numberBetween(1, 5);

            foreach (range(1, $donationCount) as $i) {
                DonationFactory::new()
                    ->recent()
                    ->completed()
                    ->create([
                        'campaign_id' => $campaign->id,
                        'user_id' => $users->random()->id,
                    ]);
            }
        }
    }

    /**
     * @param  Collection<int, Campaign>  $campaigns
     * @param  Collection<int, User>  $users
     */
    private function createVariedDonationPatterns(Collection $campaigns, Collection $users): void
    {
        $this->command->info('Creating varied donation patterns...');

        // Some major donations
        $activeCampaigns = $campaigns->where('status', CampaignStatus::ACTIVE->value);

        foreach (range(1, 15) as $i) {
            DonationFactory::new()
                ->completed()
                ->majorAmount()
                ->withMessage()
                ->create([
                    'campaign_id' => $activeCampaigns->random()->id,
                    'user_id' => $users->random()->id,
                ]);
        }

        // Anonymous donations
        foreach (range(1, 25) as $i) {
            DonationFactory::new()
                ->completed()
                ->anonymous()
                ->create([
                    'campaign_id' => $campaigns->random()->id,
                    'user_id' => $users->random()->id,
                ]);
        }

        // Small regular donations
        foreach (range(1, 80) as $i) {
            DonationFactory::new()
                ->completed()
                ->smallAmount()
                ->create([
                    'campaign_id' => $campaigns->random()->id,
                    'user_id' => $users->random()->id,
                ]);
        }

        // Failed/cancelled donations (realistic failure rate)
        foreach (range(1, 12) as $i) {
            DonationFactory::new()
                ->failed()
                ->create([
                    'campaign_id' => $campaigns->random()->id,
                    'user_id' => $users->random()->id,
                ]);
        }

        foreach (range(1, 8) as $i) {
            DonationFactory::new()
                ->cancelled()
                ->create([
                    'campaign_id' => $campaigns->random()->id,
                    'user_id' => $users->random()->id,
                ]);
        }

        // Some pending donations
        foreach (range(1, 5) as $i) {
            DonationFactory::new()
                ->pending()
                ->create([
                    'campaign_id' => $activeCampaigns->random()->id,
                    'user_id' => $users->random()->id,
                ]);
        }
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function createDonationsForCampaign(Campaign $campaign, Collection $users, int $count, bool $isCompleted = false): void
    {
        $targetAmount = $isCompleted ? $campaign->goal_amount : $campaign->current_amount;
        $remainingAmount = $targetAmount;

        for ($i = 0; $i < $count; $i++) {
            // Calculate donation amount to reach target
            if ($i === $count - 1 && $isCompleted) {
                // Last donation should complete the goal
                $amount = max(5, $remainingAmount);
            } else {
                // Random amount, but keep some for remaining donations
                $maxAmount = min($remainingAmount * 0.7, 1000);
                $amount = $this->faker->randomFloat(2, 5, max(5, $maxAmount));
            }

            $remainingAmount -= $amount;

            // Most donations are successful, some failed
            $factory = $this->faker->boolean(90) ?
                DonationFactory::new()->completed() :
                DonationFactory::new()->failed();

            $donationDate = $this->faker->dateTimeBetween($campaign->start_date, '-1 day');
            // Avoid DST issues by setting safe hour
            $donationDate->setTime(12, 0, 0);

            /** @var Donation $donation */
            $donation = $factory->create([
                'campaign_id' => $campaign->id,
                'user_id' => $users->random()->id,
                'amount' => $amount,
                'donated_at' => $donationDate,
                'created_at' => $donationDate,
                'updated_at' => $donationDate,
            ]);

            // Create corresponding payment record for completed donations
            if ($donation->status === DonationStatus::COMPLETED) {
                PaymentFactory::new()
                    ->successful()
                    ->create([
                        'donation_id' => $donation->id,
                        'amount' => $donation->amount,
                        'captured_at' => $donation->created_at,
                    ]);
            }

            if ($remainingAmount <= 0) {
                break;
            }
        }
    }

    private function calculateDonationCount(Campaign $campaign): int
    {
        // For active campaigns, create donations based on goal amount to ensure funding
        if ($campaign->status === CampaignStatus::ACTIVE) {
            // Active campaigns should have donations - use goal to determine count
            $targetAmount = $campaign->goal_amount * $this->faker->randomFloat(2, 0.15, 0.85);
            $averageDonation = 75; // €75 average

            // Ensure at least 3 donations for active campaigns
            return max(3, (int) ($targetAmount / $averageDonation));
        }

        // For other statuses, check if they have current_amount set
        $currentAmount = $campaign->current_amount;

        if ($currentAmount <= 0) {
            return 0;
        }

        // Estimate based on average donation size
        $averageDonation = 75; // €75 average

        return max(1, (int) ($currentAmount / $averageDonation));
    }

    private function updateCampaignAmount(Campaign $campaign): void
    {
        // Recalculate current amount based on actual completed donations
        $completedDonations = Donation::where('campaign_id', $campaign->id)
            ->where('status', DonationStatus::COMPLETED)
            ->get()
            ->sum('amount');

        $campaign->update(['current_amount' => $completedDonations]);
    }
}
