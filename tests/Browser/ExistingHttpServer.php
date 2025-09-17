<?php

declare(strict_types=1);

namespace Tests\Browser;

use Pest\Browser\Contracts\HttpServer;
use Throwable;

/**
 * Custom HTTP server implementation that uses an existing Laravel server
 * instead of starting a new one.
 */
final class ExistingHttpServer implements HttpServer
{
    private ?Throwable $lastThrowable = null;

    public function __construct(
        public readonly string $host = '127.0.0.1',
        public readonly int $port = 8000,
    ) {}

    public function rewrite(string $url): string
    {
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            $url = ltrim($url, '/');

            return "http://{$this->host}:{$this->port}/{$url}";
        }

        return $url;
    }

    public function start(): void
    {
        // Server is already running, no need to start
    }

    public function stop(): void
    {
        // Don't stop the existing server
    }

    public function flush(): void
    {
        // Nothing to flush
    }

    public function bootstrap(): void
    {
        // Set the app URL to point to the existing server
        config(['app.url' => "http://{$this->host}:{$this->port}"]);

        // Ensure cache warming is disabled for browser tests
        config(['cache-warming.enabled' => false]);
    }

    public function lastThrowable(): ?Throwable
    {
        return $this->lastThrowable;
    }

    public function throwLastThrowableIfNeeded(): void
    {
        if ($this->lastThrowable instanceof Throwable) {
            throw $this->lastThrowable;
        }
    }
}
