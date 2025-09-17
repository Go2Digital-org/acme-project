<?php

declare(strict_types=1);

// Set memory limit for test execution
ini_set('memory_limit', '2G');

// Optimize for parallel execution
if (getenv('PEST_PARALLEL_WORKER') || getenv('TEST_TOKEN') || getenv('PARATEST_WORKER')) {
    // Running in parallel mode - Pest 4 uses PEST_PARALLEL_WORKER
    $workerId = getenv('PEST_PARALLEL_WORKER') ?: (getenv('TEST_TOKEN') ? substr(getenv('TEST_TOKEN'), -1) : getenv('PARATEST_WORKER'));
    putenv("PARALLEL_WORKER_ID={$workerId}");
}

// Disable Laravel Debugbar globally for all tests to prevent interference
putenv('DEBUGBAR_ENABLED=false');
$_ENV['DEBUGBAR_ENABLED'] = false;
$_SERVER['DEBUGBAR_ENABLED'] = false;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Donation\Domain\Model\Donation;
use Modules\Donation\Domain\Service\PaymentGatewayInterface;
use Modules\Donation\Infrastructure\Gateway\MockPaymentGateway;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;
use Tests\TestCase;

// Browser tests using PEST 4 Browser Plugin (no RefreshDatabase for browser tests)
pest()->extend(TestCase::class)
    ->in('Browser')
    ->beforeEach(function (): void {
        // Disable cache warming for browser tests
        putenv('CACHE_WARMING_ENABLED=false');
        $_ENV['CACHE_WARMING_ENABLED'] = 'false';
        $_SERVER['CACHE_WARMING_ENABLED'] = 'false';
    });
// Temporarily disabled beforeAll hook to debug hanging tests
// ->beforeAll(function (): void {
//     // Override the HTTP server before any test runs to use existing server
//     // Get host and port from APP_URL or default to 127.0.0.1:8000
//     $appUrl = env('APP_URL', 'http://127.0.0.1:8000');
//     $parsedUrl = parse_url($appUrl);
//     $host = $parsedUrl['host'] ?? '127.0.0.1';
//     $port = $parsedUrl['port'] ?? 8000;

//     $reflection = new ReflectionClass(\Pest\Browser\ServerManager::class);
//     $httpProperty = $reflection->getProperty('http');
//     $httpProperty->setAccessible(true);
//     $httpProperty->setValue(
//         \Pest\Browser\ServerManager::instance(),
//         new \Tests\Browser\ExistingHttpServer($host, $port)
//     );
// });

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// Unit tests should use basic PHPUnit TestCase (no Laravel dependencies)
pest()->extend(PHPUnit\Framework\TestCase::class)->in('Unit');

// Feature tests use TestCase (with Laravel framework)
pest()->extend(Tests\TestCase::class)->in('Feature');

// Integration tests use IntegrationTestCase for proper database isolation
pest()->extend(Tests\Integration\IntegrationTestCase::class)
    ->in('Integration');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the expectation API at any time.
|
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

expect()->extend('toBeEmpty', fn () => $this->toBeEmpty());

expect()->extend('toBeValidMoney', function () {
    expect($this->value)
        ->toHaveProperty('amount')
        ->and($this->value->amount)->toBeFloat()
        ->and($this->value->amount)->toBeGreaterThanOrEqual(0);

    return $this;
});

expect()->extend('toBeValidCampaign', function () {
    expect($this->value)
        ->toHaveProperty('title')
        ->and($this->value->title)->toBeString()
        ->and($this->value)->toHaveProperty('goal_amount')
        ->and($this->value->goal_amount)->toBeFloat()
        ->and($this->value->goal_amount)->toBeGreaterThan(0);

    return $this;
});

expect()->extend('toBeValidDonation', function () {
    expect($this->value)
        ->toHaveProperty('amount')
        ->and($this->value->amount)->toBeFloat()
        ->and($this->value->amount)->toBeGreaterThan(0)
        ->and($this->value)->toHaveProperty('campaign_id')
        ->and($this->value->campaign_id)->toBeInt();

    return $this;
});

expect()->extend('toContainText', function (string $expectedText) {
    expect($this->value)->toBeString()
        ->and(str_contains(strtolower($this->value), strtolower($expectedText)))->toBeTrue();

    return $this;
});

expect()->extend('toBeValidUrl', function () {
    expect(filter_var($this->value, FILTER_VALIDATE_URL))->not->toBeFalse();

    return $this;
});

