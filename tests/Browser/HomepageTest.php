<?php

declare(strict_types=1);

describe('Homepage', function (): void {
    it('loads successfully and has basic content', function (): void {
        // Test ACME CSR Platform homepage
        visit('http://localhost:8000/en')
            ->assertSee('ACME');
    });

    it('responds to basic navigation', function (): void {
        // Test navigation to homepage
        $page = visit('http://localhost:8000/en');
        expect($page->url())->toContain('/en');
    });

    it('has localization working', function (): void {
        // Test localization redirect
        $page = visit('http://localhost:8000');
        expect($page->url())->toContain('/en');
    });
});
