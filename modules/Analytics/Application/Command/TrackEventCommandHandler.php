<?php

declare(strict_types=1);

namespace Modules\Analytics\Application\Command;

use Exception;
use Illuminate\Support\Facades\DB;
use Psr\Log\LoggerInterface;

class TrackEventCommandHandler
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(TrackEventCommand $command): bool
    {
        try {
            // Store event in analytics_events table
            $eventId = DB::table('analytics_events')->insertGetId([
                'event_type' => $command->eventType,
                'event_name' => $command->eventName,
                'user_id' => $command->userId,
                'organization_id' => $command->organizationId,
                'campaign_id' => $command->campaignId,
                'donation_id' => $command->donationId,
                'properties' => json_encode($command->properties),
                'session_id' => $command->sessionId,
                'user_agent' => $command->userAgent,
                'ip_address' => $command->ipAddress,
                'referrer' => $command->referrer,
                'created_at' => $command->timestamp ?? now(),
                'updated_at' => now(),
            ]);

            $this->logger->info('Analytics event tracked', [
                'event_id' => $eventId,
                'event_type' => $command->eventType,
                'event_name' => $command->eventName,
                'user_id' => $command->userId,
                'campaign_id' => $command->campaignId,
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->error('Failed to track analytics event', [
                'event_type' => $command->eventType,
                'event_name' => $command->eventName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
