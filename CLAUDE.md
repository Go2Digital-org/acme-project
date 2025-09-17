# ACME Corp CSR Platform - Development Guide

## Environment Setup

### Docker vs Local Development

The project supports both Docker and local development environments with separate configurations:

#### Docker Environment (Recommended)
- **Configuration**: Uses `.env.docker` (Docker service names)
- **Services**: MySQL, Redis, Meilisearch, Mailpit all running in containers
- **Start**: `docker-compose --env-file .env.docker up -d`
- **Access**: http://app.acme-corp-optimy.orb.local/ (OrbStack) or http://localhost:8000

```bash
# Start all services with proper environment
docker-compose --env-file .env.docker up -d

# Rebuild and start (if needed)
docker-compose --env-file .env.docker up -d --build

# Production deployment
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Run artisan commands in container
docker exec acme-app php artisan migrate
docker exec acme-app php artisan cache:clear
```

#### Local Development Environment
- **Configuration**: Uses `.env` (localhost/127.0.0.1 addresses) 
- **Services**: MySQL/Redis on localhost (optional - uses file cache/database queues)
- **Switch Command**: `./scripts/env-local.sh`
- **Start**: `php artisan serve`
- **Access**: http://localhost:8000

```bash
# Switch to Local environment
./scripts/env-local.sh

# Start Laravel dev server
php artisan serve

# Run migrations locally
php artisan migrate
```

#### Environment Files
- `.env` - Local development configuration (127.0.0.1 addresses, file cache)
- `.env.docker` - Docker environment configuration (service names: mysql, redis, etc.)

#### Quick Environment Switching
```bash
# Development/Staging Docker environment
docker-compose --env-file .env.docker up -d

# Production Docker environment
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Local development environment
php artisan serve

# Stop Docker containers
docker-compose down
```

## Test Suite Commands

### Running Tests

#### Unit Tests
```bash
# Run all unit tests
./vendor/bin/pest --testsuite=Unit

# Run with parallel execution for speed
./vendor/bin/pest --testsuite=Unit --parallel

# Run specific test file
./vendor/bin/pest tests/Unit/Campaign/Domain/Model/CampaignTest.php

# Stop on first failure
./vendor/bin/pest --testsuite=Unit --stop-on-failure
```

#### Integration Tests
```bash
# Run all integration tests
./vendor/bin/pest --testsuite=Integration

# Run specific integration test group
./vendor/bin/pest tests/Integration/Campaign/

# Run with database refresh
./vendor/bin/pest --testsuite=Integration --recreate-databases
```

#### Feature Tests
```bash
# Run all feature tests
./vendor/bin/pest --testsuite=Feature

# Run API tests only
./vendor/bin/pest tests/Feature/Api/

# Run Admin panel tests
./vendor/bin/pest tests/Feature/Admin/
```

#### Browser Tests (Pest 4 Browser Plugin)
```bash
# Run all browser tests
./vendor/bin/pest tests/Browser

# Run specific browser test
./vendor/bin/pest tests/Browser/LoginTest.php

# Run with visible browser (not headless)
BROWSER_HEADLESS=false ./vendor/bin/pest tests/Browser

# Run specific test method
./vendor/bin/pest tests/Browser --filter="can fill and submit the login form"

# Note: Browser tests use Pest 4's native browser plugin with Playwright
# Laravel Dusk is no longer needed
```

#### All Tests
```bash
# Run complete test suite
./vendor/bin/pest

# Run with coverage (requires xdebug/pcov)
./vendor/bin/pest --coverage

# Run tests matching a pattern
./vendor/bin/pest --filter=Campaign

# Run tests in a specific group
./vendor/bin/pest --group=campaign
```

### Code Quality Checks

```bash
# Run Laravel Pint (code style)
./vendor/bin/pint

# Check without fixing
./vendor/bin/pint --test

# Run PHPStan (static analysis)
./vendor/bin/phpstan analyse

# Run Deptrac (architecture boundaries)
./vendor/bin/deptrac analyse

# Run all quality checks (via GrumPHP)
./vendor/bin/grumphp run
```

### Test Database

```bash
# Create test database
mysql -u root -proot -e "CREATE DATABASE IF NOT EXISTS \`acme_corp_csr_test\`"

# Run migrations on test database
php artisan migrate --database=mysql --env=testing

# Seed test database
php artisan db:seed --database=mysql --env=testing
```

### Debugging Tests

```bash
# Show detailed output
./vendor/bin/pest -vvv

# List all available tests
./vendor/bin/pest --list-tests

# Dry run (don't execute tests)
./vendor/bin/pest --dry-run

# Generate test report
./vendor/bin/pest --log-junit report.xml
```

## Test Coverage Status