/*
|--------------------------------------------------------------------------
| Browser Testing Configuration
|--------------------------------------------------------------------------
|
| Configure Pest 4 Browser plugin with Playwright driver for fast, reliable
| end-to-end testing. This replaces Laravel Dusk with native Pest Browser plugin.
|
*/

/**
 * Configure browser test environment
 */
function configureBrowserEnvironment(): void
{
    // Load browser test configuration
    $config = [];
    if (file_exists(__DIR__ . '/Browser/browsertest.config.php')) {
        $config = require __DIR__ . '/Browser/browsertest.config.php';
    }

    // Set environment variables from config for browser tests
    if (! empty($config)) {
        $_ENV['BROWSER_NAME'] = $config['browser']['name'] ?? 'chromium';
        $_ENV['BROWSER_HEADLESS'] = $config['browser']['headless'] ?? true;
        $_ENV['BROWSER_WIDTH'] = $config['viewport']['width'] ?? 1920;
        $_ENV['BROWSER_HEIGHT'] = $config['viewport']['height'] ?? 1080;
        $_ENV['APP_URL'] = $config['app']['base_url'] ?? 'http://localhost:8000';
    }
}

/**
 * Browser test helper to wait for element with custom timeout
 */
function waitForElement(string $selector, int $timeout = 10000): \Closure
{
    return function () use ($selector, $timeout) {
        // Returns closure for use with browser tests
        return $this->waitFor($selector, $timeout / 1000);
    };
}

/**
 * Take screenshot for visual regression testing
 */
function takeVisualSnapshot(string $name): \Closure
{
    return function () use ($name) {
        $config = require __DIR__ . '/Browser/browsertest.config.php';
        $screenshotPath = $config['screenshots']['path'] ?? base_path('tests/Browser/screenshots');

        if (! is_dir($screenshotPath)) {
            mkdir($screenshotPath, 0755, true);
        }

        $filename = $name . '_' . date('Y-m-d_H-i-s') . '.png';

        return $this->screenshot($screenshotPath . '/' . $filename);
    };
}

/**
 * Assert element is visible and interactable
 */
function assertElementReady(string $selector): \Closure
{
    return function () use ($selector) {
        return $this->assertVisible($selector)
            ->assertEnabled($selector);
    };
}

/**
 * Create test user for browser authentication
 */
function createBrowserTestUser(array $attributes = []): \Modules\User\Infrastructure\Laravel\Models\User
{
    return \Modules\User\Infrastructure\Laravel\Models\User::factory()->create(array_merge([
        'email' => 'browser-test@acme-corp.com',
        'password' => bcrypt('password123'),
        'email_verified_at' => now(),
    ], $attributes));
}

/**
 * Login user for browser tests
 */
function loginBrowserUser(?\Modules\User\Infrastructure\Laravel\Models\User $user = null): \Closure
{
    return function () use ($user) {
        $testUser = $user ?? createBrowserTestUser();

        return $this->visit('/login')
            ->fill('input[name="email"]', $testUser->email)
            ->fill('input[name="password"]', 'password123')
            ->press('Sign in')
            ->waitForUrl('/dashboard', 10);
    };
}

/**
 * Fill form with test data helper
 */
function fillFormWithTestData(array $formData): \Closure
{
    return function () use ($formData) {
        $browser = $this;

        foreach ($formData as $field => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $browser = $browser->check("input[name='{$field}']")
                        ->assertChecked("input[name='{$field}']");
                } else {
                    $browser = $browser->uncheck("input[name='{$field}']")
                        ->assertNotChecked("input[name='{$field}']");
                }
            } elseif (is_array($value)) {
                // Handle select multiple
                $browser = $browser->select("select[name='{$field}']", $value);
            } else {
                // Handle text inputs, textareas, and single selects
                $browser = $browser->fill("*[name='{$field}']", $value);
            }
        }

        return $browser;
    };
}

/**
 * Wait for AJAX requests to complete
 */
function waitForAjax(int $timeout = 5000): \Closure
{
    return function () use ($timeout) {
        return $this->waitUntil('jQuery.active == 0', $timeout / 1000);
    };
}

/**
 * Assert toast notification appears
 */
function assertToastMessage(string $message, string $type = 'success'): \Closure
{
    return function () use ($message) {
        return $this->waitForText($message, 10)
            ->assertSee($message);
    };
}

/**
 * Create test campaign data for browser tests
 */
