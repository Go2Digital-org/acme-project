# ADR-004: Repository Pattern with Interface Segregation

## Status
Accepted

## Context

The platform needs to:
- Abstract data persistence from business logic
- Support different storage mechanisms (MySQL, Redis, Elasticsearch)
- Enable testing without database dependencies
- Maintain consistency across aggregate boundaries
- Support complex queries without polluting domain models

Direct use of Eloquent in domain layer creates:
- Tight coupling to Laravel
- Difficult unit testing
- Leaky abstractions
- Performance issues with eager loading
- Inconsistent query patterns

## Decision

Implement Repository Pattern with interface segregation:

### Domain Layer: Repository Interfaces

```php
namespace Modules\Campaign\Domain\Repository;

interface CampaignRepositoryInterface {
    public function findById(CampaignId $id): ?Campaign;
    public function save(Campaign $campaign): void;
    public function remove(Campaign $campaign): void;
}

interface CampaignQueryRepositoryInterface {
    public function findActive(int $limit, int $offset): array;
    public function countByStatus(CampaignStatus $status): int;
    public function findByOrganization(OrganizationId $id): array;
}
```

### Infrastructure Layer: Implementations

```php
namespace Modules\Campaign\Infrastructure\Laravel\Repository;

final class EloquentCampaignRepository implements CampaignRepositoryInterface {
    public function findById(CampaignId $id): ?Campaign {
        $model = CampaignModel::find($id->toString());

        return $model ? $this->toDomain($model) : null;
    }

    public function save(Campaign $campaign): void {
        $model = $this->toEloquent($campaign);
        $model->save();

        // Dispatch domain events
        foreach ($campaign->pullDomainEvents() as $event) {
            event($event);
        }
    }

    private function toDomain(CampaignModel $model): Campaign {
        // Map Eloquent model to domain model
    }

    private function toEloquent(Campaign $campaign): CampaignModel {
        // Map domain model to Eloquent model
    }
}
```

### Repository Types

1. **Write Repositories**: Domain model persistence
   - Save, update, delete operations
   - Work with aggregate roots only
   - Maintain transactional boundaries
   - Dispatch domain events

2. **Query Repositories**: Optimized reads
   - Return DTOs or arrays
   - Support complex queries
   - Can use raw SQL or query builder
   - Implement caching strategies

3. **Specification Repositories**: Business rule queries
   ```php
   interface SpecificationRepository {
       public function matching(Specification $spec): array;
       public function count(Specification $spec): int;
   }
   ```

## Implementation Patterns

### Unit of Work Pattern

```php
final class UnitOfWork {
    private array $new = [];
    private array $dirty = [];
    private array $removed = [];

    public function commit(): void {
        DB::transaction(function () {
            $this->insertNew();
            $this->updateDirty();
            $this->deleteRemoved();
            $this->dispatchEvents();
        });
    }
}
```

### Repository Registration

```php
// Service Provider
public function register(): void {
    $this->app->bind(
        CampaignRepositoryInterface::class,
        EloquentCampaignRepository::class
    );

    $this->app->bind(
        CampaignQueryRepositoryInterface::class,
        CachedCampaignQueryRepository::class
    );
}
```

## Consequences

### Positive

1. **Testability**: Easy to mock repositories in tests
2. **Flexibility**: Swap storage implementations
3. **Performance**: Optimized queries for different use cases
4. **Clarity**: Clear data access patterns
5. **Caching**: Centralized cache management

### Negative

1. **Mapping Overhead**: Converting between models
2. **Duplication**: Similar methods across repositories
3. **Complexity**: Additional abstraction layer
4. **Learning Curve**: Team needs to understand patterns

### Mitigations

- Use mapping libraries or code generation
- Create base repository classes for common operations
- Document repository responsibilities clearly
- Provide repository implementation examples

## Alternatives Considered

### 1. Active Record (Direct Eloquent)
- **Pros**: Simple, built into Laravel
- **Cons**: Couples domain to persistence
- **Rejected**: Violates hexagonal architecture

### 2. Data Mapper Pattern
- **Pros**: Complete separation of concerns
- **Cons**: Complex implementation
- **Rejected**: Overkill for current needs

### 3. Query Objects
- **Pros**: Reusable query logic
- **Cons**: Can become complex
- **Rejected**: Use in combination with repositories

## Implementation Guidelines

1. **Interface Segregation**: Small, focused interfaces
2. **Aggregate Boundaries**: Repositories work with aggregate roots
3. **No Business Logic**: Repositories only handle persistence
4. **Explicit Methods**: No generic find() with criteria
5. **Transaction Management**: Handle in application layer

## Testing Strategy

```php
// Unit Test with Mock
public function test_can_create_campaign(): void {
    $repository = Mockery::mock(CampaignRepositoryInterface::class);
    $repository->shouldReceive('save')->once();

    $handler = new CreateCampaignCommandHandler($repository);
    $handler->handle(new CreateCampaignCommand(...));
}

// Integration Test with Database
public function test_can_persist_campaign(): void {
    $repository = new EloquentCampaignRepository();
    $campaign = Campaign::create(...);

    $repository->save($campaign);

    $this->assertDatabaseHas('campaigns', [...]);
}
```

## Success Metrics

1. **Test Isolation**: 100% of domain tests run without database
2. **Query Performance**: Repository queries < 50ms
3. **Cache Effectiveness**: 80% cache hit rate
4. **Code Duplication**: < 10% across repositories
5. **Developer Satisfaction**: Ease of testing and maintenance

---

**Developed and Maintained by [Go2digit.al](https://go2digit.al)**

Specialized in enterprise-grade applications with focus on scalability, security, and maintainability.

Copyright 2025 Go2digit.al - All Rights Reserved