### Current Statistics (as of last run)
- **Unit Tests**: 860+ passing (89% success rate)
- **Integration Tests**: 200+ passing (26% success rate)
- **Feature Tests**: 52+ passing
- **Browser Tests**: 3 example tests created with Pest 4 Browser Plugin (integrated into CI pipeline)

### Test Organization
- `tests/Unit/` - Pure unit tests with no database access
- `tests/Integration/` - Tests requiring database/infrastructure
- `tests/Feature/` - API and controller tests
- `tests/Browser/` - End-to-end browser tests with Pest 4 Browser Plugin

### Key Test Improvements Made
1. Fixed database configuration for test isolation
2. Removed `final` and `readonly` keywords from 27+ classes for mocking
3. Created 8+ missing factories with realistic test data
4. Separated unit tests from integration tests properly
5. Fixed Mockery compatibility issues
6. **Migrated to Pest 4 Browser Plugin** (replacing Laravel Dusk)
7. **Configured XDebug for code coverage** with Herd PHP 8.4

### Code Coverage Configuration

#### XDebug with Laravel Herd (PHP 8.4)

XDebug is pre-installed with Laravel Herd. To enable code coverage:

1. Edit Herd's PHP configuration:
```bash
# Location: /Users/[username]/Library/Application Support/Herd/config/php/84/php.ini
```

2. Add XDebug configuration:
```ini
; XDebug Configuration for Code Coverage
zend_extension=/Applications/Herd.app/Contents/Resources/xdebug/xdebug-84-arm64.so
xdebug.mode=coverage
xdebug.start_with_request=yes

; Disable JIT to prevent conflicts with XDebug
opcache.jit=off
opcache.jit_buffer_size=0
```

3. Verify installation:
```bash
php -m | grep xdebug
php -r "var_dump(function_exists('xdebug_code_coverage_started'));"
```

4. Run tests with coverage:
```bash
./vendor/bin/pest --coverage
./vendor/bin/pest --coverage --min=80
```

## Important Testing Notes

1. **Always use test database**: The `.env.testing` file is configured to use `acme_corp_csr_test` database
2. **Browser tests require server**: Run `php artisan serve` before running browser tests
3. **Factories are essential**: All models have factories in `database/factories/`
4. **Mocking is enabled**: Final/readonly keywords have been removed from critical classes
5. **Parallel testing**: Use `--parallel` flag for faster test execution

## Meilisearch Search & Indexing

### Automated Commands (Recommended)

```bash
# Complete post-deployment setup (includes Meilisearch configuration)
php artisan deploy:post

# Configure Meilisearch index settings only
php artisan meilisearch:configure

# Sync Scout index settings from config
php artisan scout:sync-index-settings

# Reindex all campaigns asynchronously (500k+ indexable campaigns)
php artisan scout:import-async 'Modules\Campaign\Domain\Model\Campaign'

# Monitor indexing progress
php artisan scout:index-monitor 'Modules\Campaign\Domain\Model\Campaign'
```

### Search Configuration

- **Auto-indexing**: Enabled (`SCOUT_QUEUE=true`)
- **Async processing**: Queue workers handle indexing
- **Indexable campaigns**: Only ACTIVE and COMPLETED status (~500k of 1M total)
- **Draft/Cancelled**: Not indexed (by design via `shouldBeSearchable()`)

### Troubleshooting

For search issues like "No campaigns found" or empty results:

```bash
# 1. Check if sortable attributes are configured
curl -s 'http://127.0.0.1:7700/indexes/acme_campaigns/settings/sortable-attributes' \
  -H 'Authorization: Bearer YOUR_MASTER_KEY'

# 2. Fix missing sortable attributes
php artisan scout:sync-index-settings

# 3. Reindex if needed
php artisan scout:import-async 'Modules\Campaign\Domain\Model\Campaign'

# 4. Monitor completion
php artisan scout:index-monitor 'Modules\Campaign\Domain\Model\Campaign'
```

**See**: [docs/infrastructure/meilisearch-troubleshooting.md](docs/infrastructure/meilisearch-troubleshooting.md) for comprehensive troubleshooting guide.

## Quick Test Commands

```bash
# Quick unit test check
./vendor/bin/pest --testsuite=Unit --parallel

# Quick integration test
./vendor/bin/pest --testsuite=Integration --stop-on-failure

# Quick browser test (local only)
./vendor/bin/pest tests/Browser/LoginTest.php

# Note: Browser tests run automatically in CI pipeline after unit/integration tests
# No separate browser test workflow needed - integrated into main CI

# Full test suite with quality checks
./vendor/bin/grumphp run && ./vendor/bin/pest
```

---

**Developed and Maintained by [Go2digit.al](https://go2digit.al)**

Specialized in enterprise-grade applications with focus on scalability, security, and maintainability.

Copyright 2025 Go2digit.al - All Rights Reserved