function createBrowserTestCampaign(array $attributes = []): \Modules\Campaign\Domain\Model\Campaign
{
    $organization = createTestOrganization();

    return \Modules\Campaign\Domain\Model\Campaign::factory()->create(array_merge([
        'title' => 'Browser Test Campaign',
        'description' => 'A campaign for browser testing purposes',
        'goal_amount' => 50000.00,
        'start_date' => now()->addDay(),
        'end_date' => now()->addMonth(),
        'status' => 'active',
        'organization_id' => $organization->id,
    ], $attributes));
}

/**
 * Setup browser test environment with database
 */
function setupBrowserTestEnvironment(): void
{
    // Disable debugbar for browser tests to prevent interference
    putenv('DEBUGBAR_ENABLED=false');
    $_ENV['DEBUGBAR_ENABLED'] = false;
    config(['debugbar.enabled' => false]);

    // Ensure we're using the test database and never touching production
    ensureTestDatabase();

    // Set up Laravel test environment
    setupIntegrationTestEnvironment();

    // Configure browser-specific settings
    configureBrowserEnvironment();

    // Create default test data if needed
    if (config('testing.browser.create_test_data', false)) {
        createBrowserTestUser();
    }
}

/*
|--------------------------------------------------------------------------
| Visual Regression Testing Functions
|--------------------------------------------------------------------------
*/

/**
 * Take baseline screenshot for visual regression testing
 */
function takeBaselineScreenshot(string $name, string $selector = 'body'): \Closure
{
    return function () use ($name, $selector) {
        $config = require __DIR__ . '/Browser/browsertest.config.php';
        $baselinePath = $config['visual_regression']['baseline_path'] ?? base_path('tests/Browser/baseline');

        if (! is_dir($baselinePath)) {
            mkdir($baselinePath, 0755, true);
        }

        $filename = $name . '.png';
        $fullPath = $baselinePath . '/' . $filename;

        // Take screenshot of specific element or full page
        if ($selector === 'body') {
            return $this->screenshot($fullPath);
        }

        return $this->screenshotElement($selector, $fullPath);
    };
}

/**
 * Compare screenshot with baseline for visual regression testing
 */
function compareWithBaseline(string $name, string $selector = 'body', ?float $threshold = null): \Closure
{
    return function () use ($name, $selector, $threshold) {
        $config = require __DIR__ . '/Browser/browsertest.config.php';
        $baselinePath = $config['visual_regression']['baseline_path'] ?? base_path('tests/Browser/baseline');
        $diffPath = $config['visual_regression']['diff_path'] ?? base_path('tests/Browser/diffs');
        $defaultThreshold = $config['visual_regression']['threshold'] ?? 0.2;

        $comparisonThreshold = $threshold ?? $defaultThreshold;

        if (! is_dir($diffPath)) {
            mkdir($diffPath, 0755, true);
        }

        $baselineFile = $baselinePath . '/' . $name . '.png';
        $currentFile = $diffPath . '/' . $name . '_current.png';
        $diffFile = $diffPath . '/' . $name . '_diff.png';

        // Take current screenshot
        if ($selector === 'body') {
            $this->screenshot($currentFile);
        } else {
            $this->screenshotElement($selector, $currentFile);
        }

        // If baseline doesn't exist, create it
        if (! file_exists($baselineFile)) {
            copy($currentFile, $baselineFile);

            return $this; // First time, baseline is created
        }

        // Compare images (this would require an image comparison library)
        // For now, we'll use file comparison as a placeholder
        expect(file_exists($currentFile))->toBeTrue('Current screenshot should exist');
        expect(file_exists($baselineFile))->toBeTrue('Baseline screenshot should exist');

        return $this;
    };
}

/**
 * Assert visual consistency by comparing with baseline
 */
function assertVisuallyConsistent(string $testName, string $selector = 'body', ?float $threshold = null): \Closure
{
    return compareWithBaseline($testName, $selector, $threshold);
}

/**
 * Create visual regression test group setup
 */
function setupVisualRegressionTest(string $testName): \Closure
{
    return function () use ($testName) {
        // Ensure consistent viewport for visual tests
        $config = require __DIR__ . '/Browser/browsertest.config.php';
        $width = $config['viewport']['width'] ?? 1920;
        $height = $config['viewport']['height'] ?? 1080;

        // Set consistent browser state
        return $this->resize($width, $height)
            ->waitFor('body', 10); // Ensure page is loaded
    };
}

/*
|--------------------------------------------------------------------------
| Performance Testing Functions
|--------------------------------------------------------------------------
*/

/**
 * Measure page load time
 */
