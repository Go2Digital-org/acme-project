<?php

declare(strict_types=1);

namespace Modules\Shared\Application\Command;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\User\Infrastructure\Laravel\Models\User;
use RuntimeException;

final class SuperSeedCommandHandler implements CommandHandlerInterface
{
    private const BATCH_MEMORY_THRESHOLD = 10;

    /** @var array<string, class-string> */
    private array $availableModels = [
        'User' => User::class,
        'Campaign' => Campaign::class,
        'Donation' => Donation::class,
    ];

    /** @return array<string, mixed> */
    public function handle(CommandInterface $command): array
    {
        if (! $command instanceof SuperSeedCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        // Prevent running in production environment
        if (app()->environment('production')) {
            throw new RuntimeException('Super seeder cannot run in production environment for safety reasons.');
        }

        $startTime = microtime(true);
        $totalCreated = 0;

        if ($command->model === 'All') {
            foreach ($this->availableModels as $modelClass) {
                $created = $this->seedModel($modelClass, $command->count, $command->batchSize, $command->organizationId);
                $totalCreated += $created;
            }
        } else {
            if (! isset($this->availableModels[$command->model])) {
                throw new InvalidArgumentException("Model {$command->model} is not available for seeding");
            }

            $modelClass = $this->availableModels[$command->model];
            $totalCreated = $this->seedModel($modelClass, $command->count, $command->batchSize, $command->organizationId);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $throughput = $totalCreated > 0 ? round($totalCreated / $duration) : 0;

        $result = [
            'total_created' => $totalCreated,
            'duration' => round($duration, 2),
            'throughput' => $throughput,
            'model' => $command->model,
            'organization_id' => $command->organizationId,
            'auto_indexed' => false,
        ];

        // Auto-index if requested
        if ($command->autoIndex && $totalCreated > 0) {
            $result['auto_indexed'] = $this->performAutoIndexing($command->model);
        }

        return $result;
    }

    private function seedModel(string $modelClass, int $count, int $batchSize, ?int $organizationId): int
    {
        /** @var Model $modelInstance */
        $modelInstance = new $modelClass;
        $tableName = $modelInstance->getTable();
        $totalCreated = 0;
        $batchCount = 0;

        $batches = (int) ceil($count / $batchSize);

        for ($batch = 0; $batch < $batches; $batch++) {
            $currentBatchSize = min($batchSize, $count - $totalCreated);

            if ($currentBatchSize <= 0) {
                break;
            }

            $batchData = $this->generateBatchData($modelClass, $currentBatchSize, $organizationId);

            if ($batchData !== []) {
                DB::table($tableName)->insert($batchData);
                $totalCreated += count($batchData);
            }

            $batchCount++;

            // Memory cleanup every 10 batches
            if ($batchCount % self::BATCH_MEMORY_THRESHOLD === 0) {
                $this->cleanupMemory();
            }
        }

        return $totalCreated;
    }

    /** @return array<int, array<string, mixed>> */
    private function generateBatchData(string $modelClass, int $batchSize, ?int $organizationId): array
    {
        $data = [];
        $now = now();

        for ($i = 0; $i < $batchSize; $i++) {
            $record = $this->generateRecord($modelClass, $organizationId, $now);
            if ($record) {
                $data[] = $record;
            }
        }

        return $data;
    }

    /** @return array<string, mixed>|null */
    private function generateRecord(string $modelClass, ?int $organizationId, Carbon $timestamp): ?array
    {
        $baseData = [
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];

        return match ($modelClass) {
            User::class => array_merge($baseData, [
                'name' => fake()->name(),
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->unique()->userName() . '+' . time() . fake()->randomNumber(4) . '@example.com',
                'email_verified_at' => $timestamp,
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
                'role' => fake()->randomElement(['employee', 'admin']),
                'status' => fake()->randomElement(['active', 'inactive', 'suspended', 'pending_verification', 'blocked']),
                'organization_id' => $organizationId ?: fake()->numberBetween(1, 10),
                'remember_token' => null,
                'profile_photo_path' => null,
                'google_id' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'preferences' => null,
            ]),

            Campaign::class => array_merge($baseData, [
                'uuid' => fake()->uuid(),
                'title' => json_encode([
                    'en' => '[EN] ' . fake()->sentence(3),
                    'nl' => '[NL] ' . fake()->sentence(3),
                    'fr' => '[FR] ' . fake('fr')->sentence(3),
                ]),
                'slug' => fake()->slug(),
                'description' => json_encode([
                    'en' => '[EN] ' . fake()->paragraph(),
                    'nl' => '[NL] ' . fake()->paragraph(),
                    'fr' => '[FR] ' . fake('fr')->paragraph(),
                ]),
                'goal_amount' => fake()->randomFloat(2, 1000, 100000),
                'current_amount' => fake()->randomFloat(2, 0, 5000),
                'donations_count' => fake()->numberBetween(0, 50),
                'start_date' => fake()->dateTimeBetween('-1 month', '+1 month'),
                'end_date' => fake()->dateTimeBetween('+1 month', '+6 months'),
                'completed_at' => null,
                'status' => fake()->randomElement(['draft', 'active', 'completed', 'cancelled']),
                'submitted_for_approval_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'rejected_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'category_id' => fake()->numberBetween(1, 10),
                'category' => fake()->randomElement(['education', 'health', 'environment', 'community']),
                'visibility' => fake()->randomElement(['public', 'private', 'internal']),
                'featured_image' => null,
                'has_corporate_matching' => fake()->boolean(30),
                'corporate_matching_rate' => fake()->optional(0.3)->randomFloat(2, 0.1, 2.0),
                'max_corporate_matching' => fake()->optional(0.3)->randomFloat(2, 1000, 10000),
                'organization_id' => $organizationId ?: fake()->numberBetween(1, 10),
                'user_id' => fake()->numberBetween(1, 50),
                'metadata' => null,
                'deleted_at' => null,
            ]),

            Donation::class => array_merge($baseData, [
                'campaign_id' => fake()->numberBetween(1, 100),
                'user_id' => fake()->optional(0.7)->numberBetween(1, 50),
                'donor_name' => fake()->name(),
                'donor_email' => fake()->userName() . '+donor' . time() . fake()->randomNumber(4) . '@example.com',
                'amount' => fake()->randomFloat(2, 10, 1000),
                'currency' => 'EUR',
                'payment_method' => fake()->randomElement(['credit_card', 'bank_transfer', 'paypal']),
                'payment_gateway' => fake()->randomElement(['stripe', 'mollie', 'paypal']),
                'transaction_id' => fake()->uuid() . '-' . time() . '-' . fake()->randomNumber(6),
                'gateway_response_id' => fake()->optional(0.8)->uuid(),
                'status' => fake()->randomElement(['pending', 'completed', 'failed', 'refunded']),
                'anonymous' => fake()->boolean(20),
                'recurring' => fake()->boolean(10),
                'recurring_frequency' => fake()->optional(0.1)->randomElement(['weekly', 'monthly', 'yearly']),
                'donated_at' => fake()->dateTimeThisMonth(),
                'processed_at' => fake()->optional(0.8)->dateTimeThisMonth(),
                'completed_at' => fake()->optional(0.7)->dateTimeThisMonth(),
                'cancelled_at' => null,
                'refunded_at' => null,
                'failure_reason' => null,
                'refund_reason' => null,
                'failed_at' => null,
                'corporate_match_amount' => fake()->optional(0.2)->randomFloat(2, 5, 200),
                'confirmation_email_failed_at' => null,
                'confirmation_email_failure_reason' => null,
                'notes' => fake()->optional(0.3, null)->passthrough(json_encode([
                    'en' => '[EN] ' . fake()->sentence(),
                    'nl' => '[NL] ' . fake()->sentence(),
                    'fr' => '[FR] ' . fake('fr')->sentence(),
                ])),
                'metadata' => null,
                'deleted_at' => null,
            ]),

            default => null,
        };
    }

    private function cleanupMemory(): void
    {
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }

        if (function_exists('memory_get_usage')) {
            $memoryUsage = memory_get_usage(true) / 1024 / 1024;
            if ($memoryUsage > 512) { // If using more than 512MB
                gc_collect_cycles();
            }
        }
    }

    private function performAutoIndexing(string $model): bool
    {
        try {
            if ($model === 'All') {
                // Only index campaigns for now
                $exitCode = Artisan::call('scout:queue-import', ['model' => Campaign::class]);

                return $exitCode === 0;
            }
            if ($model === 'Campaign') {
                $exitCode = Artisan::call('scout:queue-import', ['model' => Campaign::class]);

                return $exitCode === 0;
            }
            // Skip indexing for User and Donation models for now

            return true;
        } catch (Exception) {
            // Auto-indexing failed, but don't fail the entire seeding operation
            return false;
        }
    }
}
