# ACME Corp CSR Platform - Coding Standards

## Overview

This document defines mandatory coding standards for the ACME Corp CSR platform. All code must comply with these standards to maintain consistency, quality, and architectural integrity.

## PHP Standards

### PSR-12 Extended Coding Style

All PHP code must follow PSR-12 with additional project-specific requirements:

#### File Structure
```php
<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Command;

use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;

final readonly class CreateCampaignCommandHandler
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository
    ) {}

    public function handle(CreateCampaignCommand $command): Campaign
    {
        // Implementation
    }
}
```

#### Mandatory Requirements
- `declare(strict_types=1);` in every PHP file
- Final classes by default (`final class`)
- Readonly properties (`readonly` keyword)
- Constructor property promotion
- Full type declarations for parameters and return types
- No mixed types - use unions when necessary

#### Class Declaration Standards
```php
// CORRECT - Final class with readonly properties
final readonly class CampaignService
{
    public function __construct(
        private CampaignRepositoryInterface $repository,
        private EventDispatcherInterface $eventDispatcher
    ) {}
}

// WRONG - Missing final, readonly, type declarations
class CampaignService
{
    private $repository;
    private $eventDispatcher;

    public function __construct($repository, $eventDispatcher)
    {
        $this->repository = $repository;
        $this->eventDispatcher = $eventDispatcher;
    }
}
```

## Laravel Conventions

### Controller Standards

#### Single Action Controllers (MANDATORY)
```php
// CORRECT - Single action controller
final class StoreCampaignController
{
    public function __construct(
        private readonly CreateCampaignCommandHandler $handler
    ) {}

    public function __invoke(CreateCampaignFormRequest $request): JsonResponse
    {
        $command = new CreateCampaignCommand(...$request->validated());
        $campaign = $this->handler->handle($command);

        return response()->json(new CampaignResource($campaign), 201);
    }
}

// WRONG - Multi-method controller
class CampaignController
{
    public function index() {}
    public function store() {}
    public function show() {}
    public function update() {}
    public function destroy() {}
}
```

#### Route Definitions
```php
// CORRECT - Single action routes
Route::post('/campaigns', StoreCampaignController::class);
Route::get('/campaigns/{id}', ShowCampaignController::class);
Route::put('/campaigns/{id}', UpdateCampaignController::class);

// WRONG - Resource routes
Route::resource('campaigns', CampaignController::class);
```

### Form Request Validation
```php
final class CreateCampaignFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'target_amount' => ['required', 'numeric', 'min:1'],
            'organization_id' => ['required', 'integer', 'exists:organizations,id'],
        ];
    }
}
```

### Eloquent Models
```php
final class Campaign extends Model
{
    protected $fillable = [
        'title',
        'description',
        'target_amount',
        'organization_id',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
```

## Hexagonal Architecture Rules

### Module Structure (MANDATORY)
```
modules/{Domain}/
├── Application/           # Use cases only
│   ├── Command/          # Write operations
│   ├── Query/            # Read operations
│   ├── Event/            # Domain events
│   ├── Processor/        # Complex workflows
│   └── Service/          # Application services
├── Domain/               # Pure business logic
│   ├── Model/           # Entities
│   ├── Repository/      # Interfaces only
│   ├── Service/         # Domain services
│   ├── ValueObject/     # Value objects
│   └── Exception/       # Domain exceptions
└── Infrastructure/       # External adapters
    ├── Laravel/         # Framework layer
    │   ├── Controllers/ # Single action only
    │   ├── FormRequest/ # Validation
    │   ├── Repository/  # Implementations
    │   ├── Provider/    # Service providers
    │   ├── Migration/   # Database schema
    │   ├── Factory/     # Test factories
    │   └── Seeder/      # Data seeders
    └── External/        # Third-party APIs
```