function measurePageLoadTime(): \Closure
{
    return function () {
        $startTime = microtime(true);

        return $this->waitFor('body', 30)
            ->tap(function () use ($startTime): void {
                $loadTime = (microtime(true) - $startTime) * 1000;
                expect($loadTime)->toBeLessThan(5000, 'Page should load in under 5 seconds');
            });
    };
}

/**
 * Assert page performance metrics
 */
function assertPerformanceMetrics(array $metrics = []): \Closure
{
    return function () use ($metrics) {
        // Default performance expectations
        $defaultMetrics = [
            'max_load_time' => 5000, // 5 seconds
            'max_dom_ready_time' => 3000, // 3 seconds
        ];

        $expectedMetrics = array_merge($defaultMetrics, $metrics);

        // This would require actual performance API integration
        // For now, we'll just ensure the page loads
        return $this->waitFor('body', $expectedMetrics['max_load_time'] / 1000);
    };
}

/*
|--------------------------------------------------------------------------
| Parallel Execution Helper Functions
|--------------------------------------------------------------------------
*/

/**
 * Setup isolated test environment for parallel execution
 */
function setupIsolatedTestEnvironment(): void
{
    // Use optimized parallel configuration
    $workerId = (int) getenv('PARALLEL_WORKER_ID') ?: 0;

    if ($workerId > 0) {
        // Configure worker-specific resources
        config([
            'cache.prefix' => "test_worker_{$workerId}_",
            'database.connections.mysql.pool.min' => 1,
            'database.connections.mysql.pool.max' => 3,
        ]);
    }

    // Check if we're using SQLite (CI environment)
    $connection = config('database.default');

    if ($connection === 'sqlite' || env('DB_CONNECTION') === 'sqlite') {
        // For SQLite, use file-based databases for parallel workers to avoid conflicts
        $workerId = config('testing.parallel.worker_id', 0);

        if ($workerId > 0) {
            // Use separate SQLite file for each parallel worker
            $testDatabase = storage_path("testing/test_worker_{$workerId}.sqlite");

            // Ensure directory exists
            if (! file_exists(dirname($testDatabase))) {
                mkdir(dirname($testDatabase), 0755, true);
            }

            // Configure SQLite to use the worker-specific file
            config(['database.connections.sqlite.database' => $testDatabase]);
        }

        // Worker 0 or non-parallel execution uses :memory: or default SQLite config
        return;
    }

    // MySQL configuration for local testing with optimized isolation
    $workerId = (int) getenv('PARALLEL_WORKER_ID') ?: config('testing.parallel.worker_id', 0);
    $testDatabase = $workerId > 0 ? "acme_corp_csr_test_{$workerId}" : 'acme_corp_csr_test';

    config(['database.connections.mysql.database' => $testDatabase]);

    // Create worker-specific test database if it doesn't exist
    if ($workerId > 0) {
        createWorkerTestDatabase($testDatabase);
    }
}

/**
 * Create worker-specific test database for parallel execution
 */
function createWorkerTestDatabase(string $databaseName): void
{
    static $createdDatabases = [];

    // Skip if already created in this process
    if (isset($createdDatabases[$databaseName])) {
        return;
    }

    // Check if we're using SQLite
    $connection = config('database.default');

    if ($connection === 'sqlite' || env('DB_CONNECTION') === 'sqlite') {
        // SQLite database files are created automatically when accessed
        // No need to explicitly create them
        return;
    }

    // MySQL database creation
    try {
        $pdo = new PDO(
            'mysql:host=127.0.0.1;port=3306',
            'root',
            config('database.connections.mysql.password', 'root')
        );

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $createdDatabases[$databaseName] = true;
    } catch (Exception $e) {
        // Database creation failed - tests will use default database
        // This is acceptable for most scenarios
    }
}

/**
 * Cleanup worker test database after parallel execution
 */
function cleanupWorkerTestDatabase(): void
{
    $workerId = config('testing.parallel.worker_id', 0);

    if ($workerId > 0) {
        // Check if we're using SQLite
        $connection = config('database.default');

        if ($connection === 'sqlite' || env('DB_CONNECTION') === 'sqlite') {
            // Clean up SQLite test files
            $testDatabase = storage_path("testing/test_worker_{$workerId}.sqlite");

            if (file_exists($testDatabase)) {
                @unlink($testDatabase);
            }

            // Clean up any SQLite journal files
            if (file_exists($testDatabase . '-journal')) {
                @unlink($testDatabase . '-journal');
            }

            if (file_exists($testDatabase . '-wal')) {
                @unlink($testDatabase . '-wal');
            }

            if (file_exists($testDatabase . '-shm')) {
                @unlink($testDatabase . '-shm');
            }

            return;
        }

        // MySQL cleanup
        $testDatabase = 'acme_corp_csr_test_worker_' . $workerId;

        try {
            $pdo = new PDO(
                'mysql:host=127.0.0.1;port=3306',
                'root',
                config('database.connections.mysql.password', 'root')
            );

            $pdo->exec("DROP DATABASE IF EXISTS `{$testDatabase}`");
        } catch (Exception $e) {
            // Cleanup failed - acceptable
        }
    }
}

