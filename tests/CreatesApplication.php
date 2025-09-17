<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        // Force loading of .env.testing file
        if (! isset($_ENV['APP_ENV']) || $_ENV['APP_ENV'] !== 'testing') {
            $_ENV['APP_ENV'] = 'testing';
            putenv('APP_ENV=testing');
        }

        // Load the testing environment file
        $basePath = dirname(__DIR__);
        if (file_exists($basePath . '/.env.testing')) {
            \Dotenv\Dotenv::createImmutable($basePath, '.env.testing')->load();
        }

        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Double-check we're in testing environment
        if ($app->environment() !== 'testing') {
            $app->detectEnvironment(function () {
                return 'testing';
            });
        }

        return $app;
    }
}
