# Getting Started - Developer Onboarding Guide

## Overview

This guide will help new developers get up and running with the ACME Corp CSR Donation Platform. The platform demonstrates enterprise-grade technical leadership through strategic architectural decisions and comprehensive development practices.

## Prerequisites

### Required Software

- **PHP 8.4+** - Latest stable version with modern features
- **Composer 2.x** - Dependency management
- **Node.js 20.x** - LTS version for frontend tooling
- **MySQL 8.0+** - Primary database (or PostgreSQL 13+)
- **Redis 7.0+** - Caching and queue management (optional for development)
- **Git** - Version control

### Recommended IDE Extensions

#### VS Code
- **Laravel Extension Pack** - Complete Laravel development suite
- **Vue - Official** - Vue.js 3 support with TypeScript
- **PHP Intelephense** - Advanced PHP language support
- **Laravel Pint** - Code formatting
- **PHPStan** - Static analysis integration
- **ESLint** - JavaScript/TypeScript linting
- **Tailwind CSS IntelliSense** - CSS framework support

#### PHPStorm
- **Laravel Plugin** - Laravel-specific features
- **Vue.js Plugin** - Vue.js development support
- **PHP Inspections (EA Extended)** - Additional code quality checks

## Initial Setup

### 1. Repository Setup

```bash
# Clone the repository
git clone git@github.com:your-username/acme-corp-optimy.git
cd acme-corp-optimy

# Verify you're on the correct branch
git checkout dev

# Create your feature branch
git checkout -b feature/your-feature-name
```

### 2. Environment Configuration

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Database Setup

#### Option A: MySQL (Recommended for Production Simulation)

```bash
# Connect to MySQL
mysql -u root -p

# Create databases
CREATE DATABASE `acme-corp-optimy` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE `acme-corp-optimy-test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

Update `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=acme-corp-optimy
DB_USERNAME=root
DB_PASSWORD=# Set in .env

# Test database (automatically used during testing)
DB_TEST_DATABASE=acme-corp-optimy-test
```

#### Option B: SQLite (Simplified Development)

```bash
# Create database files
touch database/database.sqlite
touch database/testing.sqlite
```

Update `.env`:
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/your/project/database/database.sqlite
```

### 4. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies
npm install
```

### 5. Database Migration and Seeding

```bash
# Run migrations
php artisan migrate

# Seed the database with development data
php artisan db:seed

# Or combine both
php artisan migrate:fresh --seed
```

### 6. Build Frontend Assets

```bash
# Development build with hot reloading
npm run dev

# Or production build
npm run build
```

### 7. Start Development Environment

```bash
# Start Laravel development server
php artisan serve

# In separate terminals, optionally run:
# php artisan queue:work
# npm run dev
```

The application will be available at: `http://localhost:8000`

## Project Structure Overview

### Hexagonal Architecture Layout

```
acme-corp-optimy/
├── modules/                    # Domain modules (business logic)
│   ├── Campaign/              # Campaign management domain
│   │   ├── Application/       # Use cases (Commands/Queries)
│   │   ├── Domain/           # Pure business logic
│   │   └── Infrastructure/   # Framework implementations
│   ├── Donation/             # Donation processing domain
│   ├── Employee/             # Employee management domain
│   ├── Organization/         # Organization domain
│   ├── Security/             # Security and audit domain
│   └── Shared/               # Shared kernel
├── app/                      # Laravel application bootstrap
├── config/                   # Configuration files
├── database/                 # Migrations, seeders, factories
├── docs/                     # Comprehensive documentation
├── resources/                # Frontend assets and views
├── routes/                   # Route definitions
├── tests/                    # Test suite
└── vendor/                   # Composer dependencies
```

### Key Architectural Principles

1. **Hexagonal Architecture**: Business logic isolated from framework
2. **Domain-Driven Design**: Clear bounded contexts and ubiquitous language
3. **CQRS Pattern**: Separate read and write operations
4. **Single Responsibility**: Each class has one reason to change
5. **Dependency Inversion**: Depend on abstractions, not concretions

## Development Workflow

### 1. Feature Development Process

#### Step 1: Understand the Domain
```bash
# Study the existing domain structure
ls modules/Campaign/Domain/Model/
cat modules/Campaign/Domain/Model/Campaign.php

# Review domain events and value objects
ls modules/Campaign/Domain/Event/
ls modules/Campaign/Domain/ValueObject/
```

#### Step 2: Create Domain Components
```bash
# Use hexagonal architecture generators
php artisan add:hex:menu

# Or create specific components
php artisan add:hex:command CreateSomething --domain=Campaign
php artisan add:hex:query FindSomething --domain=Campaign
php artisan add:hex:repository Something --domain=Campaign
```