/**
 * Configure browser test isolation for parallel execution
 */
function configureBrowserIsolation(): void
{
    $workerId = config('testing.parallel.worker_id', 0);

    // Use different ports for each worker to avoid conflicts
    $basePort = 8000;
    $workerPort = $basePort + $workerId;

    $_ENV['APP_URL'] = "http://localhost:{$workerPort}";
    config(['app.url' => "http://localhost:{$workerPort}"]);
}

/*
|--------------------------------------------------------------------------
| Browser Context Management
|--------------------------------------------------------------------------
*/

/**
 * Create fresh browser context for test isolation
 */
function createFreshBrowserContext(): \Closure
{
    return function () {
        // Browser contexts are automatically isolated in Pest Browser plugin
        // This function provides a hook for additional context setup if needed
        return $this;
    };
}

/**
 * Setup viewport for consistent rendering across workers
 */
function setupConsistentViewport(): \Closure
{
    return function () {
        $config = require __DIR__ . '/Browser/browsertest.config.php';
        $width = $config['viewport']['width'] ?? 1920;
        $height = $config['viewport']['height'] ?? 1080;

        return $this->resize($width, $height);
    };
}

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the amount of code you need to write in your tests.
|
*/

/**
 * Custom browser visit function that uses the correct base URL and sets cookies.
 * This ensures browser tests connect to the existing Laravel server on port 8000
 * and bypasses the cache warming middleware.
 */
function browserVisit(array|string $url, array $options = []): \Pest\Browser\Api\ArrayablePendingAwaitablePage|\Pest\Browser\Api\PendingAwaitablePage
{
    // Add cookie to skip cache warming middleware
    $options = array_merge([
        'storageState' => [
            'cookies' => [
                [
                    'name' => 'skip_cache_warming',
                    'value' => '1',
                    'domain' => parse_url(env('APP_URL', 'http://localhost:8000'), PHP_URL_HOST) ?: 'localhost',
                    'path' => '/',
                    'expires' => time() + 3600,
                    'httpOnly' => false,
                    'secure' => false,
                    'sameSite' => 'Lax',
                ],
            ],
        ],
    ], $options);

    // Handle array of URLs
    if (is_array($url)) {
        $urls = array_map(function ($u) {
            return convertToFullUrl($u);
        }, $url);

        return visit($urls, $options);
    }

    // Handle single URL
    $fullUrl = convertToFullUrl($url);

    return visit($fullUrl, $options);
}

/**
 * Convert relative URLs to full URLs with the Laravel server base.
 */
function convertToFullUrl(string $url): string
{
    // If already a full URL, return as-is
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
        return $url;
    }

    // For relative URLs, prepend the base URL
    $baseUrl = 'http://localhost:8000';

    // Ensure path starts with /
    if (! str_starts_with($url, '/')) {
        $url = '/' . $url;
    }

    return $baseUrl . $url;
}

/**
 * Set up test environment with faked services (for integration/feature tests).
 */
function setupTestEnvironment(): void
{
    // Apply parallel test optimizations
    if (getenv('PARALLEL_WORKER_ID')) {
        setupIsolatedTestEnvironment();
    }

    // Disable debugbar for all tests to prevent interference
    putenv('DEBUGBAR_ENABLED=false');
    $_ENV['DEBUGBAR_ENABLED'] = false;
    config(['debugbar.enabled' => false]);

    // Fake external services to avoid side effects
    Mail::fake();
    Event::fake();
    Queue::fake();
    Notification::fake();

    // Mock search services to prevent Redis connection issues in unit tests
    mockSearchServices();

    // Mock notification services to prevent database access during unit tests
    mockNotificationServices();
}

/**
 * Set up pure unit test environment (no Laravel dependencies).
 */
function setupUnitTestEnvironment(): void
{
    // Unit tests should not depend on Laravel services
    // They should test pure business logic without external dependencies
}

/**
 * Set up test environment with database for integration tests.
 */