### Dependency Rules (NON-NEGOTIABLE)
1. **Domain → Nothing** (completely pure)
2. **Application → Domain only**
3. **Infrastructure → Application + Domain**
4. **Controllers → Application only** (never Domain directly)

### CQRS Implementation
```php
// Command (Write operation)
final readonly class CreateCampaignCommand implements CommandInterface
{
    public function __construct(
        public string $title,
        public string $description,
        public float $targetAmount,
        public int $organizationId
    ) {}
}

// Query (Read operation)
final readonly class FindCampaignQuery implements QueryInterface
{
    public function __construct(
        public int $id
    ) {}
}

// Command Handler
final readonly class CreateCampaignCommandHandler
{
    public function __construct(
        private CampaignRepositoryInterface $repository,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function handle(CreateCampaignCommand $command): Campaign
    {
        $campaign = new Campaign(
            title: $command->title,
            description: $command->description,
            targetAmount: new Money($command->targetAmount),
            organizationId: new OrganizationId($command->organizationId)
        );

        $this->repository->save($campaign);

        $this->eventDispatcher->dispatch(
            new CampaignCreatedEvent($campaign)
        );

        return $campaign;
    }
}
```

### Repository Pattern
```php
// Domain layer - Interface only
interface CampaignRepositoryInterface
{
    public function save(Campaign $campaign): void;
    public function findById(CampaignId $id): ?Campaign;
    public function findByOrganization(OrganizationId $organizationId): Collection;
}

// Infrastructure layer - Implementation
final readonly class CampaignEloquentRepository implements CampaignRepositoryInterface
{
    public function __construct(
        private Campaign $model
    ) {}

    public function save(Campaign $campaign): void
    {
        $this->model->create($campaign->toArray());
    }

    public function findById(CampaignId $id): ?Campaign
    {
        $model = $this->model->find($id->value());

        return $model ? Campaign::fromArray($model->toArray()) : null;
    }
}
```

### Value Objects
```php
final readonly class Money
{
    public function __construct(
        public float $amount,
        public string $currency = 'USD'
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }
    }

    public function add(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Currency mismatch');
        }

        return new Money($this->amount + $other->amount, $this->currency);
    }
}
```

## Testing Requirements

### Test Coverage
- **Minimum Coverage**: 80% overall
- **Unit Tests**: 90% coverage for domain and application layers
- **Integration Tests**: All repository implementations
- **Feature Tests**: All API endpoints
- **Browser Tests**: Critical user journeys

### Test Structure
```php
// Unit Test Example
final class CampaignTest extends TestCase
{
    public function test_can_create_campaign_with_valid_data(): void
    {
        $campaign = new Campaign(
            title: 'Help Local School',
            description: 'Fundraising for new computers',
            targetAmount: new Money(10000.00),
            organizationId: new OrganizationId(1)
        );

        $this->assertEquals('Help Local School', $campaign->title);
        $this->assertEquals(10000.00, $campaign->targetAmount->amount);
    }

    public function test_throws_exception_with_negative_target_amount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Campaign(
            title: 'Invalid Campaign',
            description: 'This should fail',
            targetAmount: new Money(-100.00),
            organizationId: new OrganizationId(1)
        );
    }
}
```

### Test Naming Conventions
- Test methods: `test_should_do_something_when_condition()`
- Test files: `{ClassUnderTest}Test.php`
- Factory files: `{Model}Factory.php`

### Test Categories
```php
// Unit Tests - No external dependencies
#[Group('unit')]
#[Group('campaign')]
final class CampaignTest extends TestCase
{
    use RefreshDatabase;
}

// Integration Tests - Database/infrastructure required
#[Group('integration')]
#[Group('campaign')]
final class CampaignRepositoryTest extends TestCase
{
    use RefreshDatabase;
}

// Feature Tests - Full HTTP stack
#[Group('feature')]
#[Group('api')]
final class CampaignApiTest extends TestCase
{
    use RefreshDatabase;
}
```

