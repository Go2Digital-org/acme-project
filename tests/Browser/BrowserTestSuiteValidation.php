<?php

declare(strict_types=1);

describe('ACME Browser Test Suite Validation', function (): void {
    it('can launch browser and visit external pages', function (): void {
        visit('https://httpbin.org/html')
            ->assertSee('Herman Melville - Moby-Dick')
            ->assertUrlIs('https://httpbin.org/html');
    });

    it('can handle navigation and URLs', function (): void {
        visit('https://httpbin.org/json')
            ->assertSee('slideshow')
            ->assertUrlIs('https://httpbin.org/json');
    });

    it('can handle basic interactions', function (): void {
        visit('https://httpbin.org/html')
            ->assertSee('Moby-Dick')
            ->assertDontSee('Non-existent content');
    });

    it('validates browser configuration is working', function (): void {
        // Test the browser test configuration
        $page = visit('https://httpbin.org/user-agent');

        expect($page)->not->toBeNull();
        $page->assertSee('User-Agent');
    });

    it('demonstrates timeout handling', function (): void {
        visit('https://httpbin.org/delay/1')
            ->assertSee('origin');
    });
});
