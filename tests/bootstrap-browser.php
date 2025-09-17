<?php

declare(strict_types=1);

// Bootstrap file for browser tests to use existing server

// Include the original autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Override the ServerManager to use existing server before any tests run
$reflection = new ReflectionClass(\Pest\Browser\ServerManager::class);
$instanceProperty = $reflection->getProperty('instance');
$instanceProperty->setAccessible(true);

// Reset the singleton
$instanceProperty->setValue(null, null);

// Now when ServerManager::instance() is called, override the http property
\Pest\Browser\ServerManager::instance();

$httpProperty = $reflection->getProperty('http');
$httpProperty->setAccessible(true);
$httpProperty->setValue(
    \Pest\Browser\ServerManager::instance(),
    new \Tests\Browser\ExistingHttpServer('127.0.0.1', 8000)
);
