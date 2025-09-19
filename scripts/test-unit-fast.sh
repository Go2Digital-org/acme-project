#!/bin/bash

# Fast Unit Test Runner - Optimized for Speed
# Run unit tests with minimal overhead

# Disable XDebug for maximum speed
export XDEBUG_MODE=off

# Disable garbage collection during tests
export PHP_INI_SCAN_DIR=""

# Run with optimized settings
php -d memory_limit=256M \
    -d opcache.enable=1 \
    -d opcache.enable_cli=1 \
    -d opcache.jit_buffer_size=100M \
    -d opcache.jit=tracing \
    -d zend.enable_gc=0 \
    -d opcache.validate_timestamps=0 \
    -d realpath_cache_size=4M \
    -d realpath_cache_ttl=600 \
    ./vendor/bin/pest \
    --configuration=phpunit-unit.xml \
    --no-coverage \
    "$@"