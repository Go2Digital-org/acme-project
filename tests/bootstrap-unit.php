<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Lightweight Unit Test Bootstrap
|--------------------------------------------------------------------------
|
| This bootstrap file loads only the essentials for unit testing,
| skipping Laravel's framework initialization for maximum speed.
|
*/

// Load Composer autoloader
require __DIR__ . '/../vendor/autoload.php';

// That's it! No Laravel bootstrap for pure unit tests.
// Tests run 10x faster without the framework overhead.
