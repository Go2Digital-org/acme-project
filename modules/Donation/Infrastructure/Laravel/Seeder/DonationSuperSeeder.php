<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Seeder;

use DateTime;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Infrastructure\Laravel\Seeder\SuperSeed\BatchProcessor;
use Modules\Shared\Infrastructure\Laravel\Seeder\SuperSeed\PerformanceMonitor;
use Modules\Shared\Infrastructure\Laravel\Seeder\SuperSeed\PerformanceSuperSeeder;
use RuntimeException;

/**
 * Optimized Donation Super Seeder for Trillion-Scale Data Generation
 *
 * Performance Features:
 * - Raw SQL batch inserts for maximum speed
 * - Realistic donation patterns and distributions
 * - Memory-efficient batch processing with payments
 * - Campaign funding simulation
 * - Enterprise-scale transaction processing
 */
class DonationSuperSeeder extends PerformanceSuperSeeder
{
    /** @var array<string, mixed> */
    private array $userIds;

    /** @var array<int, int> */
    private array $campaignIds;

    /** @var array<string, mixed> */
    private array $campaignGoals;

    /** @var array<string, mixed> */
    private array $donationPatterns;

    /** @var array<string, mixed> */
    private array $paymentMethods;

    private int $paymentsGenerated = 0;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(PerformanceMonitor $monitor, array $options = [])
    {
        // Optimize for donation records - typically 1.5KB per record + payment
        $this->minBatchSize = 10000;
        $this->maxBatchSize = 75000;
        $this->targetMemoryUsage = 200 * 1024 * 1024; // 200MB per batch

        parent::__construct($monitor, $options);

        $this->loadReferenceData();
        $this->initializeDonationPatterns();
    }

    /**
     * Get target table name for raw inserts
     */
    protected function getTableName(): string
    {
        return 'donations';
    }

    /**
     * Estimate memory size of a single donation record (with payment)
     */
    protected function estimateRecordSize(): int
    {
        // Donation record + associated payment record: ~1.5KB
        return 1536;
    }

    /**
     * Generate batch of donation data optimized for massive scale
     *
     * @return list<array<string, mixed>>
     */
    protected function generateBatchData(int $batchSize): array
    {
        $batch = [];

        for ($i = 0; $i < $batchSize; $i++) {
            $campaign = $this->selectRandomCampaign();
            $user = $this->selectRandomUser();
            $amount = $this->generateRealisticDonationAmount();
            $status = $this->selectDonationStatus();
            $donationDate = $this->generateDonationDate();

            $donation = [
                'campaign_id' => $campaign['id'],
                'user_id' => $user,
                'amount' => $amount,
                'currency' => 'USD',
                'status' => $status,
                'donation_type' => $this->selectDonationType(),
                'is_anonymous' => random_int(1, 100) <= 15 ? 1 : 0, // 15% anonymous
                'donor_name' => random_int(1, 100) <= 85 ? null : $this->generateDonorName(), // 15% have custom names
                'donor_email' => random_int(1, 100) <= 90 ? null : $this->generateDonorEmail(), // 10% have custom emails
                'message' => random_int(1, 100) <= 25 ? $this->generateDonationMessage() : null, // 25% have messages
                'payment_method' => $this->selectPaymentMethod(),
                'transaction_id' => $this->generateTransactionId(),
                'gateway_response' => $status === 'completed' ? $this->generateGatewayResponse() : null,
                'fee_amount' => $status === 'completed' ? round($amount * 0.029 + 0.30, 2) : 0, // Realistic fee
                'net_amount' => $status === 'completed' ? round($amount - (($amount * 0.029) + 0.30), 2) : 0,
                'donated_at' => $donationDate->format('Y-m-d H:i:s'),
                'created_at' => $donationDate->format('Y-m-d H:i:s'),
                'updated_at' => $donationDate->format('Y-m-d H:i:s'),
            ];

            $batch[] = $donation;
        }

        return $batch;
    }

    /**
     * Override to also generate payments after donations
     */
    public function executeNextBatch(): int
    {
        $donationCount = parent::executeNextBatch();

        // Generate corresponding payment records for completed donations
        if ($donationCount > 0) {
            $this->generatePaymentBatch($donationCount);
        }

        return $donationCount;
    }

    /**
     * Load reference data for foreign keys
     */
    private function loadReferenceData(): void
    {
        // Cache user IDs for random selection
        $this->userIds = DB::table('users')->pluck('id')->toArray();

        if ($this->userIds === []) {
            throw new RuntimeException('No users found. Please run UserSuperSeeder first.');
        }

        // Cache campaign data with goals for realistic funding simulation
        $campaigns = DB::table('campaigns')
            ->select('id', 'goal_amount', 'current_amount')
            ->get();

        if ($campaigns->isEmpty()) {
            throw new RuntimeException('No campaigns found. Please run CampaignSeeder first.');
        }

        $this->campaignIds = [];
        $this->campaignGoals = [];

        foreach ($campaigns as $campaign) {
            $this->campaignIds[] = $campaign->id;
            $this->campaignGoals[$campaign->id] = [
                'goal' => $campaign->goal_amount,
                'current' => $campaign->current_amount,
            ];
        }
    }

