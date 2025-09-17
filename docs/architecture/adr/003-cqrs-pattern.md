# ADR-003: CQRS Pattern for Command/Query Separation

## Status
Accepted

## Context

The CSR platform has distinctly different requirements for read and write operations:

**Write Operations (Commands)**:
- Complex business validation
- Transactional consistency
- Event generation
- Audit logging
- Lower frequency (thousands per hour)

**Read Operations (Queries)**:
- Performance critical
- Different data shapes for different views
- Aggregations and calculations
- Caching opportunities
- High frequency (millions per hour)

Using the same model for both operations leads to:
- Performance bottlenecks
- Complex models trying to serve multiple purposes
- Difficulty in optimizing queries
- Coupling between write and read concerns

## Decision

Implement Command Query Responsibility Segregation (CQRS) pattern:

### Command Side (Writes)

```php
// Command: Represents intention to change state
final class CreateCampaignCommand {
    public function __construct(
        public readonly string $name,
        public readonly Money $targetAmount,
        public readonly DateTime $startDate,
        public readonly DateTime $endDate
    ) {}
}

// Command Handler: Executes business logic
final class CreateCampaignCommandHandler {
    public function handle(CreateCampaignCommand $command): Campaign {
        // Validate business rules
        // Create domain model
        // Persist through repository
        // Dispatch domain events
    }
}
```

### Query Side (Reads)

```php
// Query: Represents data request
final class GetActiveCampaignsQuery {
    public function __construct(
        public readonly ?string $departmentId,
        public readonly int $limit,
        public readonly int $offset
    ) {}
}

// Query Handler: Optimized data retrieval
final class GetActiveCampaignsQueryHandler {
    public function handle(GetActiveCampaignsQuery $query): CampaignCollection {
        // Direct database query
        // Potentially read from cache
        // Return denormalized data
    }
}
```

### Read Models

Separate optimized models for queries:

```php
// Read model optimized for list views
final class CampaignListReadModel {
    public string $id;
    public string $name;
    public string $status;
    public float $progressPercentage;
    public int $donorCount;
}

// Read model for detailed views
final class CampaignDetailReadModel {
    // Full campaign information
    // Pre-calculated statistics
    // Denormalized related data
}
```

## Implementation Structure

```
modules/Campaign/Application/
├── Command/
│   ├── CreateCampaign/
│   │   ├── CreateCampaignCommand.php
│   │   └── CreateCampaignCommandHandler.php
│   ├── ApproveCampaign/
│   └── CancelCampaign/
├── Query/
│   ├── GetActiveCampaigns/
│   │   ├── GetActiveCampaignsQuery.php
│   │   └── GetActiveCampaignsQueryHandler.php
│   ├── GetCampaignDetails/
│   └── GetCampaignStatistics/
└── ReadModel/
    ├── CampaignListReadModel.php
    └── CampaignDetailReadModel.php
```

## Consequences

### Positive

1. **Performance Optimization**: Queries can be optimized independently
2. **Scalability**: Read and write sides can scale differently
3. **Simplicity**: Each model serves a single purpose
4. **Flexibility**: Different storage for reads (cache, search engine)
5. **Team Independence**: UI teams can work with read models

### Negative

1. **Complexity**: More classes and concepts
2. **Eventual Consistency**: Read models may lag behind writes
3. **Duplication**: Similar data in different shapes
4. **Synchronization**: Need to keep read models updated

### Mitigations

- Use domain events to update read models
- Implement cache invalidation strategies
- Document consistency guarantees
- Provide clear naming conventions

## Alternatives Considered

### 1. Traditional CRUD
- **Pros**: Simple, familiar, single model
- **Cons**: Performance issues, complex models
- **Rejected**: Cannot meet performance requirements

### 2. Event Sourcing with CQRS
- **Pros**: Complete audit trail, temporal queries
- **Cons**: High complexity, storage requirements
- **Rejected**: Over-engineering for current needs

### 3. GraphQL
- **Pros**: Flexible queries, client-specified fields
- **Cons**: Complex caching, N+1 problems
- **Rejected**: Added complexity without clear benefits

## Implementation Guidelines

1. **Commands**: Always return domain model or void
2. **Queries**: Return read models or primitives
3. **Handlers**: Keep thin, delegate to services
4. **Validation**: Commands validate business rules, queries validate parameters
5. **Naming**: Use imperative for commands, descriptive for queries

## Success Metrics

1. **Query Performance**: < 100ms for list views
2. **Command Processing**: < 500ms for standard operations
3. **Cache Hit Rate**: > 80% for read operations
4. **Code Clarity**: Clear separation of concerns
5. **Test Coverage**: 100% for command handlers


---

**Developed and Maintained by [Go2digit.al](https://go2digit.al)**

Specialized in enterprise-grade applications with focus on scalability, security, and maintainability.

Copyright 2025 Go2digit.al - All Rights Reserved