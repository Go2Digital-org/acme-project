<?php

declare(strict_types=1);

namespace Modules\Admin\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Modules\Campaign\Infrastructure\Laravel\Notifications\CampaignCreatedNotification;
use Modules\Campaign\Infrastructure\Laravel\Notifications\CampaignGoalReachedNotification;
use Modules\Donation\Infrastructure\Laravel\Notifications\DonationConfirmedNotification;
use Modules\User\Infrastructure\Laravel\Models\User;

class CreateSampleNotificationsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'notifications:sample {user_id?}';

    /**
     * @var string
     */
    protected $description = 'Create sample notifications for testing';

    public function handle(): int
    {
        $userId = $this->argument('user_id');

        if ($userId) {
            $user = User::find($userId);

            if (! $user) {
                $this->error("User with ID {$userId} not found.");

                return 1;
            }
        } else {
            $user = User::first();

            if (! $user) {
                $this->error('No users found in the database.');

                return 1;
            }
        }

        $this->info("Creating sample notifications for user: {$user->name} (ID: {$user->id})");

        // Create sample notifications
        $user->notify(new CampaignCreatedNotification(
            1,
            'Clean Water Initiative',
            'Help provide clean water to communities in need across developing regions',
        ));

        $user->notify(new DonationConfirmedNotification(
            1,
            '€50.00',
            'Education for All Campaign',
        ));

        $user->notify(new CampaignGoalReachedNotification(
            2,
            'Healthcare Support Fund',
            '€10,000.00',
        ));

        $this->info(' Sample notifications created successfully!');
        $this->info('You can now check the notifications dropdown in the navigation bar.');

        return 0;
    }
}