    /**
     * Initialize realistic donation patterns
     */
    private function initializeDonationPatterns(): void
    {
        // Realistic donation amount distribution
        $this->donationPatterns = [
            'micro' => ['min' => 5, 'max' => 25, 'weight' => 40],    // $5-25 (40%)
            'small' => ['min' => 25, 'max' => 100, 'weight' => 35],  // $25-100 (35%)
            'medium' => ['min' => 100, 'max' => 500, 'weight' => 20], // $100-500 (20%)
            'large' => ['min' => 500, 'max' => 2000, 'weight' => 4],  // $500-2000 (4%)
            'major' => ['min' => 2000, 'max' => 10000, 'weight' => 1], // $2000+ (1%)
        ];

        // Payment method distribution
        $this->paymentMethods = [
            'credit_card' => 70,
            'debit_card' => 15,
            'paypal' => 10,
            'bank_transfer' => 3,
            'apple_pay' => 1,
            'google_pay' => 1,
        ];
    }

    /**
     * Generate realistic donation amount based on distribution
     */
    private function generateRealisticDonationAmount(): float
    {
        $pattern = $this->weightedRandomSelection($this->donationPatterns);

        // Ensure we have a valid pattern array with min/max values
        if (!is_array($pattern) || !isset($pattern['min']) || !isset($pattern['max'])) {
            // Fallback to default pattern if selection failed
            $pattern = ['min' => 10, 'max' => 100];
        }

        $min = (int) $pattern['min'];
        $max = (int) $pattern['max'];

        // Generate amount with some randomness but weighted toward round numbers
        $amount = random_int($min * 100, $max * 100) / 100;

        // Bias toward round numbers
        if (random_int(1, 100) <= 30) {
            $roundAmounts = [10, 25, 50, 100, 250, 500, 1000];
            $validRounds = array_filter($roundAmounts, fn (int $round): bool => $round >= $min && $round <= $max);

            if ($validRounds !== []) {
                $amount = $validRounds[array_rand($validRounds)];
            }
        }

        return round($amount, 2);
    }

    /**
     * Select donation status with realistic distribution
     */
    private function selectDonationStatus(): string
    {
        $weights = [
            'completed' => 90,
            'failed' => 6,
            'cancelled' => 2,
            'pending' => 2,
        ];

        $result = $this->weightedRandomSelection($weights);

        return is_string($result) ? $result : 'completed';
    }

    /**
     * Select donation type
     */
    private function selectDonationType(): string
    {
        $weights = [
            'one_time' => 85,
            'recurring' => 15,
        ];

        $result = $this->weightedRandomSelection($weights);

        return is_string($result) ? $result : 'one_time';
    }

    /**
     * Generate realistic donation date
     */
    private function generateDonationDate(): DateTime
    {
        // Most donations in recent months, with realistic patterns
        $weights = [
            '-6 months' => 5,
            '-3 months' => 15,
            '-2 months' => 20,
            '-1 month' => 25,
            '-2 weeks' => 20,
            '-1 week' => 15,
        ];

        $period = $this->weightedRandomSelection($weights);
        $periodString = is_string($period) ? $period : '-1 month';
        $date = fake()->dateTimeBetween($periodString, 'now');

        // Add realistic time patterns (more donations during business hours)
        $hour = $this->generateRealisticHour();
        $minute = random_int(0, 59);
        $date->setTime($hour, $minute, random_int(0, 59));

        return $date;
    }

    /**
     * Generate realistic hour with business hour bias
     */
    private function generateRealisticHour(): int
    {
        $weights = [
            0 => 1, 1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1,
            6 => 2, 7 => 3, 8 => 5, 9 => 8, 10 => 10, 11 => 12,
            12 => 15, 13 => 12, 14 => 10, 15 => 8, 16 => 6,
            17 => 5, 18 => 4, 19 => 3, 20 => 3, 21 => 2, 22 => 2, 23 => 1,
        ];

        $result = $this->weightedRandomSelection($weights);

        return is_numeric($result) ? (int) $result : 12;
    }

    /**
     * Select payment method
     */
    private function selectPaymentMethod(): string
    {
        $result = $this->weightedRandomSelection($this->paymentMethods);

        return is_string($result) ? $result : 'credit_card';
    }

    /**
     * Generate transaction ID
     */
    private function generateTransactionId(): string
    {
        return 'txn_' . strtolower(substr(md5(uniqid((string) random_int(0, mt_getrandmax()), true)), 0, 16));
    }

    /**
     * Generate gateway response for successful transactions
     */
    private function generateGatewayResponse(): string
    {
        $responses = [
            'approved',
            'authorized',
            'captured',
            'settled',
            'processed',
        ];

        return $responses[array_rand($responses)];
    }

