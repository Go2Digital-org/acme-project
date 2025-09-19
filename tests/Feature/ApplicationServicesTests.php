<?php

declare(strict_types=1);

describe('Application Services Feature Tests', function (): void {
    it('can bind and resolve services from container', function (): void {
        expect(app('cache'))->not->toBeNull();
        expect(app('db'))->not->toBeNull();
        expect(app('config'))->not->toBeNull();
        expect(app('events'))->not->toBeNull();
    });

    it('validates Laravel framework is loaded', function (): void {
        expect(class_exists('Illuminate\Foundation\Application'))->toBeTrue();
        expect(class_exists('Illuminate\Database\Eloquent\Model'))->toBeTrue();
        expect(class_exists('Illuminate\Support\Facades\Cache'))->toBeTrue();
    });

    it('validates custom modules are loaded', function (): void {
        expect(class_exists('Modules\Campaign\Domain\Model\Campaign'))->toBeTrue();
        expect(class_exists('Modules\User\Infrastructure\Laravel\Models\User'))->toBeTrue();
        expect(class_exists('Modules\Donation\Domain\Model\Donation'))->toBeTrue();
    });

    it('validates service providers are registered', function (): void {
        $providers = app()->getLoadedProviders();
        expect($providers)->toHaveKey('Illuminate\Foundation\Providers\FormRequestServiceProvider');
        expect($providers)->toHaveKey('Illuminate\Database\DatabaseServiceProvider');
    });

    it('validates middleware configuration', function (): void {
        $kernel = app('Illuminate\Contracts\Http\Kernel');
        expect($kernel)->not->toBeNull();
    });

    it('validates route configuration', function (): void {
        $router = app('router');
        expect($router)->not->toBeNull();
        expect($router->getRoutes()->count())->toBeGreaterThan(0);
    });

    it('validates database configuration is correct', function (): void {
        expect(config('database.default'))->toBe('mysql');
        expect(config('database.connections.mysql.database'))->toBe('acme_corp_csr_test');
        expect(config('database.connections.mysql.host'))->toBe('127.0.0.1');
    });

    it('validates cache and session configuration', function (): void {
        expect(config('cache.default'))->toBe('array');
        expect(config('session.driver'))->toBe('array');
        expect(config('queue.default'))->toBe('sync');
    });

    it('validates external services are disabled', function (): void {
        expect(config('scout.driver'))->toBeNull();
        expect(config('app.debug'))->toBeFalse();
        expect(config('mail.default'))->toBe('array');
    });

    it('validates application environment', function (): void {
        expect(app()->environment())->toBe('testing');
        expect(config('app.env'))->toBe('testing');
        expect(config('app.name'))->not->toBeNull();
    });
});
