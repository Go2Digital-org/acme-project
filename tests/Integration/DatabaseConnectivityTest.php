<?php

declare(strict_types=1);

namespace Tests\Integration;

use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Direct database connectivity test without Laravel bootstrap
 */
class DatabaseConnectivityTest extends TestCase
{
    public function test_mysql_connection_works(): void
    {
        $dsn = 'mysql:host=127.0.0.1;port=3306;dbname=acme_corp_csr_test';
        $username = 'root';
        $password = 'root';

        $connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $result = $connection->query('SELECT 1 as test')->fetch();

        $this->assertEquals(1, $result['test']);
    }

    public function test_database_tables_exist(): void
    {
        $dsn = 'mysql:host=127.0.0.1;port=3306;dbname=acme_corp_csr_test';
        $username = 'root';
        $password = 'root';

        $connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $result = $connection->query('SHOW TABLES')->fetchAll();

        $this->assertGreaterThan(0, count($result));
    }
}