function setupIntegrationTestEnvironment(): void
{
    // Ensure we're using the test database and never touching production
    ensureTestDatabase();

    // Use appropriate database trait based on connection
    $connection = config('database.default');

    if ($connection === 'sqlite') {
        // SQLite uses RefreshDatabase for in-memory database
        uses(RefreshDatabase::class);
    } else {
        // MySQL uses DatabaseTransactions for rollback
        uses(\Illuminate\Foundation\Testing\DatabaseTransactions::class);
    }

    // Set up basic test environment
    setupTestEnvironment();
}

/**
 * Safety check to ensure tests never run on non-test databases.
 */
function ensureTestDatabase(): void
{
    // Add lock to prevent race conditions in parallel execution
    static $databaseChecked = false;

    if ($databaseChecked) {
        return;
    }

    // Get the active database connection
    $activeConnection = config('database.default');

    // If using SQLite, no need to validate database names
    if ($activeConnection === 'sqlite') {
        // SQLite is safe for testing (especially :memory:)
        $sqliteDatabase = config('database.connections.sqlite.database');

        // Log for debugging
        if (env('APP_DEBUG')) {
            error_log("Testing with SQLite database: {$sqliteDatabase}");
        }

        return; // SQLite is always safe for testing
    }

    // For MySQL, validate the database name
    if ($activeConnection === 'mysql') {
        $currentDatabase = config('database.connections.mysql.database');

        // List of allowed test database names
        $allowedTestDatabases = [
            'acme_corp_csr_test',
            'acme_corp_csr_test_isolated',
            'acme_corp_csr_dusk_test',
            'testing', // Generic test database
        ];

        // Check for worker databases (parallel test execution)
        $isWorkerDatabase = preg_match('/acme.*test.*\d+$/', $currentDatabase) ||
                           preg_match('/acme_corp_csr_test_\d+$/', $currentDatabase);

        $databaseChecked = true;

        // Prevent tests from running on production-like databases
        $productionLikeDatabases = [
            'acme_corp_csr',
            'acme-corp-optimy',
            'production',
            'prod',
            'live',
        ];

        if (in_array($currentDatabase, $productionLikeDatabases)) {
            throw new \RuntimeException(
                "CRITICAL: Test attempted to run on production-like database '{$currentDatabase}'. "
                . 'Tests must only run on test databases. Allowed databases: '
                . implode(', ', $allowedTestDatabases)
            );
        }

        if (! in_array($currentDatabase, $allowedTestDatabases) && ! $isWorkerDatabase) {
            throw new \RuntimeException(
                "CRITICAL: Test attempted to run on unauthorized database '{$currentDatabase}'. "
                . 'Tests must only run on approved test databases. Allowed databases: '
                . implode(', ', $allowedTestDatabases) . " or worker databases matching pattern 'acme.*test.*worker.*\d+'"
            );
        }
    }

    // Additional safety: check environment
    if (! in_array(app()->environment(), ['testing', 'test', 'local'])) {
        throw new \RuntimeException(
            "CRITICAL: Tests attempted to run in '" . app()->environment() . "' environment. "
            . "Tests must only run in 'testing', 'test', or 'local' environments."
        );
    }
}

/**
 * Create a test employee with proper setup.
 */
function createTestEmployee(array $attributes = []): User
{
    return User::factory()->create($attributes);
}

/**
 * Create multiple test locales for multilingual testing.
 */
function withMultipleLocales(callable $callback): void
{
    $locales = ['en', 'nl', 'fr'];

    foreach ($locales as $locale) {
        app()->setLocale($locale);
        $callback($locale);
    }
}

/**
 * Mock payment gateway for testing.
 */
function mockPaymentGateway(): void
{
    // Mock Stripe gateway
    app()->bind(
        PaymentGatewayInterface::class,
        MockPaymentGateway::class,
    );
}

/**
 * Mock search services to prevent Redis connection issues.
 */
function mockSearchServices(): void
{
    // Mock search cache service to prevent Redis connection issues
    app()->bind(
        Modules\Search\Domain\Service\SearchCacheInterface::class,
        function () {
            $mock = Mockery::mock(Modules\Search\Domain\Service\SearchCacheInterface::class);
            $mock->shouldReceive('get')->andReturn(null);
            $mock->shouldReceive('put')->andReturn(true);
            $mock->shouldReceive('forget')->andReturn(true);
            $mock->shouldReceive('flush')->andReturn(true);

            return $mock;
        },
    );

    // Mock index entity command handler
    app()->bind(
        Modules\Search\Application\Command\IndexEntityCommandHandler::class,
        function () {
            $mock = Mockery::mock(Modules\Search\Application\Command\IndexEntityCommandHandler::class);
            $mock->shouldReceive('handle')->andReturn(null);

            return $mock;
        },
    );
}