#### Step 3: Implement Business Logic
```php
// Example: Domain model with business rules
final class Campaign {
    public function acceptDonation(Money $amount): DonationResult {
        if (!$this->status->isActive()) {
            return DonationResult::rejected('Campaign is not active');
        }
        
        if ($this->wouldExceedGoal($amount)) {
            return DonationResult::rejected('Donation would exceed goal');
        }
        
        $this->raised = $this->raised->add($amount);
        
        return DonationResult::accepted($amount);
    }
}
```

#### Step 4: Create Infrastructure Adapters
```php
// Example: Controller (single action)
final readonly class CreateCampaignController {
    public function __invoke(
        CreateCampaignRequest $request,
        CommandBusInterface $commandBus
    ): JsonResponse {
        $command = new CreateCampaignCommand(...$request->validated());
        $campaign = $commandBus->dispatch($command);
        
        return new JsonResponse(['id' => $campaign->getId()]);
    }
}
```

#### Step 5: Write Tests
```php
// Domain logic test (no framework dependencies)
class CampaignTest extends TestCase {
    public function test_campaign_accepts_valid_donation(): void {
        $campaign = new Campaign(/* ... */);
        
        $result = $campaign->acceptDonation(Money::euros(100));
        
        $this->assertTrue($result->wasAccepted());
    }
}

// Integration test
class CreateCampaignTest extends TestCase {
    public function test_can_create_campaign_via_api(): void {
        $response = $this->postJson('/api/campaigns', [
            'name' => 'Test Campaign',
            'goal' => 1000
        ]);
        
        $response->assertStatus(201);
    }
}
```

### 2. Testing Workflow

```bash
# Run all tests
./vendor/bin/pest

# Run specific test suites
./vendor/bin/pest --testsuite=Unit
./vendor/bin/pest --testsuite=Feature
./vendor/bin/pest --testsuite=Integration

# Run tests with coverage
./vendor/bin/pest --coverage --min=80

# Run specific test file
./vendor/bin/pest tests/Unit/Campaign/Domain/CampaignTest.php

# Run tests in parallel (faster)
./vendor/bin/pest --parallel
```

### 3. Code Quality Workflow

```bash
# Check code style
composer pint:test

# Fix code style
composer pint

# Run static analysis
composer stan

# Run all quality checks
composer lint

# Fix all auto-fixable issues
composer lint:fix

# Run before committing
composer pre-commit
```

### 4. Git Workflow

```bash
# Create feature branch
git checkout -b feature/add-campaign-categories

# Make commits with conventional format
git commit -m "feat(campaign): add category support to campaigns"
git commit -m "test(campaign): add tests for campaign categories"
git commit -m "docs(campaign): update campaign documentation"

# Push branch
git push origin feature/add-campaign-categories

# Create pull request (use GitHub CLI or web interface)
gh pr create --title \"Add Campaign Categories\" --body \"Implements category support for better campaign organization\"
```

## Development Commands Reference

### Laravel Commands

```bash
# Application
php artisan serve                    # Start development server
php artisan queue:work              # Start queue worker
php artisan schedule:work           # Run scheduler (development)
php artisan tinker                  # Interactive shell

# Database
php artisan migrate                 # Run migrations
php artisan migrate:fresh --seed    # Fresh migration with seeding
php artisan db:seed                 # Run seeders
php artisan migrate:rollback        # Rollback last migration

# Cache
php artisan cache:clear             # Clear application cache
php artisan config:clear            # Clear configuration cache
php artisan route:clear             # Clear route cache
php artisan view:clear              # Clear view cache

# Optimization (production)
php artisan optimize                # Optimize application
php artisan config:cache            # Cache configuration
php artisan route:cache             # Cache routes
php artisan view:cache              # Cache views
```

### Custom Hexagonal Commands

```bash
# Interactive menu
php artisan add:hex:menu

# Domain generation
php artisan add:hex:domain NewDomain
php artisan add:hex:structure NewDomain

# Component generation
php artisan add:hex:command
php artisan add:hex:find-query
php artisan add:hex:repository
php artisan add:hex:form-request
php artisan add:hex:migration
```

### Frontend Commands

```bash
# Development
npm run dev                         # Start Vite development server
npm run build                       # Build for production
npm run preview                     # Preview production build

# Testing
npm run test                        # Run JavaScript tests
npm run test:coverage              # Run tests with coverage

# Linting
npm run lint                       # Run ESLint
npm run format                     # Format with Prettier
npm run type-check                 # TypeScript type checking
```

### Quality Assurance Commands

