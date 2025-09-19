# ACME Corp CSR Platform - Module Dependencies & Architecture

## Overview

The ACME Corp CSR platform is built using **Hexagonal Architecture** with **Domain-Driven Design (DDD)** principles, organized into 23 specialized modules. Each module follows strict dependency rules and implements **CQRS** (Command Query Responsibility Segregation) patterns.

## Module Dependency Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              PRESENTATION LAYER                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐           │
│  │    Admin    │  │   DevTools  │  │  Dashboard  │  │    Theme    │           │
│  │  (Filament) │  │    (Dev)    │  │  (Widgets)  │  │   (UI/UX)   │           │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘           │
└─────────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              APPLICATION LAYER                                  │
│                                                                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐           │
│  │    Auth     │  │   Search    │  │  Analytics  │  │   Export    │           │
│  │ (Security)  │  │ (Discovery) │  │ (Metrics)   │  │ (Data Out)  │           │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘           │
│                                                                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐           │
│  │ Notification│  │  Bookmark   │  │ CacheWarming│  │   Import    │           │
│  │ (Alerts)    │  │ (UserPrefs) │  │ (Performance│  │ (Data In)   │           │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘           │
└─────────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                               BUSINESS LAYER                                    │
│                                                                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐           │
│  │  Campaign   │◄─┤  Donation   │  │Organization │  │    User     │           │
│  │ (Core CSR)  │  │ (Financial) │  │ (Companies) │  │ (Identity)  │           │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘           │
│                                                                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐           │
│  │   Category  │  │   Currency  │  │    Team     │  │    Audit    │           │
│  │ (Taxonomy)  │  │ (Exchange)  │  │ (Groups)    │  │ (Tracking)  │           │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘           │
└─────────────────────────────────────────────────────────────────────────────────┘
                                       │
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                            INFRASTRUCTURE LAYER                                 │
│                                                                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐           │
│  │   Tenancy   │  │Localization │  │   Shared    │  │             │           │
│  │(Multi-Tenant│  │(Translation)│  │(Foundation) │  │             │           │
│  └─────────────┘  └─────────────┘  └─────────────┘  └─────────────┘           │
└─────────────────────────────────────────────────────────────────────────────────┘

DEPENDENCY FLOW:
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│Presentation │────▶│ Application │────▶│   Business  │────▶│Infrastructure│
│   Layer     │     │    Layer    │     │    Layer    │     │    Layer    │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
```

## Module Classifications

### Foundation Modules (Infrastructure)
- **Shared**: Core interfaces, base classes, common value objects
- **Tenancy**: Multi-tenant isolation and context
- **Localization**: Translation and internationalization

### Core Business Modules
- **Campaign**: CSR campaign management (aggregate root)
- **Donation**: Financial contributions and payments
- **Organization**: Company and corporate entity management
- **User**: Employee identity and authentication
- **Category**: Campaign and content categorization
- **Currency**: Exchange rates and financial calculations
- **Team**: Group collaboration and management
- **Audit**: Activity tracking and compliance

### Application Services
- **Auth**: Authentication and authorization
- **Search**: Content discovery and filtering
- **Analytics**: Metrics aggregation and reporting
- **Notification**: User alerts and communications
- **Bookmark**: User preferences and favorites
- **CacheWarming**: Performance optimization
- **Export**: Data extraction and reporting
- **Import**: Data ingestion and migration

### Interface Modules
- **Admin**: Administrative interface (Filament)
- **Dashboard**: User dashboards and widgets
- **Theme**: UI/UX customization
- **DevTools**: Development utilities

## Detailed Module Dependencies

### Core Business Dependencies

```
Campaign Module Dependencies:
├── Shared (interfaces, events, value objects)
├── Organization (campaign ownership)
├── User (campaign creators)
├── Category (campaign classification)
├── Currency (goal amounts, conversions)
└── Audit (change tracking)

Donation Module Dependencies:
├── Shared (interfaces, events, value objects)
├── Campaign (donation targets)
├── User (donors)
├── Currency (amount calculations)
└── Audit (financial tracking)

Organization Module Dependencies:
├── Shared (interfaces, events)
├── User (organization members)
├── Tenancy (organization isolation)
└── Audit (organization changes)

User Module Dependencies:
├── Shared (interfaces, events)
├── Organization (employee relationships)
├── Tenancy (user context)
└── Audit (user activity)
```

### Application Service Dependencies

```
Analytics Module Dependencies:
├── Shared (caching, aggregation services)
├── Campaign (performance metrics)
├── Donation (financial analytics)
├── Organization (organizational metrics)
└── User (participation analytics)

Search Module Dependencies:
├── Shared (search interfaces)
├── Campaign (searchable campaigns)
├── Organization (searchable organizations)
└── User (searchable users)

Notification Module Dependencies:
├── Shared (event dispatching)
├── Campaign (campaign alerts)
├── Donation (payment confirmations)
├── Organization (org notifications)
└── User (user alerts)
```

## Database Table Ownership by Module

### Campaign Module Tables
- `campaigns` - Core campaign entities
- `bookmarks` - Campaign bookmarking (shared with Bookmark module)

### Donation Module Tables
- `donations` - Donation transactions
- `payments` - Payment processing records

### Organization Module Tables
- `organizations` - Organization entities
- `domains` - Organization domain verification

### User Module Tables
- `users` - User accounts
- `personal_access_tokens` - API authentication
- `password_reset_tokens` - Password recovery

### Currency Module Tables
- `currencies` - Supported currencies
- `exchange_rate_history` - Rate tracking
- `currency_payment_gateway` - Gateway configurations
- `payment_gateways` - Payment provider settings

### Category Module Tables
- `categories` - Content categorization

### Shared Module Tables
- `languages` - Supported languages
- `pages` - CMS content
- `social_media` - Social media configurations
- `migrations` - Schema versioning
- `application_cache` - Performance caching
- `query_cache` - Query result caching

### Auth/Permission Tables
- `roles` - User roles
- `permissions` - System permissions
- `model_has_roles` - Role assignments
- `model_has_permissions` - Permission assignments
- `role_has_permissions` - Role-permission mapping

### Infrastructure Tables
- `sessions` - User sessions
- `failed_jobs` - Failed queue jobs
- `jobs` - Queue management
- `job_batches` - Batch job tracking
- `job_progress` - Job progress tracking
- `notifications` - User notifications
- `audits` - Audit trail

### Tenancy Tables
- `tenant_login_tokens` - Multi-tenant authentication
- `tenant_user_impersonation_tokens` - User impersonation

### Export Module Tables
- `export_jobs` - Export task management
- `export_progress` - Export progress tracking

## API Platform Resource Distribution

### Campaign Module Resources
- `CampaignResource` - Primary campaign API
- `BookmarkResource` - Campaign bookmarking

### Organization Module Resources
- `OrganizationResource` - Organization management API

### Currency Module Resources
- `CurrencyResource` - Currency and exchange rate API

### Dashboard Module Resources
- `DashboardResource` - Dashboard data API
- `DashboardCacheStatusResource` - Cache status monitoring

### Auth Module Resources
- `AuthenticationResource` - Authentication endpoints

### Search Module Resources
- `SearchResource` - Unified search API

### Shared Module Resources
- `NotificationResource` - Notification delivery API

### CacheWarming Module Resources
- `CacheStatusResource` - Cache management API

## CQRS Command/Query Flow Patterns

### Command Flow (Write Operations)
```
Controller → Command → CommandHandler → Repository → Domain Model → Events
     ↓            ↓           ↓             ↓            ↓         ↓
FormRequest  → Validation → Business    → Database  → Business → Event
               Rules       Logic         Transaction   Rules     Listeners
```

### Query Flow (Read Operations)
```
Controller → Query → QueryHandler → ReadModel/Repository → Data Transfer
     ↓         ↓         ↓              ↓                    ↓
   Request → Filter → Aggregation → Optimized Query → API Resource
```

### Example Command Flows

#### Campaign Creation Flow
```
StoreCampaignController
├── CreateCampaignFormRequest (validation)
├── CreateCampaignCommand (data transfer)
├── CreateCampaignCommandHandler (business logic)
│   ├── CampaignRepositoryInterface (persistence)
│   ├── Campaign::validateDateRange() (domain rules)
│   ├── Campaign::validateGoalAmount() (domain rules)
│   └── CampaignCreatedEvent (side effects)
└── CampaignResource (response)
```

#### Donation Processing Flow
```
ProcessDonationController
├── ProcessDonationFormRequest (validation)
├── ProcessDonationCommand (data transfer)
├── ProcessDonationCommandHandler (business logic)
│   ├── DonationRepositoryInterface (persistence)
│   ├── CampaignRepositoryInterface (validation)
│   ├── Campaign::canAcceptDonation() (business rules)
│   └── DonationProcessingEvent (payment flow)
└── DonationResource (response)
```

## Event Flow Between Modules

### Domain Events and Cross-Module Communication

```
Campaign Events:
├── CampaignCreatedEvent
│   ├── → Notification Module (creator alert)
│   ├── → Analytics Module (metrics update)
│   └── → Audit Module (activity log)
├── CampaignActivatedEvent
│   ├── → Notification Module (team notifications)
│   ├── → Search Module (index update)
│   └── → CacheWarming Module (cache refresh)
├── CampaignCompletedEvent
│   ├── → Analytics Module (completion metrics)
│   ├── → Notification Module (success alerts)
│   └── → Export Module (reporting triggers)

Donation Events:
├── DonationCreatedEvent
│   ├── → Campaign Module (amount updates)
│   ├── → Notification Module (donor confirmation)
│   └── → Analytics Module (financial metrics)
├── DonationCompletedEvent
│   ├── → Campaign Module (goal tracking)
│   ├── → Organization Module (impact metrics)
│   └── → Audit Module (financial audit)

Organization Events:
├── OrganizationVerifiedEvent
│   ├── → User Module (permission updates)
│   ├── → Campaign Module (publishing rights)
│   └── → Notification Module (verification alerts)

User Events:
├── UserRegisteredEvent
│   ├── → Organization Module (membership setup)
│   ├── → Notification Module (welcome emails)
│   └── → Analytics Module (user metrics)
```

## Hexagonal Architecture Enforcement

### Dependency Rules (Enforced by Deptrac)

1. **Domain Layer** → No external dependencies
   - Pure business logic
   - Only domain concepts
   - No framework code

2. **Application Layer** → Domain Layer only
   - Use cases and orchestration
   - Command/Query handlers
   - Domain events

3. **Infrastructure Layer** → Application + Domain
   - Framework implementations
   - External service adapters
   - Database repositories

4. **Controllers** → Application Layer only
   - Single action controllers
   - No direct domain access
   - FormRequest validation

### Port and Adapter Pattern

```
Primary Adapters (Driving):
├── Laravel Controllers (HTTP)
├── API Platform Resources (REST/GraphQL)
├── Filament Admin Interface
├── Console Commands
└── Queue Jobs

Secondary Adapters (Driven):
├── Eloquent Repositories (Database)
├── Meilisearch Adapters (Search)
├── Redis Adapters (Cache)
├── Mail/SMS Adapters (Notifications)
└── Payment Gateway Adapters
```

## Guidelines for Adding New Modules

### 1. Module Creation Checklist

#### Structure Requirements
- [ ] Follow hexagonal directory structure
- [ ] Implement proper namespace conventions
- [ ] Create Domain, Application, Infrastructure layers
- [ ] Add module to bootstrap/providers.php

#### Domain Layer Requirements
- [ ] Define aggregate roots and entities
- [ ] Create value objects for domain concepts
- [ ] Implement domain services for complex logic
- [ ] Define repository interfaces
- [ ] Create domain exceptions
- [ ] Design domain events

#### Application Layer Requirements
- [ ] Implement CQRS commands and queries
- [ ] Create command/query handlers
- [ ] Define application services
- [ ] Implement event handlers
- [ ] Create DTOs for data transfer

#### Infrastructure Layer Requirements
- [ ] Implement repository adapters
- [ ] Create Laravel service providers
- [ ] Add database migrations
- [ ] Create Eloquent factories
- [ ] Implement API Platform resources
- [ ] Add single-action controllers

### 2. Dependency Guidelines

#### Allowed Dependencies
- **Any Module** → `Shared` module (foundation services)
- **Application Modules** → Core business modules (User, Organization)
- **Analytics** → All modules for metrics collection
- **Notification** → All modules for event handling
- **Search** → Searchable modules for indexing

#### Forbidden Dependencies
- **Domain modules** → Application/Infrastructure modules
- **Core business modules** → Application service modules
- **Infrastructure modules** → Other infrastructure modules (direct)
- **Circular dependencies** between any modules

### 3. Database Design Guidelines

#### Table Ownership
- Each module owns its primary entity tables
- Shared tables belong to Shared module
- Pivot tables belong to the module with primary responsibility
- Foreign keys must respect module boundaries

#### Migration Guidelines
- Place migrations in module's Infrastructure/Laravel/Migration
- Use descriptive migration names with module prefix
- Include rollback functionality
- Document schema changes in module documentation

### 4. Event Design Guidelines

#### Event Naming
- Use past tense: `CampaignCreated`, `DonationCompleted`
- Include entity name and action
- Place in Application/Event directory

#### Event Payload
- Include minimal required data
- Use value objects for complex data
- Avoid including entire entities
- Design for backward compatibility

### 5. API Design Guidelines

#### Resource Organization
- Place API Platform resources in Infrastructure/ApiPlatform/Resource
- Use descriptive resource names
- Implement proper filtering and pagination
- Follow REST principles

#### Controller Design
- Single action controllers only
- Place in Infrastructure/Laravel/Controllers
- Use FormRequest for validation
- Return appropriate HTTP status codes

### 6. Testing Requirements

#### Test Organization
- Unit tests in tests/Unit/{Module}/
- Integration tests in tests/Integration/{Module}/
- Feature tests in tests/Feature/{Module}/
- Browser tests in tests/Browser/

#### Coverage Requirements
- Minimum 90% code coverage
- Test all command handlers
- Test all query handlers
- Test domain model business rules
- Test repository implementations

### 7. Documentation Requirements

#### Module Documentation
- Create docs/modules/{module-name}.md
- Document module responsibilities
- List public interfaces
- Describe integration points
- Include usage examples

#### API Documentation
- Generate OpenAPI specifications
- Document all endpoints
- Include request/response examples
- Document error responses

## Module Maturity Levels

### Level 1: Foundation (Stable)
- **Shared**: Core infrastructure ✅
- **Tenancy**: Multi-tenant support ✅
- **Auth**: Authentication/authorization ✅
- **User**: User management ✅

### Level 2: Core Business (Stable)
- **Organization**: Company management ✅
- **Campaign**: CSR campaigns ✅
- **Donation**: Financial transactions ✅
- **Category**: Content categorization ✅
- **Currency**: Exchange rates ✅

### Level 3: Application Services (Mature)
- **Search**: Content discovery ✅
- **Analytics**: Metrics and reporting ✅
- **Notification**: Alert system ✅
- **Audit**: Activity tracking ✅

### Level 4: Interface & Tools (Evolving)
- **Admin**: Administrative interface 🔄
- **Dashboard**: User dashboards 🔄
- **Export**: Data extraction 🔄
- **Import**: Data ingestion 🔄
- **CacheWarming**: Performance optimization 🔄

### Level 5: Extensions (Development)
- **Theme**: UI customization 🚧
- **Team**: Group management 🚧
- **Bookmark**: User preferences 🚧
- **DevTools**: Development utilities 🚧
- **Localization**: Translation system 🚧

## Architecture Validation

### Automated Checks
- **Deptrac**: Dependency rule enforcement
- **PHPStan**: Static analysis for type safety
- **Pest**: Comprehensive test suite
- **GrumPHP**: Code quality gates

### Manual Reviews
- Architecture decision records (ADRs)
- Module boundary reviews
- Performance impact assessments
- Security vulnerability assessments

---

**Architecture Status**: ✅ Stable and Production Ready

**Last Updated**: September 2025

**Maintained By**: Go2digit.al Architecture Team