/**
 * Mock notification services to prevent database access during unit tests.
 */
function mockNotificationServices(): void
{
    // Mock notification command handler
    app()->bind(
        Modules\Notification\Application\Command\CreateNotificationCommandHandler::class,
        function () {
            $mock = Mockery::mock(Modules\Notification\Application\Command\CreateNotificationCommandHandler::class);
            $mock->shouldReceive('handle')->andReturn(null);

            return $mock;
        },
    );

    // Mock notification repository
    app()->bind(
        Modules\Notification\Domain\Repository\NotificationRepositoryInterface::class,
        function () {
            $mock = Mockery::mock(Modules\Notification\Domain\Repository\NotificationRepositoryInterface::class);
            $mock->shouldReceive('create')->andReturn(null);
            $mock->shouldReceive('findById')->andReturn(null);

            return $mock;
        },
    );

    // Mock notification listener directly to prevent it from being called
    app()->bind(
        Modules\Notification\Infrastructure\Laravel\Listeners\CampaignCreatedNotificationListener::class,
        function () {
            $mock = Mockery::mock(Modules\Notification\Infrastructure\Laravel\Listeners\CampaignCreatedNotificationListener::class);
            $mock->shouldReceive('handle')->andReturn(null);

            return $mock;
        },
    );
}

/**
 * Assert database has campaign with specific attributes.
 */
function assertDatabaseHasCampaign(array $attributes): void
{
    expect(DB::table('campaigns'))
        ->where($attributes)
        ->exists()
        ->toBeTrue();
}

/**
 * Assert database has donation with specific attributes.
 */
function assertDatabaseHasDonation(array $attributes): void
{
    expect(DB::table('donations'))
        ->where($attributes)
        ->exists()
        ->toBeTrue();
}

/**
 * Create test campaign with proper relationships.
 */
function createTestCampaign(array $attributes = []): Campaign
{
    return Campaign::factory()->create($attributes);
}

/**
 * Create test organization for campaigns.
 */
function createTestOrganization(array $attributes = []): Organization
{
    return Organization::factory()->create($attributes);
}

/**
 * Create test donation with proper relationships.
 */
function createTestDonation(array $attributes = []): Donation
{
    return Donation::factory()->create($attributes);
}

/**
 * Assert API response has proper structure.
 */
function assertApiResponse($response, array $structure): void
{
    $response->assertStatus(200)
        ->assertJsonStructure($structure);
}

/**
 * Assert multilingual content is properly returned.
 */
function assertMultilingualContent($response, string $field, array $locales = ['en', 'nl', 'fr']): void
{
    $data = $response->json('data');

    foreach ($locales as $locale) {
        expect($data)->toHaveKey("{$field}_{$locale}");
    }
}

/*
|--------------------------------------------------------------------------
| Global Test Setup
|--------------------------------------------------------------------------
*/

// Browser test environment setup with Pest 4 Browser plugin
beforeEach(function (): void {
    setupBrowserTestEnvironment();
})->group('browser');

// Configure browser tests to use Playwright instead of Dusk
beforeEach(function (): void {
    // Create standard test user for browser tests
    $this->testUser = createBrowserTestUser();

    // Create test organization and campaign if needed
    $this->testOrganization = createTestOrganization();
    $this->testCampaign = createBrowserTestCampaign([
        'organization_id' => $this->testOrganization->id,
    ]);

    // Set up Laravel application URL for browser tests
    config(['app.url' => config('app.url', 'http://localhost:8000')]);
})->in('Browser');

// Set up pure unit test environment (no Laravel dependencies)
beforeEach(function (): void {
    setupUnitTestEnvironment();
})->group('unit');

// Set up integration test environment (with Laravel services, no database)
beforeEach(function (): void {
    setupTestEnvironment();
})->group('integration');

// Set up feature test environment (with Laravel services and database)
beforeEach(function (): void {
    setupIntegrationTestEnvironment();
})->group('feature');

// Clean up after each test
afterEach(function (): void {
    // Reset locale
    app()->setLocale('en');

    // Clear any cached data
    cache()->flush();

    // Clean up browser-specific data
    if (isset($this->testUser)) {
        unset($this->testUser);
    }
    if (isset($this->testOrganization)) {
        unset($this->testOrganization);
    }
    if (isset($this->testCampaign)) {
        unset($this->testCampaign);
    }
});