```bash
# Code formatting
vendor/bin/pint                     # Format PHP code
vendor/bin/pint --test             # Check formatting without fixing

# Static analysis
vendor/bin/phpstan analyse          # Run PHPStan analysis
vendor/bin/phpstan analyse --level=8 # Specific level

# Architecture validation
vendor/bin/deptrac analyse          # Check architectural boundaries

# Code modernization
vendor/bin/rector process           # Apply Rector rules
vendor/bin/rector process --dry-run # Preview changes

# Git hooks
vendor/bin/grumphp run             # Run all git hooks
```

## Debugging and Troubleshooting

### Laravel Telescope (Development)

```bash
# Install Telescope (if not already installed)
php artisan telescope:install
php artisan migrate

# Access at: http://localhost:8000/telescope
```

Telescope provides:
- Request monitoring
- Database query analysis
- Job queue monitoring
- Cache operations
- Mail preview

### Debug Bar (Development)

The application includes Laravel Debugbar for development:
- SQL queries and execution time
- Memory usage
- View rendering time
- Route information
- Session data

### Common Issues and Solutions

#### Issue: \"Class not found\" errors
```bash
# Solution: Regenerate autoload files
composer dump-autoload
```

#### Issue: Permission errors on storage
```bash
# Solution: Fix storage permissions
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

#### Issue: NPM build fails
```bash
# Solution: Clear npm cache and reinstall
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
```

#### Issue: Tests fail with database errors
```bash
# Solution: Ensure test database is properly configured
php artisan migrate --env=testing
```

#### Issue: Queue jobs not processing
```bash
# Solution: Restart queue worker
php artisan queue:restart
php artisan queue:work
```

## IDE Configuration

### VS Code Settings

Create `.vscode/settings.json`:
```json
{
  \"editor.formatOnSave\": true,
  \"editor.codeActionsOnSave\": {
    \"source.fixAll.eslint\": true
  },
  \"[php]\": {
    \"editor.defaultFormatter\": \"open-southeners.laravel-pint\"
  },
  \"[vue]\": {
    \"editor.defaultFormatter\": \"esbenp.prettier-vscode\"
  },
  \"phpstan.enabled\": true,
  \"phpstan.level\": \"8\",
  \"typescript.preferences.includePackageJsonAutoImports\": \"on\"
}
```

### PHPStorm Configuration

1. **PHP Interpreter**: Configure PHP 8.4 interpreter
2. **Laravel Plugin**: Enable Laravel plugin and configure project root
3. **Code Style**: Import Laravel/PSR-12 code style
4. **PHPStan**: Configure PHPStan integration at level 8
5. **Vue.js**: Enable Vue.js plugin for SFC support

## Environment Variables Reference

### Application Configuration
```env
APP_NAME=\"ACME Corp CSR Platform\"
APP_ENV=local
APP_KEY=base64:your-key-here
APP_DEBUG=true
APP_URL=http://localhost:8000

# Locale settings
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=UTC
```

### Database Configuration
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=acme-corp-optimy
DB_USERNAME=root
DB_PASSWORD=
DB_TEST_DATABASE=acme-corp-optimy-test
```

### Cache & Session Configuration
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Mail Configuration (Development)
```env
MAIL_MAILER=log
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME=\"${APP_NAME}\"
```

### Payment Gateway Configuration (Development)
```env
# Mollie (Test Mode)
MOLLIE_API_KEY=test_your_mollie_test_key
MOLLIE_WEBHOOK_SECRET=# Set in .env

# Stripe (Test Mode)  
STRIPE_PUBLIC_KEY=pk_test_your_stripe_test_key
STRIPE_SECRET_KEY=sk_test_your_stripe_test_key
STRIPE_WEBHOOK_SECRET=# Set in .env
```

## Next Steps

After completing the setup:

1. **Explore the Codebase**: Review existing domain modules and their structure
2. **Run Tests**: Ensure all tests pass in your environment
3. **Read Architecture Documentation**: Understand the hexagonal architecture principles
4. **Try Creating a Feature**: Use the hex generators to create a simple feature
5. **Review Code Quality Tools**: Understand the quality gates and standards
6. **Set Up IDE Integration**: Configure your IDE for optimal development experience

## Getting Help

- **Documentation**: Comprehensive docs in `/docs` directory
- **Code Examples**: Study existing implementations in `/modules`
- **Architecture Guide**: `/docs/development/hex-commands.md` for hexagonal architecture commands
- **Testing Guide**: `/docs/development/code-quality.md` - Testing patterns and quality standards
- **Technical Support**: Contact info@go2digit.al for technical discussions

Welcome to the ACME Corp CSR Development Team! This platform demonstrates enterprise-grade technical leadership through thoughtful architectural decisions, comprehensive testing, and maintainable code practices.

---

**Developed and Maintained by Go2digit.al**

Specialized in enterprise-grade applications with focus on scalability, security, and maintainability.

Copyright 2025 Go2digit.al - All Rights Reserved