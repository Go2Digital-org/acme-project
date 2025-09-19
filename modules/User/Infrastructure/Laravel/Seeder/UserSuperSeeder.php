<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Seeder;

use DateTime;
use Modules\Shared\Infrastructure\Laravel\Seeder\SuperSeed\PerformanceMonitor;
use Modules\Shared\Infrastructure\Laravel\Seeder\SuperSeed\PerformanceSuperSeeder;

/**
 * Optimized User Super Seeder for 20K+ Concurrent Employees
 *
 * Performance Features:
 * - Raw SQL batch inserts for maximum speed
 * - Pre-generated realistic employee data
 * - Memory-efficient batch processing
 * - Realistic corporate hierarchy distribution
 * - Optimized for enterprise scale
 */
class UserSuperSeeder extends PerformanceSuperSeeder
{
    /** @var array<string, mixed> */
    private array $departmentDistribution;

    /** @var array<string, mixed> */
    private array $roleDistribution;

    /** @var array<string, mixed> */
    private array $locationDistribution;

    /** @var array<string, mixed> */
    private array $preGeneratedData;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(PerformanceMonitor $monitor, array $options = [])
    {
        // Optimize for user records - typically 2KB per record
        $this->minBatchSize = 5000;
        $this->maxBatchSize = 50000;
        $this->targetMemoryUsage = 150 * 1024 * 1024; // 150MB per batch

        parent::__construct($monitor, $options);

        $this->initializeDistributions();
        $this->preGenerateStaticData();
    }

    /**
     * Get target table name for raw inserts
     */
    protected function getTableName(): string
    {
        return 'users';
    }

    /**
     * Estimate memory size of a single user record
     */
    protected function estimateRecordSize(): int
    {
        // User record with all fields: ~2KB
        return 2048;
    }

    /**
     * Generate batch of user data optimized for enterprise scale
     *
     * @return list<array<string, mixed>>
     */
    protected function generateBatchData(int $batchSize): array
    {
        $batch = [];
        now();

        // Pre-calculate some values to reduce per-record overhead
        /** @var array<int, string> */
        $domains = ['acme.com', 'acmecorp.com', 'acme-group.com'];
        $passwordHash = bcrypt('password'); // Same for all demo users

        for ($i = 0; $i < $batchSize; $i++) {
            $userId = $this->generateEmployeeId();
            $department = $this->selectDepartment();
            $role = $this->selectRole($department);
            $location = $this->selectLocation();
            $firstName = $this->generateFakeData('name');
            $lastName = $this->generateLastName();

            // Generate email with corporate domain
            $email = $this->generateCorporateEmail($firstName, $lastName, $domains);

            // Realistic join dates (mostly within last 5 years)
            $joinDate = $this->generateJoinDate();

            $batch[] = [
                'name' => $firstName . ' ' . $lastName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'email_verified_at' => $joinDate->modify('+' . random_int(1, 7) . ' days'),
                'password' => $passwordHash,
                'user_id' => $userId,
                'department' => $department,
                'position' => $role,
                'location' => $location,
                'phone' => $this->generateCorporatePhone(),
                'hire_date' => $joinDate->format('Y-m-d'),
                'salary' => $this->generateSalaryForRole($role),
                'manager_id' => null, // Will be populated in post-processing
                'is_active' => random_int(1, 100) <= 95 ? 1 : 0, // 95% active employees
                'created_at' => $joinDate->format('Y-m-d H:i:s'),
                'updated_at' => $joinDate->format('Y-m-d H:i:s'),
            ];
        }

        // @phpstan-ignore-next-line
        return $batch;
    }

