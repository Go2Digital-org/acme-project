<?php

declare(strict_types=1);

namespace Tests\Browser;

use Pest\Browser\Playwright\Playwright;
use Tests\TestCase;

abstract class BrowserTestCase extends TestCase
{
    /**
     * Get the base URL for browser tests
     */
    public static function baseUrl(): string
    {
        return env('PLAYWRIGHT_BASE_URL', env('TEST_SERVER_URL', 'http://127.0.0.1:8000'));
    }

    /**
     * Configure browser testing settings
     */
    public static function configureBrowser(): void
    {
        // Disable cache warming for browser tests
        putenv('CACHE_WARMING_ENABLED=false');
        $_ENV['CACHE_WARMING_ENABLED'] = 'false';
        $_SERVER['CACHE_WARMING_ENABLED'] = 'false';

        // Check environment variable or default to headless
        $headless = env('BROWSER_HEADLESS', true);

        if ($headless === false || $headless === 'false') {
            Playwright::headed();
        }

        // Set realistic timeouts for browser page loading
        Playwright::setTimeout(30000); // 30 seconds default timeout to handle Laravel startup
    }

    /**
     * Skip cache warming for browser tests by setting a cookie
     *
     * @return array<string, mixed>
     */
    public static function skipCacheWarming(): array
    {
        return [
            'name' => 'skip_cache_warming',
            'value' => '1',
            'domain' => '127.0.0.1',
            'path' => '/',
            'expires' => time() + 3600,
            'httpOnly' => false,
            'secure' => false,
            'sameSite' => 'Lax',
        ];
    }
}
