<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Modules\Campaign\Domain\Model\Campaign;

test('simple campaign creation', function (): void {
    // Disable Scout for this test
    config(['scout.driver' => null]);

    $campaign = Campaign::create([
        'uuid' => Str::uuid()->toString(),
        'title' => ['en' => 'Test Campaign'],
        'description' => ['en' => 'Test Description'],
        'goal_amount' => 1000,
        'current_amount' => 0,
        'start_date' => now(),
        'end_date' => now()->addMonth(),
        'status' => 'active',
        'category_id' => 1,
        'organization_id' => 1,
        'user_id' => 1,
    ]);

    expect($campaign)->toBeInstanceOf(Campaign::class);
});