    /**
     * Initialize realistic corporate distributions
     */
    private function initializeDistributions(): void
    {
        // Realistic department distribution for large corporation
        $this->departmentDistribution = [
            'Engineering' => 25,
            'Sales' => 20,
            'Marketing' => 12,
            'Customer Success' => 10,
            'Operations' => 10,
            'Human Resources' => 8,
            'Finance' => 7,
            'Legal' => 3,
            'Executive' => 2,
            'IT' => 3,
        ];

        // Role distribution by seniority
        $this->roleDistribution = [
            'Junior' => 40,
            'Mid-Level' => 35,
            'Senior' => 20,
            'Manager' => 4,
            'Director' => 1,
        ];

        // Office locations for global corporation
        $this->locationDistribution = [
            'New York, NY' => 30,
            'San Francisco, CA' => 25,
            'London, UK' => 15,
            'Toronto, CA' => 10,
            'Berlin, DE' => 8,
            'Singapore' => 7,
            'Sydney, AU' => 5,
        ];
    }

    /**
     * Pre-generate static data for performance
     */
    private function preGenerateStaticData(): void
    {
        // Pre-generate common names for better performance
        $this->preGeneratedData = [
            'first_names' => [
                'James', 'John', 'Robert', 'Michael', 'William', 'David', 'Richard', 'Joseph',
                'Thomas', 'Christopher', 'Charles', 'Daniel', 'Matthew', 'Anthony', 'Mark',
                'Mary', 'Patricia', 'Jennifer', 'Linda', 'Elizabeth', 'Barbara', 'Susan',
                'Jessica', 'Sarah', 'Karen', 'Nancy', 'Lisa', 'Betty', 'Helen', 'Sandra',
                'Emma', 'Olivia', 'Ava', 'Isabella', 'Sophia', 'Charlotte', 'Mia', 'Amelia',
                'Harper', 'Evelyn', 'Abigail', 'Emily', 'Ella', 'Madison', 'Scarlett',
                'Liam', 'Noah', 'Oliver', 'Elijah', 'William', 'James', 'Benjamin', 'Lucas',
                'Henry', 'Alexander', 'Mason', 'Michael', 'Ethan', 'Daniel', 'Jacob',
            ],
            'last_names' => [
                'Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis',
                'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson',
                'Taylor', 'Thomas', 'Jackson', 'White', 'Harris', 'Martin', 'Thompson', 'Garcia',
                'Martinez', 'Robinson', 'Clark', 'Lewis', 'Lee', 'Walker', 'Hall', 'Allen',
                'Young', 'Hernandez', 'King', 'Wright', 'Lopez', 'Hill', 'Scott', 'Green',
                'Adams', 'Baker', 'Gonzalez', 'Nelson', 'Carter', 'Mitchell', 'Perez', 'Roberts',
            ],
            'departments' => array_keys($this->departmentDistribution),
            'locations' => array_keys($this->locationDistribution),
        ];
    }

