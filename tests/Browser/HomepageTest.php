<?php

declare(strict_types=1);

describe('Homepage', function (): void {
    it('loads successfully and displays ACME content', function (): void {
        $page = browserVisit('/en/');

        // Wait for page to load and check for ACME branding
        $page->assertSee('ACME Corp');
    });

    it('displays the navigation menu', function (): void {
        $page = browserVisit('/en/');

        $page->assertSee('Campaigns');
    });

    it('shows login link when not authenticated', function (): void {
        $page = browserVisit('/en/');

        $page->assertSee('Login');
    });
});
