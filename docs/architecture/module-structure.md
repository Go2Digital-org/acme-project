# Go2digit.al ACME Corp CSR Platform - Module Structure Guide

This document provides a comprehensive overview of the hexagonal architecture module structure used in the Go2digit.al ACME Corp CSR Platform.

## Overview

Each module in the platform follows a consistent hexagonal architecture pattern with three distinct layers:

```
modules/[ModuleName]/
├── Domain/              # Core business logic (innermost layer)
├── Application/         # Use cases and orchestration (middle layer)
└── Infrastructure/      # External adapters (outermost layer)
```

## Layer Responsibilities

### Domain Layer (Core)
The Domain layer contains pure business logic with **no external dependencies**.

```
Domain/
├── Model/              # Rich domain models with business rules
├── ValueObject/        # Immutable value objects for type safety
├── Repository/         # Repository interfaces (contracts only)
├── Specification/      # Business rule specifications
├── Exception/          # Domain-specific exceptions
└── Event/              # Domain events (optional)
```

**Key Principles:**
- No framework dependencies (Laravel, Eloquent, etc.)
- Contains all business rules and validation
- Immutable where possible
- Rich, expressive domain models

**Example Structure:**
```php
// Domain/Model/User.php
final class User {
    public function canCreateCampaigns(): bool {
        return $this->role->canCreateCampaigns() 
            && $this->isActive() 
            && $this->isVerified();
    }
}

// Domain/ValueObject/EmailAddress.php
final readonly class EmailAddress {
    public function __construct(public string $value) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailAddressException($value);
        }
    }
}

// Domain/Repository/UserRepositoryInterface.php
interface UserRepositoryInterface {
    public function save(User $user): void;
    public function findById(int $id): ?User;
}
```

### Application Layer (Use Cases)
The Application layer orchestrates domain objects to fulfill use cases.

```
Application/
├── Command/            # CQRS Commands and Command Handlers
│   ├── CreateUserCommand.php
│   └── CreateUserCommandHandler.php
├── Query/              # CQRS Queries and Query Handlers
│   ├── FindUserByIdQuery.php
│   └── FindUserByIdQueryHandler.php
├── ReadModel/          # Optimized read models for queries
│   └── UserProfileReadModel.php
└── Service/            # Application services (optional)
```

**Key Principles:**
- Orchestrates domain objects
- Implements use cases
- Handles cross-cutting concerns (logging, events)
- Contains no business logic (delegates to domain)

**Example Structure:**
```php
// Application/Command/CreateUserCommand.php
final readonly class CreateUserCommand implements CommandInterface {
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $role
    ) {}
}

// Application/Command/CreateUserCommandHandler.php
final readonly class CreateUserCommandHandler {
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private EventDispatcherInterface $eventDispatcher
    ) {}
    
    public function __invoke(CreateUserCommand $command): User {
        $user = User::create(
            firstName: $command->firstName,
            lastName: $command->lastName,
            email: new EmailAddress($command->email),
            role: UserRole::from($command->role)
        );
        
        $this->userRepository->save($user);
        
        $this->eventDispatcher->dispatch(
            new UserCreatedEvent($user->getId())
        );
        
        return $user;
    }
}
```

### Infrastructure Layer (Adapters)
The Infrastructure layer implements interfaces and handles external concerns.

```
Infrastructure/
├── ApiPlatform/        # API Platform adapters
│   ├── Handler/
│   │   ├── Processor/  # Write operation processors
│   │   └── Provider/   # Read operation providers
│   └── Resource/       # API resources
├── Filament/          # Admin interface adapters
│   ├── Pages/         # Custom admin pages
│   └── Resources/     # Admin resource definitions
└── Laravel/           # Laravel-specific implementations
    ├── Command/       # Artisan commands
    ├── Controllers/   # Single-action controllers
    ├── Factory/       # Model factories
    ├── Migration/     # Database migrations
    ├── Models/        # Eloquent models (persistence only)
    ├── Policies/      # Authorization policies
    ├── Provider/      # Service provider
    ├── Repository/    # Repository implementations
    └── Seeder/        # Database seeders
```

