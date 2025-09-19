<?php

declare(strict_types=1);

describe('Basic Laravel Test', function (): void {
    it('can boot Laravel application', function (): void {
        expect(app())->not->toBeNull();
        expect(config('app.name'))->not->toBeNull();
    });

    it('can access basic route', function (): void {
        $response = $this->get('/');
        expect($response->status())->toBeIn([200, 302, 404]); // Any valid HTTP status
    });
});
