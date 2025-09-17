<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Config;
use Modules\Notification\Infrastructure\Broadcasting\Service\NotificationBroadcaster;

/**
 * Command to debug broadcasting configuration and connectivity.
 *
 * This command helps diagnose broadcasting issues by checking configuration,
 * testing connections, and providing debugging information.
 */
class BroadcastingDebugCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:debug-broadcast 
                            {--test-connection : Test broadcasting connection}
                            {--show-config : Show broadcasting configuration}
                            {--check-env : Check environment variables}
                            {--test-channels : Test channel authorization}';

    /**
     * The console command description.
     */
    protected $description = 'Debug notification broadcasting configuration and connectivity';

    public function __construct(
        private readonly NotificationBroadcaster $broadcaster,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Debugging Notification Broadcasting');
        $this->line('=====================================');

        $allGood = true;

        if ($this->option('show-config') || ! $this->hasOptions()) {
            $allGood &= $this->checkConfiguration();
        }

        if ($this->option('check-env') || ! $this->hasOptions()) {
            $allGood &= $this->checkEnvironmentVariables();
        }

        if ($this->option('test-connection') || ! $this->hasOptions()) {
            $allGood &= $this->testConnection();
        }

        if ($this->option('test-channels') || ! $this->hasOptions()) {
            $allGood &= $this->testChannels();
        }

        $this->line('');

        if ($allGood) {
            $this->info('✅ All broadcasting checks passed!');

            return self::SUCCESS;
        }
        $this->error('❌ Some broadcasting checks failed. Please review the output above.');

        return self::FAILURE;
    }

    /**
     * Check if any specific options were provided.
     */
    private function hasOptions(): bool
    {
        if ($this->option('test-connection')) {
            return true;
        }
        if ($this->option('show-config')) {
            return true;
        }
        if ($this->option('check-env')) {
            return true;
        }

        return (bool) $this->option('test-channels');
    }

    /**
     * Check broadcasting configuration.
     */
    private function checkConfiguration(): bool
    {
        $this->line("\n📋 Broadcasting Configuration");
        $this->line('-----------------------------');

        $driver = Config::get('broadcasting.default');
        $connections = Config::get('broadcasting.connections');

        $this->line("Default Driver: <info>{$driver}</info>");

        if (! isset($connections[$driver])) {
            $this->error("❌ Default driver '{$driver}' not found in connections");

            return false;
        }

        $config = $connections[$driver];
        $this->line("Driver Type: <info>{$config['driver']}</info>");

        // Check driver-specific configuration
        switch ($config['driver']) {
            case 'pusher':
                return $this->checkPusherConfig($config);
            case 'redis':
                return $this->checkRedisConfig($config);
            case 'log':
                $this->line('✅ Log driver configured (for development)');

                return true;
            case 'null':
                $this->warn('⚠️  Null driver configured (broadcasting disabled)');

                return true;
            default:
                $this->warn("⚠️  Unknown driver: {$config['driver']}");

                return false;
        }
    }

    /**
     * Check Pusher configuration.
     *
     * @param  array<string, mixed>  $config
     */
    private function checkPusherConfig(array $config): bool
    {
        $required = ['key', 'secret', 'app_id'];
        $allGood = true;

        foreach ($required as $field) {
            if (empty($config[$field])) {
                $this->error("❌ Missing Pusher {$field}");
                $allGood = false;

                continue;
            }

            $value = strlen((string) $config[$field]) > 10 ? substr((string) $config[$field], 0, 10) . '...' : $config[$field];
            $this->line("✅ Pusher {$field}: <info>{$value}</info>");
        }

        $options = $config['options'] ?? [];
        $cluster = $options['cluster'] ?? 'mt1';
        $this->line("✅ Pusher cluster: <info>{$cluster}</info>");

        return $allGood;
    }

    /**
     * Check Redis configuration.
     *
     * @param  array<string, mixed>  $config
     */
    private function checkRedisConfig(array $config): bool
    {
        $connection = $config['connection'] ?? 'default';
        $this->line("✅ Redis connection: <info>{$connection}</info>");

        try {
            $redis = app('redis')->connection($connection);
            /** @var Connection $redis */
            $redis->ping();
            $this->line('✅ Redis connection successful');

            return true;
        } catch (Exception $e) {
            $this->error('❌ Redis connection failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Check environment variables.
     */
    private function checkEnvironmentVariables(): bool
    {
        $this->line("\n🌍 Environment Variables");
        $this->line('------------------------');

        $envVars = [
            'BROADCAST_CONNECTION' => config('broadcasting.default'),
            'PUSHER_APP_ID' => config('broadcasting.connections.pusher.app_id'),
            'PUSHER_APP_KEY' => config('broadcasting.connections.pusher.key'),
            'PUSHER_APP_SECRET' => config('broadcasting.connections.pusher.secret'),
            'PUSHER_APP_CLUSTER' => config('broadcasting.connections.pusher.options.cluster'),
        ];

        $allGood = true;

        foreach ($envVars as $var => $value) {
            if ($value === null) {
                if ($var === 'BROADCAST_CONNECTION') {
                    $this->error("❌ {$var} is not set");
                    $allGood = false;

                    continue;
                }

                $this->line("⚪ {$var}: <comment>not set</comment>");

                continue;
            }

            $displayValue = $var === 'PUSHER_APP_SECRET'
                ? str_repeat('*', min(8, strlen((string) $value)))
                : (string) $value;
            $this->line("✅ {$var}: <info>{$displayValue}</info>");
        }

        return $allGood;
    }

    /**
     * Test broadcasting connection.
     */
    private function testConnection(): bool
    {
        $this->line("\n🔌 Testing Broadcasting Connection");
        $this->line('----------------------------------');

        try {
            $this->broadcaster->testBroadcast();
            $this->line('✅ Test broadcast sent successfully');
            $this->line('   Check your browser console or admin dashboard for the test notification');

            return true;
        } catch (Exception $e) {
            $this->error('❌ Broadcasting test failed: ' . $e->getMessage());
            $this->line('   Stack trace:');
            $this->line($e->getTraceAsString());

            return false;
        }
    }

    /**
     * Test channel definitions.
     */
    private function testChannels(): bool
    {
        $this->line("\n📡 Testing Channel Definitions");
        $this->line('------------------------------');

        $channels = [
            'admin-dashboard',
            'admin-role-super_admin',
            'admin-role-csr_admin',
            'admin-role-finance_admin',
            'security-alerts',
            'system-maintenance',
            'compliance-notifications',
            'payment-notifications',
        ];

        $allGood = true;

        foreach ($channels as $channel) {
            try {
                // Check if routes/channels.php exists and has channel definitions
                $channelsFile = base_path('routes/channels.php');

                if (! file_exists($channelsFile)) {
                    $this->error('❌ channels.php file not found');
                    $allGood = false;

                    continue;
                }

                $content = file_get_contents($channelsFile);

                if ($content !== false && str_contains($content, $channel)) {
                    $this->line("✅ Channel '{$channel}' is defined");

                    continue;
                }

                $this->warn("⚠️  Channel '{$channel}' definition not found");
            } catch (Exception $e) {
                $this->error("❌ Error checking channel '{$channel}': " . $e->getMessage());
                $allGood = false;
            }
        }

        return $allGood;
    }
}