    /**
     * Generate donation message
     */
    private function generateDonationMessage(): string
    {
        $messages = [
            'Keep up the great work!',
            'Happy to support this cause.',
            'Every little bit helps.',
            'Thank you for making a difference.',
            'Proud to be part of this mission.',
            'In honor of my family.',
            'Great initiative!',
            'Hope this helps reach your goal.',
            'Supporting from afar.',
            'Wishing you all the best.',
        ];

        return $messages[array_rand($messages)];
    }

    /**
     * Generate donor name for custom donations
     */
    private function generateDonorName(): string
    {
        return fake()->firstName() . ' ' . fake()->lastName();
    }

    /**
     * Generate donor email for custom donations
     */
    private function generateDonorEmail(): string
    {
        return fake()->unique()->safeEmail();
    }

    /**
     * Select random campaign with weighted selection toward active campaigns
     */
    /**
     * @return array<string, mixed>
     */
    private function selectRandomCampaign(): array
    {
        $campaignId = $this->campaignIds[array_rand($this->campaignIds)];

        return [
            'id' => $campaignId,
            'goal' => $this->campaignGoals[$campaignId]['goal'] ?? 1000,
            'current' => $this->campaignGoals[$campaignId]['current'] ?? 0,
        ];
    }

    /**
     * Select random user
     */
    private function selectRandomUser(): int
    {
        return $this->userIds[array_rand($this->userIds)];
    }

    /**
     * Generate payment batch for completed donations
     */
    private function generatePaymentBatch(int $donationCount): void
    {
        // Get the most recent donations that were completed
        $recentDonations = DB::table('donations')
            ->where('status', 'completed')
            ->orderBy('id', 'desc')
            ->limit($donationCount)
            ->get(['id', 'amount', 'payment_method', 'transaction_id', 'donated_at']);

        if ($recentDonations->isEmpty()) {
            return;
        }

        $paymentBatch = [];

        foreach ($recentDonations as $donation) {
            $paymentBatch[] = [
                'donation_id' => $donation->id,
                'amount' => $donation->amount,
                'currency' => 'USD',
                'payment_method' => $donation->payment_method,
                'gateway' => $this->selectPaymentGateway($donation->payment_method),
                'gateway_transaction_id' => $donation->transaction_id,
                'status' => 'captured',
                'gateway_fee' => round($donation->amount * 0.029 + 0.30, 2),
                'net_amount' => round($donation->amount - (($donation->amount * 0.029) + 0.30), 2),
                'captured_at' => $donation->donated_at,
                'created_at' => $donation->donated_at,
                'updated_at' => $donation->donated_at,
            ];
        }

        // Batch insert payments
        if ($paymentBatch !== []) {
            $paymentProcessor = new BatchProcessor('payments');
            $this->paymentsGenerated += $paymentProcessor->insertBatch($paymentBatch);
        }
    }

    /**
     * Select payment gateway based on method
     */
    private function selectPaymentGateway(string $method): string
    {
        return match ($method) {
            'credit_card', 'debit_card' => 'stripe',
            'paypal' => 'paypal',
            'bank_transfer' => 'plaid',
            'apple_pay' => 'stripe',
            'google_pay' => 'stripe',
            default => 'stripe',
        };
    }

    /**
     * Weighted random selection helper
     *
     * @param  array<string|int, mixed>  $weights
     * @return array<string, mixed>|string|int
     */
    private function weightedRandomSelection(array $weights): array|string|int
    {
        // If weights is associative array with weight values
        $firstValue = current($weights);
        if (is_array($firstValue) && isset($firstValue['weight'])) {
            $totalWeight = array_sum(array_column($weights, 'weight'));
            $randomNumber = random_int(1, $totalWeight);

            $currentWeight = 0;
            foreach ($weights as $key => $data) {
                if (!is_array($data) || !isset($data['weight'])) {
                    continue;
                }
                $currentWeight += $data['weight'];
                if ($randomNumber <= $currentWeight) {
                    return $data;
                }
            }
        }

        // If weights is simple associative array
        $totalWeight = (int) array_sum(array_filter($weights, 'is_numeric'));
        if ($totalWeight > 0) {
            $randomNumber = random_int(1, $totalWeight);

            $currentWeight = 0;
            foreach ($weights as $item => $weight) {
                if (!is_numeric($weight)) {
                    continue;
                }
                $currentWeight += $weight;
                if ($randomNumber <= $currentWeight) {
                    return $item;
                }
            }
        }

        // Fallback
        $firstKey = array_key_first($weights);

        return $firstKey ?? '';
    }

    /**
     * Get additional metrics for donations
     */
    /**
     * @return array<string, mixed>
     */
    public function getMetrics(): array
    {
        $baseMetrics = $this->getProgress();

        return array_merge($baseMetrics, [
            'payments_generated' => $this->paymentsGenerated,
            'total_campaigns' => count($this->campaignIds),
            'total_users' => count($this->userIds),
        ]);
    }
}