## Git Commit Conventions

### Commit Message Format
```
<type>(<scope>): <description>

<body>

<footer>
```

### Commit Types
- `feat`: New feature
- `fix`: Bug fix
- `refactor`: Code refactoring
- `test`: Adding/updating tests
- `docs`: Documentation changes
- `style`: Code style changes
- `perf`: Performance improvements
- `chore`: Maintenance tasks

### Examples
```
feat(campaign): add campaign creation endpoint

Implement single-action controller for campaign creation with:
- Form request validation
- CQRS command handler
- Event dispatching for campaign created

Closes #123

fix(donation): resolve payment processing timeout

Update payment gateway timeout from 30s to 60s to handle
high-volume transactions during peak hours.

Fixes #456

refactor(campaign): extract value objects from entity

Move Money and OrganizationId to separate value object classes
to improve domain model design and testability.

Related to #789
```

### Branch Naming
- Feature: `feature/campaign-creation`
- Bug fix: `fix/donation-timeout`
- Refactor: `refactor/extract-value-objects`

## Code Review Checklist

### Architecture Compliance
- [ ] Follows hexagonal architecture structure
- [ ] Dependency rules are respected
- [ ] Single action controllers only
- [ ] CQRS pattern implemented correctly
- [ ] Repository interfaces in domain layer
- [ ] Business logic in domain layer only

### Code Quality
- [ ] `declare(strict_types=1);` present
- [ ] Classes are final by default
- [ ] Properties are readonly where applicable
- [ ] Full type declarations used
- [ ] Constructor property promotion used
- [ ] No business logic in controllers
- [ ] Value objects used for domain concepts

### Testing
- [ ] Unit tests cover business logic
- [ ] Integration tests for repositories
- [ ] Feature tests for API endpoints
- [ ] Test coverage meets requirements
- [ ] No mocking of value objects
- [ ] Factories used for test data

### Laravel Conventions
- [ ] Form requests for validation
- [ ] Resource classes for API responses
- [ ] Eloquent relationships properly defined
- [ ] Database migrations follow conventions
- [ ] Service providers registered correctly

### Security
- [ ] Input validation implemented
- [ ] Authorization checks in place
- [ ] SQL injection prevention
- [ ] XSS protection enabled
- [ ] CSRF protection for state-changing operations

### Performance
- [ ] Database queries optimized
- [ ] N+1 query problems avoided
- [ ] Appropriate caching implemented
- [ ] Large datasets paginated
- [ ] Background jobs for heavy operations

### Documentation
- [ ] Complex business logic documented
- [ ] API endpoints documented
- [ ] Database schema documented
- [ ] Architecture decisions recorded

## Code Quality Tools

### Required Tools
- **Laravel Pint**: Code formatting (PSR-12)
- **PHPStan**: Static analysis (Level 8)
- **Deptrac**: Architecture boundaries
- **Pest**: Testing framework
- **GrumPHP**: Git hooks for quality checks

### Configuration Commands
```bash
# Format code
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse

# Architecture validation
./vendor/bin/deptrac analyse

# Run tests
./vendor/bin/pest

# All quality checks
./vendor/bin/grumphp run
```

## Do's and Don'ts

### DO
- Use single action controllers
- Implement CQRS for all operations
- Place business logic in domain layer
- Use value objects for domain concepts
- Write comprehensive tests
- Follow dependency rules strictly
- Use final classes by default
- Implement readonly properties
- Use constructor property promotion
- Declare strict types always

### DON'T
- Create multi-method controllers
- Put business logic in controllers
- Access infrastructure from domain
- Use mixed types unnecessarily
- Skip test coverage requirements
- Violate architectural boundaries
- Use anemic domain models
- Ignore static analysis warnings
- Commit without running quality checks
- Mix framework code with domain logic

---

Developed and Maintained by Go2Digital
Copyright 2025 Go2Digital - All Rights Reserved