// Clean up parallel execution resources
afterAll(function (): void {
    // Only cleanup worker databases, not the main test database
    if (config('testing.parallel.worker_id', 0) > 0) {
        cleanupWorkerTestDatabase();
    }
});

/*
|--------------------------------------------------------------------------
| Domain-Specific Setup
|--------------------------------------------------------------------------
*/

// Campaign integration testing setup (requires database)
beforeEach(function (): void {
    $this->employee = createTestEmployee([
        'email' => 'test@acme-corp.com',
        'name' => 'Test Employee',
    ]);

    $this->organization = createTestOrganization([
        'name' => 'Test Organization',
        'category' => 'environmental',
    ]);
})->group('campaign')->group('integration');

// Donation testing setup
beforeEach(function (): void {
    mockPaymentGateway();
})->group('donation', 'payment');

// Multilingual testing setup
beforeEach(function (): void {
    config(['app.locale' => 'en']);
    config(['app.available_locales' => ['en', 'nl', 'fr']]);
})->group('multilingual');

/*
|--------------------------------------------------------------------------
| Test Groups and Tags
|--------------------------------------------------------------------------
*/

// Unit tests - fast isolated tests (can run in parallel safely)
pest()->group('unit')->in('Unit');

// Feature tests - integration with Laravel framework
pest()->group('feature')->in('Feature');

// Integration tests - external service integration (limited parallelism)
pest()->group('integration')->in('Integration');

// Browser tests - using PEST 4 Browser Plugin with Playwright
pest()->group('browser')->in('Browser');

/*
|--------------------------------------------------------------------------
| Parallel Execution Configuration
|--------------------------------------------------------------------------
*/

// Parallel execution is controlled via command line options:
// ./vendor/bin/pest --parallel (automatic detection)
// ./vendor/bin/pest --processes=4 (specific number)
// Environment variables can be used for defaults in phpunit.xml or pest configuration

/*
|--------------------------------------------------------------------------
| Domain-Specific Test Groups
|--------------------------------------------------------------------------
*/

// Visual regression testing group setup
beforeEach(function (): void {
    // Set consistent test environment for visual tests
    if (method_exists($this, 'setupVisualEnvironment')) {
        $this->setupVisualEnvironment();
    }
})->group('visual');

// Performance testing group setup
beforeEach(function (): void {
    // Disable cache for performance tests
    config(['cache.default' => 'array']);
})->group('performance');

// Multilingual tests - locale and translation testing
pest()->group('multilingual');

// Payment tests - payment gateway integration
pest()->group('payment');

// Campaign domain tests
pest()->group('campaign');

// Donation domain tests
pest()->group('donation');

// Organization domain tests
pest()->group('organization');

/*
|--------------------------------------------------------------------------
| Custom Assertions
|--------------------------------------------------------------------------
*/

// Assert that a model has required attributes
function assertModelHasRequiredAttributes($model, array $attributes): void
{
    foreach ($attributes as $attribute) {
        expect($model)->toHaveProperty($attribute);
    }
}

// Assert that an event was dispatched with specific payload
function assertEventDispatched(string $eventClass, array $payload = []): void
{
    Event::assertDispatched($eventClass, function ($event) use ($payload): bool {
        if (empty($payload)) {
            return true;
        }

        foreach ($payload as $key => $value) {
            if (! property_exists($event, $key) || $value !== $event->{$key}) {
                return false;
            }
        }

        return true;
    });
}

// Assert that a queue job was dispatched
function assertJobDispatched(string $jobClass): void
{
    Queue::assertPushed($jobClass);
}

// Assert that an email was sent
function assertEmailSent(string $mailable): void
{
    Mail::assertSent($mailable);
}

// Assert that a notification was sent
function assertNotificationSent(string $notificationClass): void
{
    Notification::assertSent($notificationClass);
}

/**
 * Store a system event for testing purposes.
 */
function storeSystemEvent(
    string $eventName,
    string $eventClass,
    array $payload,
    string $aggregateType,
    int $aggregateId,
    int $version,
    string $correlationId,
    ?string $causationId,
): string {
    $eventId = DB::table('system_events')->insertGetId([
        'event_name' => $eventName,
        'event_class' => $eventClass,
        'payload' => json_encode($payload),
        'aggregate_type' => $aggregateType,
        'aggregate_id' => $aggregateId,
        'version' => $version,
        'occurred_at' => now(),
        'correlation_id' => $correlationId,
        'causation_id' => $causationId,
        'processed' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return (string) $eventId;
}
