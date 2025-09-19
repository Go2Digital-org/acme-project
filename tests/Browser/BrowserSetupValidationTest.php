<?php

declare(strict_types=1);

describe('Browser Setup Validation', function (): void {
    it('can visit ACME application homepage', function (): void {
        visit('http://localhost:8000/en')
            ->assertSee('ACME');
    });

    it('validates localization redirect works', function (): void {
        $page = visit('http://localhost:8000');
        expect($page->url())->toContain('/en');
    });

    it('can navigate to localized pages', function (): void {
        $page = visit('http://localhost:8000/en');
        expect($page->url())->toBe('http://localhost:8000/en');
    });

    it('can handle ACME application page loads', function (): void {
        visit('http://localhost:8000/en')
            ->assertSee('ACME'); // Should see ACME content
    });

    it('validates ACME application has proper structure', function (): void {
        visit('http://localhost:8000/en')
            ->assertDontSee('Fatal error'); // Should not have PHP errors
    });
});