**Key Principles:**
- Implements domain and application interfaces
- Handles framework-specific concerns
- Manages external integrations
- Contains no business logic

**Example Structure:**
```php
// Infrastructure/Laravel/Controllers/CreateUserController.php
final readonly class CreateUserController {
    public function __construct(
        private CommandBusInterface $commandBus
    ) {}
    
    public function __invoke(CreateUserRequest $request): JsonResponse {
        $command = new CreateUserCommand(...$request->validated());
        $user = $this->commandBus->dispatch($command);
        
        return new JsonResponse(['id' => $user->getId()], 201);
    }
}

// Infrastructure/Laravel/Repository/UserEloquentRepository.php
final readonly class UserEloquentRepository implements UserRepositoryInterface {
    public function save(User $user): void {
        $eloquentModel = UserEloquentModel::firstOrNew(['id' => $user->getId()]);
        $eloquentModel->fill($user->toArray());
        $eloquentModel->save();
    }
}
```

## Module Examples

### Complete Module: User
The User module serves as the reference implementation for hexagonal architecture.

#### Domain Layer
```php
// modules/User/Domain/Model/User.php
final class User {
    public function __construct(
        private readonly int $id,
        private string $firstName,
        private string $lastName,
        private readonly EmailAddress $email,
        private UserStatus $status,
        private UserRole $role,
        private readonly DateTimeImmutable $createdAt,
    ) {}
    
    // Rich business logic
    public function canCreateCampaigns(): bool {
        return $this->role->canCreateCampaigns() 
            && $this->isActive() 
            && $this->isVerified();
    }
    
    public function promoteToAdmin(): void {
        if (!$this->isActive()) {
            throw new InvalidArgumentException('Cannot promote inactive user');
        }
        $this->role = UserRole::ADMIN;
    }
}

// modules/User/Domain/ValueObject/UserRole.php
enum UserRole: string {
    case ADMIN = 'admin';
    case EMPLOYEE = 'employee';
    
    public function canCreateCampaigns(): bool {
        return match($this) {
            self::ADMIN => true,
            self::EMPLOYEE => true,
        };
    }
}
```

#### Application Layer
```php
// modules/User/Application/Command/UpdateUserCommand.php
final readonly class UpdateUserCommand implements CommandInterface {
    public function __construct(
        public int $userId,
        public string $firstName,
        public string $lastName,
        public ?string $jobTitle = null
    ) {}
}

// modules/User/Application/Query/FindUserByIdQuery.php
final readonly class FindUserByIdQuery implements QueryInterface {
    public function __construct(public readonly int $userId) {}
}
```

#### Infrastructure Layer
```php
// modules/User/Infrastructure/Laravel/Controllers/UpdateUserController.php
final readonly class UpdateUserController {
    public function __invoke(
        int $userId, 
        UpdateUserRequest $request,
        CommandBusInterface $commandBus
    ): JsonResponse {
        $command = new UpdateUserCommand($userId, ...$request->validated());
        $user = $commandBus->dispatch($command);
        
        return new JsonResponse(['data' => new UserResource($user)]);
    }
}
```

### CQRS Implementation Example: Category
The Category module demonstrates full CQRS implementation.

```php
// modules/Category/Application/Command/CreateCategoryCommandHandler.php
final readonly class CreateCategoryCommandHandler {
    public function __invoke(CreateCategoryCommand $command): Category {
        $category = Category::create(
            name: $command->name,
            description: $command->description,
            color: CategoryColor::from($command->color)
        );
        
        $this->categoryRepository->save($category);
        
        return $category;
    }
}

// modules/Category/Application/Query/FindCategoriesQueryHandler.php
final readonly class FindCategoriesQueryHandler {
    public function __invoke(FindCategoriesQuery $query): CategoryListReadModel {
        return $this->categoryReadRepository->findAll(
            page: $query->page,
            limit: $query->limit,
            search: $query->searchTerm
        );
    }
}
```

