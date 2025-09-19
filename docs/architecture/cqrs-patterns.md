# CQRS Patterns in ACME Corp CSR Platform

## Table of Contents

1. [Overview](#overview)
2. [CQRS Pattern Explanation](#cqrs-pattern-explanation)
3. [Command Pattern Implementation](#command-pattern-implementation)
4. [Query Pattern Implementation](#query-pattern-implementation)
5. [Command/Query Handlers](#commandquery-handlers)
6. [Event Handling and Domain Events](#event-handling-and-domain-events)
7. [CQRS Integration with API Platform](#cqrs-integration-with-api-platform)
8. [Read Models](#read-models)
9. [Best Practices](#best-practices)
10. [Testing CQRS Components](#testing-cqrs-components)
11. [Common Patterns and Anti-patterns](#common-patterns-and-anti-patterns)
12. [Real Examples from Codebase](#real-examples-from-codebase)

## Overview

The ACME Corp CSR Platform implements Command Query Responsibility Segregation (CQRS) as a fundamental architectural pattern to separate write operations (commands) from read operations (queries). This separation enables:

- **Scalability**: Different optimization strategies for reads vs writes
- **Maintainability**: Clear separation of concerns
- **Performance**: Optimized read models for specific use cases
- **Domain Clarity**: Commands express business intent, queries focus on data retrieval

## CQRS Pattern Explanation

### Core Principles

CQRS divides the application into two distinct sides:

1. **Command Side (Write Model)**
   - Handles business operations and state changes
   - Enforces business rules and validation
   - Publishes domain events
   - Uses normalized domain models

2. **Query Side (Read Model)**
   - Optimized for data retrieval
   - Denormalized views for specific use cases
   - No business logic, pure data access
   - Can use different storage mechanisms

### Architecture Flow

```
API Request → Processor → Command Bus → Command Handler → Domain Model → Event Publisher
                                                              ↓
Query Request → Query Handler → Read Model → Optimized Data Retrieval
                                    ↑
                            Event Listener ← Domain Events
```

## Command Pattern Implementation

### Command Interface

All commands implement the base `CommandInterface`:

```php
namespace Modules\Shared\Application\Command;

interface CommandInterface {}
```

### Command Structure

Commands are immutable data structures that represent business intent:

```php
// Real example: CreateCampaignCommand
final readonly class CreateCampaignCommand implements CommandInterface
{
    public function __construct(
        public array $title,              // Multilingual title
        public array $description,        // Multilingual description
        public float $goalAmount,         // Business requirement
        public string $startDate,         // Validation constraint
        public string $endDate,           // Validation constraint
        public int $organizationId,       // Domain relationship
        public int $userId,               // Business context
        public ?string $locale = 'en',    // Localization
        public ?string $category = null,  // Optional classification
        public ?int $categoryId = null,   // Optional relationship
        public array $metadata = [],      // Extensibility
        public string $status = 'draft',  // Initial state
    ) {}
}
```

### Command Naming Conventions

Commands follow the pattern `{Verb}{Entity}Command`:

- `CreateCampaignCommand` - Creates a new campaign
- `ApproveCampaignCommand` - Approves a pending campaign
- `UpdateCampaignCommand` - Updates campaign details
- `CompleteCampaignCommand` - Marks campaign as completed
- `RejectCampaignCommand` - Rejects a pending campaign

### Command Validation

Commands should be self-validating with meaningful constraints:

```php
final readonly class ApproveCampaignCommand implements CommandInterface
{
    public function __construct(
        public int $campaignId,
        public int $approverId,
        public ?string $notes = null,
    ) {
        // Implicit validation through type system
        // Additional validation in handler
    }
}
```

## Query Pattern Implementation

### Query Interface

All queries implement the base `QueryInterface`:

```php
namespace Modules\Shared\Application\Query;

interface QueryInterface {}
```

### Query Structure

Queries are simple data structures focusing on retrieval criteria:

```php
// Real example: FindCampaignByIdQuery
final readonly class FindCampaignByIdQuery implements QueryInterface
{
    public function __construct(
        public int $campaignId,
    ) {}
}

// Complex query example: SearchCampaignsQuery
final readonly class SearchCampaignsQuery implements QueryInterface
{
    public function __construct(
        public ?string $search = null,
        public ?string $status = null,
        public ?int $organizationId = null,
        public ?int $categoryId = null,
        public ?string $sortBy = 'created_at',
        public string $sortDirection = 'desc',
        public int $page = 1,
        public int $perPage = 15,
        public ?array $filters = null,
    ) {}
}
```

### Query Naming Conventions

Queries follow descriptive patterns:

- `Find{Entity}ByIdQuery` - Single entity retrieval
- `List{Entities}Query` - Collection retrieval
- `Get{Entity}StatsQuery` - Aggregated data
- `Search{Entities}Query` - Search operations

## Command/Query Handlers

### Command Handler Interface

```php
namespace Modules\Shared\Application\Command;

interface CommandHandlerInterface
{
    public function handle(CommandInterface $command): mixed;
}
```

### Command Handler Implementation

Command handlers contain business logic and orchestrate domain operations:

```php
class CreateCampaignCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly CampaignRepositoryInterface $repository,
    ) {}

    public function handle(CommandInterface $command): Campaign
    {
        // 1. Type safety validation
        if (! $command instanceof CreateCampaignCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        // 2. Transaction boundary
        return DB::transaction(function () use ($command): Campaign {
            // 3. Locale context setup
            if ($command->locale) {
                App::setLocale($command->locale);
            }

            // 4. Business logic and domain model creation
            $status = $command->status === 'pending_approval'
                ? CampaignStatus::PENDING_APPROVAL
                : CampaignStatus::DRAFT;

            $campaignData = [
                'uuid' => Str::uuid()->toString(),
                'title' => $command->title,
                'description' => $command->description,
                'goal_amount' => $command->goalAmount,
                'current_amount' => 0,
                'start_date' => $command->startDate,
                'end_date' => $command->endDate,
                'status' => $status->value,
                'organization_id' => $command->organizationId,
                'user_id' => $command->userId,
                'category' => $command->category,
                'category_id' => $command->categoryId,
                'metadata' => $command->metadata === [] ? null : $command->metadata,
            ];

            // 5. Domain model persistence
            $campaign = $this->repository->create($campaignData);

            // 6. Business rule validation
            $campaign->validateDateRange();
            $campaign->validateGoalAmount();

            // 7. Domain event publication
            event(new CampaignCreatedEvent(
                campaignId: $campaign->id,
                userId: $command->userId,
                organizationId: $command->organizationId,
                title: $command->title[$command->locale ?? 'en'] ?? '',
                goalAmount: $command->goalAmount,
            ));

            return $campaign;
        });
    }
}
```

### Query Handler Implementation

Query handlers focus on optimized data retrieval:

```php
final readonly class FindCampaignByIdQueryHandler implements QueryHandlerInterface
{
    public function __construct(
        private CampaignRepositoryInterface $repository,
    ) {}

    public function handle(QueryInterface $query): Campaign
    {
        if (! $query instanceof FindCampaignByIdQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        $campaign = $this->repository->findById($query->campaignId);

        if (! $campaign instanceof Campaign) {
            throw CampaignException::notFound($query->campaignId);
        }

        return $campaign;
    }
}
```

### Handler Responsibilities

**Command Handlers:**
- Business logic execution
- Transaction management
- Domain validation
- Event publishing
- State mutation

**Query Handlers:**
- Data retrieval optimization
- Read model construction
- Filtering and sorting
- Pagination
- Caching strategies

## Event Handling and Domain Events

### Domain Event Structure

Domain events capture business-significant occurrences:

```php
class CampaignCreatedEvent extends AbstractDomainEvent implements CampaignEventInterface
{
    public function __construct(
        public readonly int $campaignId,
        public readonly int $userId,
        public readonly int $organizationId,
        public readonly string $title,
        public readonly float $goalAmount,
        public readonly ?array $titleTranslations = null,
        public readonly ?array $descriptionTranslations = null,
        public readonly ?string $locale = 'en',
    ) {
        parent::__construct();
    }

    public function getEventName(): string
    {
        return 'campaign.created';
    }

    public function getCampaignId(): int
    {
        return $this->campaignId;
    }

    public function getEventData(): array
    {
        return [
            'campaignId' => $this->campaignId,
            'userId' => $this->userId,
            'organizationId' => $this->organizationId,
            'title' => $this->title,
            'goalAmount' => $this->goalAmount,
            'titleTranslations' => $this->titleTranslations,
            'descriptionTranslations' => $this->descriptionTranslations,
            'locale' => $this->locale,
        ];
    }
}
```

### Event Publishing

Events are published from command handlers after successful operations:

```php
// Inside command handler
event(new CampaignCreatedEvent(
    campaignId: $campaign->id,
    userId: $command->userId,
    organizationId: $command->organizationId,
    title: $title,
    goalAmount: $command->goalAmount,
));
```

### Event-Driven Side Effects

Events trigger side effects like:

- **Notifications**: Email/SMS to stakeholders
- **Analytics**: Tracking and metrics
- **Search Indexing**: Meilisearch updates
- **Read Model Updates**: Denormalized views
- **Audit Logging**: Change tracking

## CQRS Integration with API Platform

### API Platform Processors

Processors bridge API Platform with CQRS command bus:

```php
final readonly class CreateOrganizationProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): OrganizationResource {
        if (! is_object($data)) {
            throw new InvalidArgumentException('Data must be an object');
        }

        // 1. Transform API input to command
        $command = new CreateOrganizationCommand(
            name: $data->name ?? null,
            registrationNumber: $data->registration_number ?? null,
            taxId: $data->tax_id ?? null,
            category: $data->category ?? null,
            website: $data->website ?? null,
            email: $data->email ?? null,
            phone: $data->phone ?? null,
            address: $data->address ?? null,
            city: $data->city ?? null,
            country: $data->country ?? null,
        );

        // 2. Execute command via command bus
        $organization = $this->commandBus->handle($command);

        // 3. Transform domain model to API resource
        return OrganizationResource::fromModel($organization);
    }
}
```

### Command Bus Interface

```php
interface CommandBusInterface
{
    /**
     * Handle a command and return its result.
     */
    public function handle(CommandInterface $command): mixed;

    /**
     * Dispatch a command without expecting a result.
     */
    public function dispatch(CommandInterface $command): void;
}
```

### Integration Benefits

1. **Decoupling**: API layer separate from business logic
2. **Validation**: Command validation independent of API
3. **Transformation**: Clean data flow between layers
4. **Testing**: Easy to mock command bus for API tests

## Read Models

### Read Model Structure

Read models are optimized for specific query patterns:

```php
final class CampaignReadModel extends AbstractReadModel
{
    public function __construct(
        int $campaignId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($campaignId, $data, $version);
        $this->setCacheTtl(900); // 15 minutes cache
    }

    // Optimized getters for common access patterns
    public function getCampaignId(): int
    {
        return (int) $this->id;
    }

    public function getProgressPercentage(): float
    {
        $target = $this->getTargetAmount();
        if ($target <= 0) {
            return 0.0;
        }
        return min(100, ($this->getCurrentAmount() / $target) * 100);
    }

    public function getRemainingDays(): int
    {
        $end = $this->getEndDate();
        if (! $end) {
            return 0;
        }
        $endTime = strtotime($end);
        $remaining = (int) (($endTime - time()) / (60 * 60 * 24));
        return max(0, $remaining);
    }

    // Cache optimization
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'campaign',
            'campaign:' . $this->id,
            'organization:' . $this->getOrganizationId(),
            'status:' . $this->getStatus(),
        ]);
    }

    // Formatted output for different contexts
    public function toSummary(): array
    {
        return [
            'id' => $this->getCampaignId(),
            'title' => $this->getTitle(),
            'status' => $this->getStatus(),
            'progress_percentage' => $this->getProgressPercentage(),
            'remaining_days' => $this->getRemainingDays(),
            'is_featured' => $this->isFeatured(),
            // ... optimized for list views
        ];
    }
}
```

### Read Model Builders

Builders construct read models from database queries:

```php
class CampaignListReadModelBuilder
{
    public function buildForOrganization(int $organizationId): Collection
    {
        // Optimized query with joins for list view
        $campaigns = DB::table('campaigns')
            ->leftJoin('organizations', 'campaigns.organization_id', '=', 'organizations.id')
            ->leftJoin('users', 'campaigns.user_id', '=', 'users.id')
            ->where('campaigns.organization_id', $organizationId)
            ->select([
                'campaigns.*',
                'organizations.name as organization_name',
                'users.name as creator_name',
                // ... other optimized fields
            ])
            ->get();

        return $campaigns->map(function ($campaign) {
            return new CampaignReadModel($campaign->id, (array) $campaign);
        });
    }
}
```

## Best Practices

### Command Design

1. **Immutability**: Use `readonly` classes
2. **Intent-Revealing Names**: Clear business purpose
3. **Single Responsibility**: One business operation per command
4. **Data Validation**: Type safety and constraints
5. **Rich Types**: Value objects over primitive types

```php
// ✅ Good: Clear intent and immutable
final readonly class ApproveCampaignCommand implements CommandInterface
{
    public function __construct(
        public int $campaignId,
        public int $approverId,
        public ?string $notes = null,
    ) {}
}

// ❌ Bad: Generic and mutable
class UpdateCommand
{
    public mixed $data;
    public function setData($data) { $this->data = $data; }
}
```

### Query Design

1. **Specific Purpose**: Tailored to use case
2. **Minimal Data**: Only required fields
3. **Performance Focus**: Optimized retrieval
4. **Pagination Support**: For large datasets

```php
// ✅ Good: Specific and optimized
final readonly class GetActiveCampaignsByOrganizationQuery implements QueryInterface
{
    public function __construct(
        public int $organizationId,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}

// ❌ Bad: Generic and inefficient
class GetAllDataQuery implements QueryInterface {}
```

### Handler Design

1. **Single Responsibility**: One command/query type
2. **Transaction Boundaries**: Proper DB transactions
3. **Error Handling**: Meaningful exceptions
4. **Event Publishing**: After successful operations
5. **Type Safety**: Validate command/query types

### Event Design

1. **Business Significance**: Meaningful to domain
2. **Immutable Data**: Capture moment in time
3. **Complete Information**: All relevant context
4. **Backward Compatibility**: Event versioning

## Testing CQRS Components

### Command Handler Testing

```php
beforeEach(function (): void {
    $this->repository = app(CampaignRepositoryInterface::class);
    $this->handler = new ApproveCampaignCommandHandler($this->repository);
    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('super_admin');

    $this->campaign = Campaign::factory()->create([
        'status' => CampaignStatus::PENDING_APPROVAL->value,
    ]);
});

it('successfully approves campaign and sets to active', function (): void {
    Event::fake();

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id
    );

    $result = $this->handler->handle($command);

    expect($result)->toBeInstanceOf(Campaign::class)
        ->and($result->status)->toBe(CampaignStatus::ACTIVE)
        ->and($result->approved_by)->toBe($this->superAdmin->id)
        ->and($result->approved_at)->not->toBeNull();

    // Verify database update
    $this->assertDatabaseHas('campaigns', [
        'id' => $this->campaign->id,
        'status' => CampaignStatus::ACTIVE->value,
        'approved_by' => $this->superAdmin->id,
    ]);
});

it('dispatches campaign status changed event', function (): void {
    Event::fake();

    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id
    );

    $this->handler->handle($command);

    Event::assertDispatched(CampaignStatusChangedEvent::class, function ($event) {
        return $event->campaign->id === $this->campaign->id
            && $event->previousStatus === CampaignStatus::PENDING_APPROVAL
            && $event->newStatus === CampaignStatus::ACTIVE
            && $event->changedByUserId === $this->superAdmin->id;
    });
});
```

### Query Handler Testing

```php
it('finds campaign by id successfully', function (): void {
    $campaign = Campaign::factory()->create();

    $query = new FindCampaignByIdQuery($campaign->id);
    $result = $this->handler->handle($query);

    expect($result)->toBeInstanceOf(Campaign::class)
        ->and($result->id)->toBe($campaign->id);
});

it('throws exception when campaign not found', function (): void {
    $query = new FindCampaignByIdQuery(999999);

    expect(fn () => $this->handler->handle($query))
        ->toThrow(CampaignException::class);
});
```

### Event Testing

```php
it('dispatches campaign approved event', function (): void {
    Event::fake();

    $notes = 'Campaign approved - excellent impact potential';
    $command = new ApproveCampaignCommand(
        campaignId: $this->campaign->id,
        approverId: $this->superAdmin->id,
        notes: $notes
    );

    $this->handler->handle($command);

    Event::assertDispatched(CampaignApprovedEvent::class, function ($event) use ($notes) {
        return $event->campaign->id === $this->campaign->id
            && $event->approvedByUserId === $this->superAdmin->id
            && $event->notes === $notes;
    });
});
```

## Common Patterns and Anti-patterns

### ✅ Good Patterns

**1. Command-Query Separation**
```php
// Commands modify state
class CreateCampaignCommand implements CommandInterface {}

// Queries retrieve data
class FindCampaignByIdQuery implements QueryInterface {}
```

**2. Event-Driven Side Effects**
```php
// In handler: publish events after state change
event(new CampaignCreatedEvent($campaignId, $userId));

// Event listeners handle side effects
class SendCampaignNotificationListener
{
    public function handle(CampaignCreatedEvent $event): void
    {
        // Send notifications, update search index, etc.
    }
}
```

**3. Read Model Optimization**
```php
// Optimized for specific use cases
class CampaignCardReadModel extends AbstractReadModel
{
    // Only fields needed for card display
    public function getCardData(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getTitle(),
            'progress' => $this->getProgressPercentage(),
            'remaining_days' => $this->getRemainingDays(),
        ];
    }
}
```

### ❌ Anti-patterns

**1. Mixing Commands and Queries**
```php
// ❌ Bad: Command that returns data for display
class CreateAndGetCampaignCommand implements CommandInterface
{
    // Should be separate Create command + Get query
}
```

**2. Anemic Commands**
```php
// ❌ Bad: Generic update without business meaning
class UpdateCampaignCommand implements CommandInterface
{
    public function __construct(public array $data) {}
}

// ✅ Good: Specific business operations
class ApproveCampaignCommand implements CommandInterface {}
class RejectCampaignCommand implements CommandInterface {}
```

**3. Fat Handlers**
```php
// ❌ Bad: Handler doing too much
class CreateCampaignCommandHandler
{
    public function handle(CommandInterface $command): Campaign
    {
        // Create campaign
        // Send emails
        // Update analytics
        // Generate reports
        // ... (should use events)
    }
}
```

**4. Query Side Effects**
```php
// ❌ Bad: Query modifying state
class GetCampaignByIdQueryHandler
{
    public function handle(QueryInterface $query): Campaign
    {
        $campaign = $this->repository->findById($query->id);
        $campaign->incrementViewCount(); // Side effect!
        return $campaign;
    }
}
```

## Real Examples from Codebase

### Campaign Management Commands

```php
// Campaign creation workflow
CreateCampaignCommand → Draft campaign
SubmitForApprovalCommand → Pending approval
ApproveCampaignCommand → Active campaign
CompleteCampaignCommand → Completed campaign

// Campaign modification
UpdateCampaignCommand → Edit details
RejectCampaignCommand → Rejected state
DeleteCampaignCommand → Soft delete
```

### Campaign Query Examples

```php
// Single entity retrieval
FindCampaignByIdQuery → CampaignReadModel

// List operations
ListActiveCampaignsQuery → Collection<CampaignReadModel>
ListCampaignsByOrganizationQuery → Filtered collection
GetCampaignsByStatusQuery → Status-based filtering

// Search operations
SearchCampaignsQuery → Search results with pagination

// Analytics
GetCampaignStatsQuery → Aggregated statistics
GetCampaignAnalyticsQuery → Detailed analytics
GetUserCampaignStatsQuery → User-specific metrics
```

### Event-Driven Workflows

```php
// Campaign approval workflow
ApproveCampaignCommand
  → CampaignApprovedEvent
  → StatusChangedEvent
  → NotificationEvent
  → SearchIndexUpdateEvent

// Campaign creation workflow
CreateCampaignCommand
  → CampaignCreatedEvent
  → WelcomeNotificationEvent
  → AnalyticsTrackingEvent
```

### API Platform Integration

```php
// API endpoint: POST /api/campaigns
CreateCampaignProcessor
  → CreateCampaignCommand
  → CreateCampaignCommandHandler
  → CampaignCreatedEvent
  → Response: CampaignResource

// API endpoint: PUT /api/campaigns/{id}/approve
ApproveCampaignProcessor
  → ApproveCampaignCommand
  → ApproveCampaignCommandHandler
  → CampaignApprovedEvent
  → Response: CampaignResource
```

### Read Model Usage

```php
// Campaign list endpoint
ListCampaignsQuery
  → CampaignListReadModelBuilder
  → Collection<CampaignReadModel>
  → CampaignCardPresenter
  → JSON response

// Campaign details endpoint
FindCampaignByIdQuery
  → CampaignReadModel
  → CampaignDetailPresenter
  → JSON response with analytics
```

## Conclusion

The CQRS pattern in the ACME Corp CSR Platform provides:

- **Clear Separation**: Commands for writes, queries for reads
- **Scalability**: Optimized read/write models
- **Maintainability**: Single responsibility handlers
- **Testability**: Isolated, mockable components
- **Event-Driven Architecture**: Loose coupling via domain events
- **API Integration**: Clean API Platform processor pattern

This architecture supports the platform's complex business requirements while maintaining code quality, performance, and developer productivity.

---

**Developed and Maintained by [Go2digit.al](https://go2digit.al)**

Copyright 2025 Go2digit.al - All Rights Reserved