    /**
     * Generate unique employee ID with corporate format
     */
    private function generateEmployeeId(): string
    {
        static $counter = 10000;

        return 'EMP' . str_pad((string) ++$counter, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate corporate email address
     */
    /**
     * @param  array<int, string>  $domains
     */
    private function generateCorporateEmail(string $firstName, string $lastName, array $domains): string
    {
        $domain = $domains[array_rand($domains)];
        $username = strtolower($firstName . '.' . $lastName);

        // Clean username for email
        $username = preg_replace('/[^a-z0-9.]/', '', $username);

        // Add number if needed to ensure uniqueness
        /** @var array<string, int> */
        static $emailCounter = [];
        $baseUsername = $username;
        $counter = $emailCounter[$baseUsername] ?? 0;
        $emailCounter[$baseUsername] = $counter + 1;

        if ($counter > 0) {
            $username .= $counter;
        }

        return $username . '@' . $domain;
    }

    /**
     * Generate corporate phone number
     */
    private function generateCorporatePhone(): string
    {
        $areaCode = ['212', '415', '646', '628', '929', '917'][array_rand(['212', '415', '646', '628', '929', '917'])];
        $exchange = str_pad((string) random_int(200, 999), 3, '0', STR_PAD_LEFT);
        $number = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

        return "+1 ({$areaCode}) {$exchange}-{$number}";
    }

    /**
     * Select department based on realistic distribution
     */
    private function selectDepartment(): string
    {
        return $this->weightedRandomSelection($this->departmentDistribution);
    }

    /**
     * Select role based on department and hierarchy
     */
    private function selectRole(string $department): string
    {
        $baseRole = $this->weightedRandomSelection($this->roleDistribution);

        // Combine with department-specific titles
        $departmentRoles = match ($department) {
            'Engineering' => ['Software Engineer', 'Frontend Developer', 'Backend Developer', 'DevOps Engineer', 'QA Engineer'],
            'Sales' => ['Sales Representative', 'Account Executive', 'Sales Development Representative', 'Account Manager'],
            'Marketing' => ['Marketing Specialist', 'Content Creator', 'Digital Marketer', 'Brand Manager'],
            'Customer Success' => ['Customer Success Manager', 'Support Specialist', 'Account Manager'],
            'Operations' => ['Operations Analyst', 'Operations Manager', 'Business Analyst'],
            'Human Resources' => ['HR Specialist', 'Recruiter', 'HR Business Partner', 'Talent Acquisition'],
            'Finance' => ['Financial Analyst', 'Accountant', 'Finance Manager', 'Controller'],
            'Legal' => ['Legal Counsel', 'Paralegal', 'Compliance Officer'],
            'Executive' => ['VP', 'SVP', 'Chief Officer', 'President'],
            'IT' => ['IT Specialist', 'Systems Administrator', 'Network Engineer', 'Security Analyst'],
            default => ['Specialist'],
        };

        $specificRole = $departmentRoles[array_rand($departmentRoles)];

        // Add seniority level
        if ($baseRole === 'Manager') {
            return $specificRole . ' Manager';
        }

        if ($baseRole === 'Director') {
            return 'Director of ' . $department;
        }

        if ($baseRole === 'Senior') {
            return 'Senior ' . $specificRole;
        }

        if ($baseRole === 'Junior') {
            return 'Junior ' . $specificRole;
        }

        return $specificRole;
    }

    /**
     * Select location based on distribution
     */
    private function selectLocation(): string
    {
        return $this->weightedRandomSelection($this->locationDistribution);
    }

    /**
     * Generate realistic join date
     */
    private function generateJoinDate(): DateTime
    {
        // Most employees joined within last 5 years, with heavier weight on recent years
        /** @var array<string, int> */
        $weights = [
            '-5 years' => 10,
            '-4 years' => 15,
            '-3 years' => 20,
            '-2 years' => 25,
            '-1 year' => 20,
            '-6 months' => 10,
        ];

        $period = $this->weightedRandomSelection($weights);

        return fake()->dateTimeBetween($period, '-1 month');
    }

    /**
     * Generate salary based on role level
     */
    private function generateSalaryForRole(string $role): int
    {
        // Add location adjustment
        return match (true) {
            str_contains($role, 'Junior') => random_int(45000, 65000),
            str_contains($role, 'Senior') => random_int(85000, 120000),
            str_contains($role, 'Manager') => random_int(100000, 150000),
            str_contains($role, 'Director') => random_int(150000, 250000),
            str_contains($role, 'VP') || str_contains($role, 'Chief') => random_int(200000, 400000),
            default => random_int(60000, 90000), // Mid-level
        };
    }

    /**
     * Get random last name from pre-generated list
     */
    private function generateLastName(): string
    {
        return $this->preGeneratedData['last_names'][array_rand($this->preGeneratedData['last_names'])];
    }

    /**
     * Weighted random selection helper
     *
     * @param  array<string, int>  $weights
     */
    private function weightedRandomSelection(array $weights): string
    {
        $totalWeight = array_sum($weights);
        $randomNumber = random_int(1, $totalWeight);

        $currentWeight = 0;
        foreach ($weights as $item => $weight) {
            $currentWeight += $weight;
            if ($randomNumber <= $currentWeight) {
                return $item;
            }
        }

        // Fallback to first item
        return (string) array_key_first($weights);
    }
}