## Directory Naming Conventions

### Files
- **Commands**: `{Action}{Entity}Command.php` (e.g., `CreateUserCommand.php`)
- **Command Handlers**: `{Action}{Entity}CommandHandler.php`
- **Queries**: `Find{Entity}ByIdQuery.php` or `List{Entities}Query.php`
- **Query Handlers**: `{QueryName}Handler.php`
- **Domain Models**: `{Entity}.php` (e.g., `User.php`, `Campaign.php`)
- **Value Objects**: Descriptive names (e.g., `EmailAddress.php`, `Money.php`)
- **Controllers**: Single action controllers `{Action}{Entity}Controller.php`

### Directories
- Use **PascalCase** for module names (`User`, `Campaign`, `Donation`)
- Use **singular names** for directories (`Model`, `Command`, `Query`)
- Use **descriptive names** that reflect purpose

## Dependency Management

### Service Provider Pattern
Each module has its own service provider for dependency injection:

```php
// modules/User/Infrastructure/Laravel/Provider/UserServiceProvider.php
final class UserServiceProvider extends ServiceProvider {
    public function register(): void {
        // Bind repository interfaces
        $this->app->bind(
            UserRepositoryInterface::class,
            UserEloquentRepository::class
        );
        
        // Register command handlers
        $this->app->bind(CreateUserCommandHandler::class);
        $this->app->bind(UpdateUserCommandHandler::class);
        
        // Register query handlers
        $this->app->bind(FindUserByIdQueryHandler::class);
    }
    
    public function boot(): void {
        // Register event listeners
        Event::listen(
            UserCreatedEvent::class,
            SendWelcomeEmailHandler::class
        );
    }
}
```

### Module Registration
Modules are automatically registered in the main application:

```php
// config/app.php
'providers' => [
    // Module Providers (auto-discovered)
    Modules\User\Infrastructure\Laravel\Provider\UserServiceProvider::class,
    Modules\Campaign\Infrastructure\Laravel\Provider\CampaignServiceProvider::class,
    Modules\Donation\Infrastructure\Laravel\Provider\DonationServiceProvider::class,
],
```

## Testing Structure

Each layer has corresponding tests with appropriate scope:

```
tests/
├── Unit/
│   └── User/
│       ├── Domain/
│       │   ├── Model/
│       │   └── ValueObject/
│       └── Application/
│           ├── Command/
│           └── Query/
├── Integration/
│   └── User/
│       └── Infrastructure/
│           └── Repository/
└── Feature/
    └── Api/
        └── User/
```

### Test Examples

```php
// tests/Unit/User/Domain/Model/UserTest.php
class UserTest extends TestCase {
    public function test_user_can_create_campaigns_when_active_and_verified(): void {
        $user = User::create(
            id: 1,
            firstName: 'John',
            lastName: 'Doe',
            email: new EmailAddress('john@example.com'),
            role: UserRole::EMPLOYEE
        );
        $user->verifyEmail(now());
        
        expect($user->canCreateCampaigns())->toBeTrue();
    }
}

// tests/Feature/Api/User/CreateUserTest.php
class CreateUserTest extends TestCase {
    public function test_creates_user_with_valid_data(): void {
        $response = $this->postJson('/api/users', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'role' => 'employee'
        ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }
}
```

## Module Communication

### Event-Driven Communication
Modules communicate through domain events:

```php
// Publisher (Campaign module)
class CampaignGoalReachedEvent implements DomainEventInterface {
    public function __construct(
        public readonly CampaignId $campaignId,
        public readonly Money $finalAmount
    ) {}
}

// Subscriber (Notification module)
class NotifyGoalReachedHandler {
    public function __invoke(CampaignGoalReachedEvent $event): void {
        // Send notifications
    }
}
```

