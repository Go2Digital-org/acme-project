<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Testing Database Configuration
    |--------------------------------------------------------------------------
    |
    | Optimized settings for test execution with proper isolation
    | and deadlock prevention.
    |
    */

    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'acme_corp_csr_test'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', 'root'),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => false, // Disable strict mode for tests
        'engine' => 'InnoDB',
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            // Optimizations for testing
            PDO::ATTR_PERSISTENT => false, // Don't persist connections
            PDO::ATTR_EMULATE_PREPARES => true, // Faster for tests
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            PDO::ATTR_TIMEOUT => 10, // 10 second timeout
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]) : [],
        // Transaction settings to prevent deadlocks
        'isolation_level' => 'READ COMMITTED',
        'lock_wait_timeout' => 10,
        'innodb_lock_wait_timeout' => 10,
    ],

    'sqlite' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => false,
        'journal_mode' => 'MEMORY',
        'synchronous' => 'OFF',
        'temp_store' => 'MEMORY',
        'cache_size' => 10000,
        'page_size' => 4096,
    ],
];
