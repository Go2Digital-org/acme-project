<?php

declare(strict_types=1);

describe('Browser Infrastructure Tests', function (): void {
    it('can start a browser session', function (): void {
        $page = visit('https://httpbin.org/html');

        expect($page)->not->toBeNull();
    });

    it('can interact with page elements', function (): void {
        visit('https://httpbin.org/html')
            ->assertSee('Herman Melville - Moby-Dick');
    });

    it('can handle browser navigation', function (): void {
        visit('https://httpbin.org/html')
            ->assertUrlIs('https://httpbin.org/html');
    });
});
