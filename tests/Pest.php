<?php

declare(strict_types=1);

pest()->extend(Tests\UnitTestCase::class)->in('Unit');
pest()->extend(Tests\Integration\IntegrationTestCase::class)->in('Integration');
pest()->extend(Tests\TestCase::class)->in('Feature');
pest()->extend(Tests\TestCase::class)->in('Browser');
