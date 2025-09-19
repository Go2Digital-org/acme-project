<?php

declare(strict_types=1);

describe('Fast Command Feature Tests', function (): void {
    it('can access application without console commands', function (): void {
        expect(app())->not->toBeNull();
    });

    it('can check application configuration', function (): void {
        expect(config('app.name'))->not->toBeNull();
        expect(config('app.env'))->toBe('testing');
    });

    it('can access application services', function (): void {
        expect(app('cache'))->not->toBeNull();
        expect(app('db'))->not->toBeNull();
    });

    it('validates database connection configuration', function (): void {
        expect(config('database.default'))->toBe('mysql');
        expect(config('database.connections.mysql.database'))->toBe('acme_corp_csr_test');
    });

    it('validates cache configuration', function (): void {
        expect(config('cache.default'))->toBe('array');
        expect(config('session.driver'))->toBe('array');
    });

    it('validates queue configuration', function (): void {
        expect(config('queue.default'))->toBe('sync');
        expect(config('mail.default'))->toBe('array');
    });

    it('validates external service configuration', function (): void {
        expect(config('scout.driver'))->toBeNull();
        expect(config('app.debug'))->toBeFalse();
    });
});
