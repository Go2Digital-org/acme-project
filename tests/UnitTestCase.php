<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Lightweight test case for pure unit tests.
 *
 * This class bypasses Laravel's bootstrap entirely for faster test execution.
 * Use this for testing pure PHP classes without framework dependencies.
 */
abstract class UnitTestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // No Laravel bootstrap - pure PHPUnit
        // Tests run 10x faster without framework overhead
    }
}
