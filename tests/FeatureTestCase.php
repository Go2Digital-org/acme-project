<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class FeatureTestCase extends TestCase
{
    use DatabaseTransactions;

    /**
     * Setup the test environment for feature tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure we're using the test database
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'acme_corp_csr_test',
        ]);
    }
}
