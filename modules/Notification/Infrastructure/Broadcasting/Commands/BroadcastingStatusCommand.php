<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Broadcasting\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Redis\Connections\Connection;
use Illuminate\Support\Facades\Config;

/**
 * Command to check broadcasting status and configuration.
 *
 * This command provides a quick overview of the broadcasting system
 * status and helps identify configuration issues.
 */
class BroadcastingStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'notifications:broadcasting-status 
                            {--detailed : Show detailed configuration}
                            {--check-deps : Check required dependencies}';

    /**
     * The console command description.
     */
    protected $description = 'Show broadcasting system status and configuration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('📡 Broadcasting System Status');
        $this->line('==============================');

        $this->checkBasicConfiguration();

        if ($this->option('check-deps')) {
            $this->checkDependencies();
        }

        if ($this->option('detailed')) {
            $this->showDetailedConfiguration();
        }

        $this->showQuickStart();

        return self::SUCCESS;
    }

    /**
     * Check basic broadcasting configuration.
     */
    private function checkBasicConfiguration(): void
    {
        $driver = Config::get('broadcasting.default');
        $this->line("Current Driver: <info>{$driver}</info>");

        $status = match ($driver) {
            'pusher' => $this->checkPusherStatus(),
            'redis' => $this->checkRedisStatus(),
            'log' => '✅ Log driver (development)',
            'null' => '⚠️  Null driver (disabled)',
            default => '❌ Unknown driver',
        };

        $this->line("Status: {$status}");
        $this->line('');
    }

    /**
     * Check Pusher configuration status.
     */
    private function checkPusherStatus(): string
    {
        $config = Config::get('broadcasting.connections.pusher');

        if (! $config['key'] || ! $config['secret'] || ! $config['app_id']) {
            return '❌ Pusher not configured (missing credentials)';
        }

        return '✅ Pusher configured';
    }

    /**
     * Check Redis configuration status.
     */
    private function checkRedisStatus(): string
    {
        try {
            $redis = app('redis')->connection('default');
            /** @var Connection $redis */
            $redis->ping();

            return '✅ Redis connected';
        } catch (Exception) {
            return '❌ Redis connection failed';
        }
    }

    /**
     * Check required dependencies.
     */
    private function checkDependencies(): void
    {
        $this->line('🔍 Checking Dependencies');
        $this->line('-----------------------');

        $dependencies = [
            'Laravel Echo' => $this->checkLaravelEcho(),
            'Pusher PHP SDK' => $this->checkPusherPhp(),
            'Broadcasting Routes' => $this->checkBroadcastingRoutes(),
            'Channel Definitions' => $this->checkChannelDefinitions(),
            'Service Providers' => $this->checkServiceProviders(),
        ];

        foreach ($dependencies as $name => $status) {
            $this->line("{$name}: {$status}");
        }

        $this->line('');
    }

    /**
     * Check if Laravel Echo is available.
     */
    private function checkLaravelEcho(): string
    {
        $jsFiles = [
            'resources/js/app.js',
            'resources/js/bootstrap.js',
            'package.json',
        ];

        foreach ($jsFiles as $file) {
            $path = base_path($file);

            if (file_exists($path)) {
                $content = file_get_contents($path);

                if ($content !== false && (str_contains($content, 'laravel-echo') || str_contains($content, 'Echo'))) {
                    return '✅ Laravel Echo detected';
                }
            }
        }

        return '⚠️  Laravel Echo not detected in JS files';
    }

    /**
     * Check if Pusher PHP SDK is installed.
     */
    private function checkPusherPhp(): string
    {
        if (class_exists('Pusher\Pusher')) {
            return '✅ Pusher PHP SDK installed';
        }

        return '❌ Pusher PHP SDK not installed';
    }

    /**
     * Check if broadcasting routes are enabled.
     */
    private function checkBroadcastingRoutes(): string
    {
        $routeFile = base_path('routes/channels.php');

        if (file_exists($routeFile)) {
            return '✅ Broadcasting routes file exists';
        }

        return '❌ Broadcasting routes file missing';
    }

    /**
     * Check channel definitions.
     */
    private function checkChannelDefinitions(): string
    {
        $routeFile = base_path('routes/channels.php');

        if (! file_exists($routeFile)) {
            return '❌ Channel definitions file missing';
        }

        $content = file_get_contents($routeFile);

        if ($content === false) {
            return '❌ Could not read channels file';
        }

        $requiredChannels = [
            'admin-dashboard',
            'user.notifications',
            'admin-role-',
            'security-alerts',
        ];

        $foundChannels = 0;

        foreach ($requiredChannels as $channel) {
            if (str_contains($content, $channel)) {
                $foundChannels++;
            }
        }

        if ($foundChannels === count($requiredChannels)) {
            return '✅ All required channels defined';
        }

        return "⚠️  {$foundChannels}/" . count($requiredChannels) . ' channels defined';
    }

    /**
     * Check service providers.
     */
    private function checkServiceProviders(): string
    {
        $providers = [
            'BroadcastServiceProvider',
            'NotificationServiceProvider',
        ];

        $configFile = config_path('app.php');

        if (! file_exists($configFile)) {
            return '❌ App configuration file missing';
        }

        $content = file_get_contents($configFile);

        if ($content === false) {
            return '❌ Could not read app configuration file';
        }

        $foundProviders = 0;

        foreach ($providers as $provider) {
            if (str_contains($content, $provider)) {
                $foundProviders++;
            }
        }

        return $foundProviders > 0 ? '✅ Service providers configured' : '⚠️  Check service providers';
    }

    /**
     * Show detailed configuration.
     */
    private function showDetailedConfiguration(): void
    {
        $this->line('🔧 Detailed Configuration');
        $this->line('-------------------------');

        $config = Config::get('broadcasting');
        $this->line("Default Connection: <info>{$config['default']}</info>");

        foreach ($config['connections'] as $name => $connection) {
            $this->line("\nConnection '{$name}':");
            $this->line("  Driver: {$connection['driver']}");

            if ($connection['driver'] === 'pusher') {
                $this->line('  App ID: ' . ($connection['app_id'] ? '***' : 'not set'));
                $this->line('  Key: ' . ($connection['key'] ? '***' : 'not set'));
                $this->line('  Secret: ' . ($connection['secret'] ? '***' : 'not set'));
                $this->line('  Cluster: ' . ($connection['options']['cluster'] ?? 'mt1'));
            } elseif ($connection['driver'] === 'redis') {
                $this->line("  Connection: {$connection['connection']}");
            }
        }

        $this->line('');
    }

    /**
     * Show quick start information.
     */
    private function showQuickStart(): void
    {
        $this->line('🚀 Quick Start Commands');
        $this->line('----------------------');
        $this->line('Test broadcasting:         <comment>php artisan notifications:test-broadcast</comment>');
        $this->line('Debug configuration:       <comment>php artisan notifications:debug-broadcast</comment>');
        $this->line('Test specific type:        <comment>php artisan notifications:test-broadcast --type=donation</comment>');
        $this->line('Test multiple notifications: <comment>php artisan notifications:test-broadcast --count=5</comment>');
        $this->line('');

        $this->info('💡 Tips:');
        $this->line('- For development, use BROADCAST_CONNECTION=log in your .env file');
        $this->line('- For production, configure Pusher credentials and use BROADCAST_CONNECTION=pusher');
        $this->line('- Check browser console for WebSocket connection status');
        $this->line('- Ensure Laravel Echo is properly configured in your frontend');
    }
}