### Query-Based Integration
Modules can query other modules through interfaces:

```php
// Interface in User module
interface FindUserQueryInterface {
    public function findById(int $id): ?UserReadModel;
}

// Usage in Campaign module
class CreateCampaignCommandHandler {
    public function __construct(
        private FindUserQueryInterface $userQuery
    ) {}
}
```

## Architectural Boundaries

### Dependency Rules
1. **Domain** depends on nothing
2. **Application** depends only on Domain
3. **Infrastructure** depends on Application and Domain
4. **Modules** can only depend on Shared module and specific allowed modules

### Enforcement
Boundaries are enforced through:

1. **Deptrac** - Architectural boundary testing
2. **PHPStan** - Static analysis
3. **Architecture Tests** - Runtime boundary validation

```php
// Architecture test example
test('domain layer has no external dependencies')
    ->expect('Modules\\User\\Domain')
    ->not->toUse([
        'Illuminate\\*',
        'Laravel\\*',
        'Modules\\Campaign',
        'Modules\\Donation'
    ]);
```

## Best Practices

### 1. Keep Domain Pure
- No framework dependencies in Domain layer
- Rich domain models with business logic
- Immutable value objects where possible

### 2. Single Responsibility Controllers
- One action per controller
- Minimal logic - delegate to application layer
- Clear, descriptive method names

### 3. Explicit Dependencies
- Constructor injection for all dependencies
- Interface-based abstractions
- No service locator pattern

### 4. Consistent Naming
- Follow established naming conventions
- Use domain language (ubiquitous language)
- Be explicit and descriptive

### 5. Comprehensive Testing
- Unit tests for domain logic
- Integration tests for infrastructure
- Feature tests for API endpoints

## Module Migration Checklist

When creating or migrating a module to hexagonal architecture:

- [ ] **Domain Layer**
  - [ ] Extract domain models from Eloquent models
  - [ ] Create value objects for primitive types
  - [ ] Define repository interfaces
  - [ ] Implement domain specifications

- [ ] **Application Layer**
  - [ ] Create command/query objects
  - [ ] Implement command/query handlers
  - [ ] Define read models for queries
  - [ ] Set up event handling

- [ ] **Infrastructure Layer**
  - [ ] Create single-action controllers
  - [ ] Implement repository with Eloquent
  - [ ] Set up API Platform resources
  - [ ] Create Filament admin interfaces

- [ ] **Testing**
  - [ ] Write unit tests for domain logic
  - [ ] Create integration tests for repositories
  - [ ] Add feature tests for API endpoints
  - [ ] Include architecture boundary tests

- [ ] **Documentation**
  - [ ] Update module README
  - [ ] Document public interfaces
  - [ ] Add usage examples
  - [ ] Update architecture diagrams

## Troubleshooting Common Issues

### Issue: Circular Dependencies
**Solution**: Use events for inter-module communication instead of direct dependencies

### Issue: Complex Queries in Domain
**Solution**: Move complex queries to Application layer query handlers

### Issue: Framework Code in Domain
**Solution**: Create abstractions and move implementations to Infrastructure layer

### Issue: Fat Controllers
**Solution**: Extract logic to Command/Query handlers, keep controllers thin

### Issue: Anemic Domain Models
**Solution**: Move business logic from services to domain models

## References

- [Hexagonal Architecture](./hexagonal-architecture.md) - Detailed hexagonal architecture implementation
- [Module Interactions](./module-interactions.md) - Cross-module communication patterns
- [API Platform Design](./api-platform-design.md) - API integration patterns
- [Developer Guide](../development/developer-guide.md) - Complete development workflow
- [Hex Commands Guide](../development/hex-commands.md) - Creating new modules with hex commands
- [Code Quality](../development/code-quality.md) - Testing and quality standards

---

**Developed and Maintained by Go2digit.al**

Specialized in enterprise-grade applications with focus on scalability, security, and maintainability.

Copyright 2025 Go2digit.al - All Rights Reserved