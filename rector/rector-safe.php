<?php

declare(strict_types=1);

require_once __DIR__ . '/rector-profiles.php';

// Ultra-safe configuration for manual runs that won't break PHPStan level 8
return RectorProfiles::conservative();
