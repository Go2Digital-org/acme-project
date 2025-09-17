# Hexagonal Architecture CLI Commands

## Overview

The ACME Corp CSR Platform provides a comprehensive set of `hex:` artisan commands to scaffold and maintain hexagonal architecture components. These commands ensure consistent structure and accelerate development while maintaining architectural boundaries.

## Command Reference

### Interactive Menu Command

#### `hex:menu`
Interactive menu for hexagonal architecture generators that provides a user-friendly interface to all available commands.

```bash
php artisan hex:menu
```

**Features:**
- **Smart Analysis**: Analyze domain completeness and retrofit missing components
- **Domain Creation**: Create complete domains or empty directory structures
- **CQRS Components**: Add commands, events, and queries with handlers
- **Data Layer**: Add models, repositories, and migrations
- **API Layer**: Add resources, processors, and providers
- **Infrastructure**: Add form requests, service providers, factories, and seeders

**Menu Categories:**

1. **Smart Analysis**
   - `hex:analyze-domains` - Analyze Domain Completeness
   - `hex:retrofit-domain` - Retrofit Missing Components
   - `hex:complete-partial-domains` - Bulk Complete Domains

2. **Domain Creation**
   - `hex:create-domain` - Create Complete Domain (27 files)
   - `hex:add:structure` - Create Empty Directory Structure

3. **CQRS Components**
   - `add:hex:command` - Add Commands & Handlers
   - `add:hex:event` - Add Events & Handlers
   - `add:hex:find-query` - Add Queries & Handlers

4. **Data Layer**
   - `hex:add:model` - Add Domain Model
   - `hex:add:repository` - Add Repository Interface
   - `hex:add:repository-eloquent` - Add Repository Implementation
   - `hex:add:migration` - Add Database Migration

5. **API Layer**
   - `hex:add:resource` - Add API Resource
   - `hex:add:processor` - Add API Processors
   - `hex:add:provider` - Add API Providers

6. **Infrastructure**
   - `hex:add:form-request` - Add Form Requests
   - `hex:add:service-provider` - Add Service Provider
   - `hex:add:factory` - Add Model Factory
   - `hex:add:seeder` - Add Database Seeder

**Usage Example:**
```bash
$ php artisan hex:menu

╔══════════════════════════════════════════════════════════╗
║                Hexagonal Architecture Generator          ║
║                     Go²Digital DevTools                   ║
╚══════════════════════════════════════════════════════════╝

 Select a command to execute:
 > Analyze Domain Completeness
   Retrofit Missing Components
   Create Complete Domain (27 files)
   Add Commands & Handlers
   Add Domain Model
   Add API Resource
   Exit Menu
```

**Benefits:**
- **Visual Navigation**: Easy to browse all available commands
- **Contextual Grouping**: Commands organized by architectural layer
- **Quick Access**: No need to remember command names
- **Guided Workflow**: Logical progression from domain to infrastructure
- **Exit Safety**: Easy return to menu after each command

### Module Structure Commands

#### `hex:add:structure`
Creates the complete hexagonal architecture structure for a new module.

```bash
php artisan hex:add:structure Campaign

# Creates:
# modules/Campaign/
# ├── Domain/
# │   ├── Model/
# │   ├── ValueObject/
# │   ├── Repository/
# │   ├── Specification/
# │   ├── Event/
# │   └── Exception/
# ├── Application/
# │   ├── Command/
# │   ├── Query/
# │   ├── ReadModel/
# │   └── Service/
# └── Infrastructure/
#     ├── Laravel/
#     │   ├── Controllers/
#     │   ├── Models/
#     │   ├── Repository/
#     │   ├── Migration/
#     │   ├── Factory/
#     │   ├── Seeder/
#     │   └── Provider/
#     ├── ApiPlatform/
#     │   ├── Handler/
#     │   └── Resource/
#     └── Filament/
#         ├── Pages/
#         └── Resources/
```

### Domain Layer Commands

#### `hex:add:model`
Creates a domain model in the Domain layer.

```bash
php artisan hex:add:model Campaign

# Interactive prompts:
# - Select module: Campaign
# - Enter model name: Campaign

# Creates:
# modules/Campaign/Domain/Model/Campaign.php
```

Example generated model:
```php
namespace Modules\Campaign\Domain\Model;

class Campaign
{
    private string $id;
    private string $title;
    private string $description;
    private float $targetAmount;
    private \DateTimeInterface $startDate;
    private \DateTimeInterface $endDate;

    public function __construct(
        string $id,
        string $title,
        string $description,
        float $targetAmount,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->targetAmount = $targetAmount;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    // Getters and business logic methods
}
```

#### `hex:add:repository`
Creates a repository interface in the Domain layer.

```bash
php artisan hex:add:repository Campaign

# Creates:
# modules/Campaign/Domain/Repository/CampaignRepositoryInterface.php
```

Example interface:
```php
namespace Modules\Campaign\Domain\Repository;

use Modules\Campaign\Domain\Model\Campaign;

interface CampaignRepositoryInterface
{
    public function find(string $id): ?Campaign;
    public function findAll(): array;
    public function save(Campaign $campaign): void;
    public function delete(string $id): void;
}
```

### Infrastructure Layer Commands

#### `hex:add:repository-eloquent`
Creates an Eloquent implementation of a repository interface.

```bash
php artisan hex:add:repository-eloquent Campaign

# Creates:
# modules/Campaign/Infrastructure/Laravel/Repository/EloquentCampaignRepository.php
```

Example implementation:
```php
namespace Modules\Campaign\Infrastructure\Laravel\Repository;

use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Domain\Model\Campaign;
use Modules\Campaign\Infrastructure\Laravel\Models\CampaignEloquent;

class EloquentCampaignRepository implements CampaignRepositoryInterface
{
    public function find(string $id): ?Campaign
    {
        $eloquent = CampaignEloquent::find($id);
        return $eloquent ? $this->toDomain($eloquent) : null;
    }

    public function save(Campaign $campaign): void
    {
        CampaignEloquent::updateOrCreate(
            ['id' => $campaign->getId()],
            $this->toEloquent($campaign)
        );
    }

    private function toDomain(CampaignEloquent $eloquent): Campaign
    {
        // Map Eloquent model to Domain model
    }

    private function toEloquent(Campaign $domain): array
    {
        // Map Domain model to Eloquent attributes
    }
}
```

#### `hex:add:migration`
Creates a database migration for the module following hexagonal architecture principles.

```bash
php artisan hex:add:migration Campaign create_campaigns_table

# Creates:
# modules/Campaign/Infrastructure/Laravel/Migration/2024_09_16_000000_create_campaigns_table.php
```

**Example migration in hexagonal architecture:**
```php
namespace Modules\Campaign\Infrastructure\Laravel\Migration;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignsTable extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description');
            $table->decimal('goal_amount', 15, 2);
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('organization_id')->constrained('organizations');
            $table->foreignId('user_id')->constrained('users');
            $table->string('status')->index();
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->boolean('is_featured')->default(false)->index();
            $table->json('translations')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'start_date', 'end_date']);
            $table->index(['organization_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
}
```

**Running migrations in hexagonal modules:**
```bash
# Run all module migrations
php artisan migrate

# Run specific module migrations
php artisan migrate --path=modules/Campaign/Infrastructure/Laravel/Migration

# Rollback module migrations
php artisan migrate:rollback --path=modules/Campaign/Infrastructure/Laravel/Migration

# Fresh migration for module (drop and recreate)
php artisan migrate:fresh --path=modules/Campaign/Infrastructure/Laravel/Migration
```

#### `hex:add:factory`
Creates a model factory for testing.

```bash
php artisan hex:add:factory Campaign

# Creates:
# modules/Campaign/Infrastructure/Laravel/Factory/CampaignFactory.php
```

Example factory:
```php
namespace Modules\Campaign\Infrastructure\Laravel\Factory;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Campaign\Infrastructure\Laravel\Models\CampaignEloquent;

class CampaignFactory extends Factory
{
    protected $model = CampaignEloquent::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'target_amount' => $this->faker->randomFloat(2, 1000, 100000),
            'start_date' => $this->faker->dateTimeBetween('now', '+1 month'),
            'end_date' => $this->faker->dateTimeBetween('+1 month', '+6 months'),
            'status' => $this->faker->randomElement(['draft', 'active', 'completed']),
        ];
    }
}
```

#### `hex:add:seeder`
Creates a database seeder for the module.

```bash
php artisan hex:add:seeder Campaign

# Creates:
# modules/Campaign/Infrastructure/Laravel/Seeder/CampaignSeeder.php
```

### API Platform Commands

#### `hex:add:resource`
Creates an API Platform resource for REST API endpoints.

```bash
php artisan hex:add:resource Campaign

# Creates:
# modules/Campaign/Infrastructure/ApiPlatform/Resource/CampaignResource.php
```

Example resource:
```php
namespace Modules\Campaign\Infrastructure\ApiPlatform\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[ApiResource(
    operations: [
        new Get(provider: CampaignItemProvider::class),
        new GetCollection(provider: CampaignCollectionProvider::class),
        new Post(processor: CampaignCreateProcessor::class),
        new Put(processor: CampaignUpdateProcessor::class),
        new Delete(processor: CampaignDeleteProcessor::class),
    ]
)]
class CampaignResource
{
    public string $id;
    public string $title;
    public string $description;
    public float $targetAmount;
    public float $raisedAmount;
    public \DateTimeInterface $startDate;
    public \DateTimeInterface $endDate;
    public string $status;
}
```

#### `hex:add:provider`
Creates API Platform state providers for data retrieval.

```bash
php artisan hex:add:provider Campaign

# Options:
# - Collection: For list endpoints
# - Item: For single item endpoints

# Creates:
# modules/Campaign/Infrastructure/ApiPlatform/Handler/CampaignCollectionProvider.php
# modules/Campaign/Infrastructure/ApiPlatform/Handler/CampaignItemProvider.php
```

#### `hex:add:processor`
Creates API Platform state processors for data mutations.

```bash
php artisan hex:add:processor Campaign

# Options:
# - Create: POST endpoint processor
# - Delete: DELETE endpoint processor
# - Patch: PATCH endpoint processor
# - Put: PUT endpoint processor

# Creates:
# modules/Campaign/Infrastructure/ApiPlatform/Handler/CampaignCreateProcessor.php
# modules/Campaign/Infrastructure/ApiPlatform/Handler/CampaignDeleteProcessor.php
# modules/Campaign/Infrastructure/ApiPlatform/Handler/CampaignPatchProcessor.php
# modules/Campaign/Infrastructure/ApiPlatform/Handler/CampaignPutProcessor.php
```

### Laravel Integration Commands

#### `hex:add:form-request`
Creates form request validators for API endpoints.

```bash
php artisan hex:add:form-request Campaign

# Options:
# - Create: Validation for POST requests
# - Patch: Validation for PATCH requests
# - Put: Validation for PUT requests

# Creates:
# modules/Campaign/Infrastructure/Laravel/Requests/CreateCampaignRequest.php
# modules/Campaign/Infrastructure/Laravel/Requests/PatchCampaignRequest.php
# modules/Campaign/Infrastructure/Laravel/Requests/PutCampaignRequest.php
```

Example form request:
```php
namespace Modules\Campaign\Infrastructure\Laravel\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCampaignRequest extends FormRequest
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
            'target_amount' => ['required', 'numeric', 'min:0'],
            'start_date' => ['required', 'date', 'after:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'category_id' => ['required', 'exists:categories,id'],
        ];
    }
}
```

#### `hex:add:service-provider`
Creates a Laravel service provider for dependency injection.

```bash
php artisan hex:add:service-provider Campaign

# Creates:
# modules/Campaign/Infrastructure/Laravel/Provider/CampaignServiceProvider.php
```

Example service provider:
```php
namespace Modules\Campaign\Infrastructure\Laravel\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Campaign\Domain\Repository\CampaignRepositoryInterface;
use Modules\Campaign\Infrastructure\Laravel\Repository\EloquentCampaignRepository;

class CampaignServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind repository interfaces to implementations
        $this->app->bind(
            CampaignRepositoryInterface::class,
            EloquentCampaignRepository::class
        );
    }

    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Migration');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../Views', 'campaign');

        // Load translations
        $this->loadTranslationsFrom(__DIR__ . '/../Lang', 'campaign');
    }
}
```

## Usage Examples

### Creating a New Module from Scratch

```bash
# 1. Create the complete structure
php artisan hex:add:structure Payment

# 2. Create domain model
php artisan hex:add:model Payment
# Enter: Payment

# 3. Create repository interface
php artisan hex:add:repository Payment
# Enter: PaymentRepository

# 4. Create Eloquent implementation
php artisan hex:add:repository-eloquent Payment

# 5. Create migration
php artisan hex:add:migration Payment create_payments_table

# 6. Create factory for testing
php artisan hex:add:factory Payment

# 7. Create API resource
php artisan hex:add:resource Payment

# 8. Create API providers and processors
php artisan hex:add:provider Payment
php artisan hex:add:processor Payment

# 9. Create form requests
php artisan hex:add:form-request Payment

# 10. Create service provider
php artisan hex:add:service-provider Payment

# 11. Run migration
php artisan migrate
```

### Adding Components to Existing Module

```bash
# Add a new domain model to Campaign module
php artisan hex:add:model Campaign
# Enter: CampaignStatistics

# Add a new repository for the model
php artisan hex:add:repository Campaign
# Enter: CampaignStatisticsRepository

# Add the Eloquent implementation
php artisan hex:add:repository-eloquent Campaign
# Select: CampaignStatisticsRepository
```

## Best Practices

### 1. Module Naming Convention
- Use PascalCase for module names: `Campaign`, `Donation`, `User`
- Keep names singular: `User` not `Users`
- Use business domain terms: `Donation` not `Payment`

### 2. Command Execution Order
When creating a new module, follow this order:
1. `hex:add:structure` - Create base structure
2. `hex:add:model` - Define domain models
3. `hex:add:repository` - Define repository interfaces
4. `hex:add:repository-eloquent` - Implement repositories
5. `hex:add:migration` - Create database schema
6. `hex:add:factory` & `hex:add:seeder` - Testing data
7. `hex:add:resource` - API endpoints
8. `hex:add:provider` & `hex:add:processor` - API handlers
9. `hex:add:service-provider` - Wire everything together

### 3. Layer Responsibilities
- **Domain**: Pure business logic, no framework dependencies
- **Application**: Use case orchestration, command/query handlers
- **Infrastructure**: Framework-specific implementations

### 4. Testing After Generation
Always create tests after generating components:
```bash
# Create unit test for domain model
php artisan make:test Campaign/Domain/Model/CampaignTest --unit

# Create integration test for repository
php artisan make:test Campaign/Infrastructure/Repository/CampaignRepositoryTest

# Create feature test for API
php artisan make:test Campaign/Api/CampaignApiTest
```

## Configuration

### Customizing Templates
Templates for generated files are located in `stubs/hex/`. You can customize them:

```bash
# Publish stubs for customization
php artisan vendor:publish --tag=hex-stubs

# Edit templates in:
# stubs/hex/model.stub
# stubs/hex/repository.stub
# stubs/hex/controller.stub
# etc.
```

### Module Registration
Generated modules are automatically registered if you have auto-discovery enabled. Otherwise, add to `config/app.php`:

```php
'providers' => [
    // ...
    Modules\Campaign\Infrastructure\Laravel\Provider\CampaignServiceProvider::class,
],
```

## Troubleshooting

### Module Not Found
If a module isn't recognized:
```bash
# Clear and rebuild autoload
composer dump-autoload

# Clear application cache
php artisan cache:clear
php artisan config:clear
```

### Migration Not Running
If migrations aren't found:
```bash
# Check migration path in service provider
# Run migrations explicitly
php artisan migrate --path=modules/Campaign/Infrastructure/Laravel/Migration
```

### API Routes Not Working
If API routes aren't registered:
```bash
# Check service provider registration
php artisan route:list | grep campaign

# Clear route cache
php artisan route:clear
```

## Command Options Reference

Most commands support these common options:

| Option | Description | Example |
|--------|-------------|---------|
| `--force` | Overwrite existing files | `php artisan hex:add:model Campaign --force` |
| `--module` | Specify module name | `php artisan hex:add:model --module=Campaign` |
| `--name` | Specify component name | `php artisan hex:add:model --name=CampaignStats` |

## Database and Migration Best Practices in Hexagonal Architecture

### Migration Organization

In hexagonal architecture, migrations live in the Infrastructure layer because they're database-specific implementation details:

```
modules/
└── Campaign/
    └── Infrastructure/
        └── Laravel/
            └── Migration/
                ├── 2024_01_01_000001_create_campaigns_table.php
                ├── 2024_01_02_000002_add_featured_to_campaigns.php
                └── 2024_01_03_000003_create_campaign_donations_table.php
```

### Creating Complex Migrations

**Example: Multi-table migration with relationships**
```bash
php artisan hex:add:migration Invoice create_invoices_table
```

Generated migration:
```php
namespace Modules\Invoice\Infrastructure\Laravel\Migration;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    public function up(): void
    {
        // Main invoice table
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number')->unique();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('organization_id')->constrained();
            $table->string('status')->index(); // draft, sent, paid, cancelled
            $table->date('issue_date');
            $table->date('due_date');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2);
            $table->decimal('total_amount', 15, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common queries
            $table->index(['organization_id', 'status']);
            $table->index(['customer_id', 'issue_date']);
            $table->index(['status', 'due_date']);
        });

        // Invoice line items table
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total', 15, 2);
            $table->timestamps();

            $table->index('invoice_id');
        });

        // Invoice payments table
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('invoice_id')->constrained();
            $table->decimal('amount', 15, 2);
            $table->string('payment_method');
            $table->string('transaction_id')->nullable();
            $table->datetime('paid_at');
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
}
```

### Domain Model to Database Mapping

**Domain Model (Pure, no framework dependencies):**
```php
// modules/Invoice/Domain/Model/Invoice.php
namespace Modules\Invoice\Domain\Model;

use Modules\Invoice\Domain\ValueObject\InvoiceNumber;
use Modules\Invoice\Domain\ValueObject\Money;
use Modules\Invoice\Domain\ValueObject\InvoiceStatus;

class Invoice
{
    private string $id;
    private InvoiceNumber $invoiceNumber;
    private string $customerId;
    private string $organizationId;
    private InvoiceStatus $status;
    private \DateTimeImmutable $issueDate;
    private \DateTimeImmutable $dueDate;
    private array $lineItems;
    private Money $subtotal;
    private Money $taxAmount;
    private Money $total;

    public function __construct(
        string $id,
        InvoiceNumber $invoiceNumber,
        string $customerId,
        string $organizationId,
        \DateTimeImmutable $issueDate,
        \DateTimeImmutable $dueDate
    ) {
        $this->id = $id;
        $this->invoiceNumber = $invoiceNumber;
        $this->customerId = $customerId;
        $this->organizationId = $organizationId;
        $this->status = InvoiceStatus::draft();
        $this->issueDate = $issueDate;
        $this->dueDate = $dueDate;
        $this->lineItems = [];
        $this->calculateTotals();
    }

    public function addLineItem(LineItem $item): void
    {
        $this->lineItems[] = $item;
        $this->calculateTotals();
    }

    public function send(): void
    {
        if (!$this->status->isDraft()) {
            throw new \DomainException('Can only send draft invoices');
        }
        $this->status = InvoiceStatus::sent();
    }

    public function markAsPaid(Payment $payment): void
    {
        if (!$this->status->isSent()) {
            throw new \DomainException('Can only pay sent invoices');
        }
        $this->payments[] = $payment;
        if ($this->getTotalPaid()->equals($this->total)) {
            $this->status = InvoiceStatus::paid();
        }
    }

    private function calculateTotals(): void
    {
        $subtotal = 0;
        foreach ($this->lineItems as $item) {
            $subtotal += $item->getTotal();
        }
        $this->subtotal = new Money($subtotal);
        $this->taxAmount = $this->subtotal->percentage(20); // 20% VAT
        $this->total = $this->subtotal->add($this->taxAmount);
    }
}
```

**Infrastructure Model (Eloquent):**
```php
// modules/Invoice/Infrastructure/Laravel/Models/InvoiceEloquent.php
namespace Modules\Invoice\Infrastructure\Laravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceEloquent extends Model
{
    use SoftDeletes;

    protected $table = 'invoices';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'invoice_number',
        'customer_id',
        'organization_id',
        'status',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItemEloquent::class, 'invoice_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePaymentEloquent::class, 'invoice_id');
    }
}
```

**Repository Implementation (Maps between Domain and Infrastructure):**
```php
// modules/Invoice/Infrastructure/Laravel/Repository/InvoiceEloquentRepository.php
namespace Modules\Invoice\Infrastructure\Laravel\Repository;

use Modules\Invoice\Domain\Model\Invoice;
use Modules\Invoice\Domain\Repository\InvoiceRepositoryInterface;
use Modules\Invoice\Infrastructure\Laravel\Models\InvoiceEloquent;

class InvoiceEloquentRepository implements InvoiceRepositoryInterface
{
    public function findById(string $id): ?Invoice
    {
        $eloquent = InvoiceEloquent::with(['items', 'payments'])->find($id);

        if (!$eloquent) {
            return null;
        }

        return $this->toDomain($eloquent);
    }

    public function save(Invoice $invoice): void
    {
        $eloquent = InvoiceEloquent::findOrNew($invoice->getId());

        $eloquent->fill([
            'id' => $invoice->getId(),
            'invoice_number' => (string) $invoice->getInvoiceNumber(),
            'customer_id' => $invoice->getCustomerId(),
            'organization_id' => $invoice->getOrganizationId(),
            'status' => $invoice->getStatus()->getValue(),
            'issue_date' => $invoice->getIssueDate(),
            'due_date' => $invoice->getDueDate(),
            'subtotal' => $invoice->getSubtotal()->getAmount(),
            'tax_amount' => $invoice->getTaxAmount()->getAmount(),
            'total_amount' => $invoice->getTotal()->getAmount(),
        ]);

        $eloquent->save();

        // Save line items
        $this->saveLineItems($invoice, $eloquent);
    }

    private function toDomain(InvoiceEloquent $eloquent): Invoice
    {
        $invoice = new Invoice(
            $eloquent->id,
            new InvoiceNumber($eloquent->invoice_number),
            $eloquent->customer_id,
            $eloquent->organization_id,
            new \DateTimeImmutable($eloquent->issue_date),
            new \DateTimeImmutable($eloquent->due_date)
        );

        // Map line items
        foreach ($eloquent->items as $item) {
            $invoice->addLineItem($this->toLineItemDomain($item));
        }

        // Map payments
        foreach ($eloquent->payments as $payment) {
            $invoice->addPayment($this->toPaymentDomain($payment));
        }

        return $invoice;
    }
}
```

### Migration Commands for Hexagonal Architecture

```bash
# Create new migration in module
php artisan hex:add:migration Invoice create_invoices_table

# Run all migrations (including modules)
php artisan migrate

# Run only module migrations
php artisan migrate --path=modules/Invoice/Infrastructure/Laravel/Migration

# Create and run migration in one command
php artisan hex:add:migration Invoice add_currency_to_invoices && php artisan migrate

# Rollback last module migration
php artisan migrate:rollback --path=modules/Invoice/Infrastructure/Laravel/Migration

# Reset all module migrations
php artisan migrate:reset --path=modules/Invoice/Infrastructure/Laravel/Migration

# Refresh module migrations (rollback + migrate)
php artisan migrate:refresh --path=modules/Invoice/Infrastructure/Laravel/Migration

# Check migration status
php artisan migrate:status

# Create migration with specific schema
php artisan hex:add:migration Invoice create_invoice_audit_logs_table
```

### Advanced Migration Patterns

**1. Adding Indexes After Data Migration:**
```php
public function up(): void
{
    // Add new column without index
    Schema::table('invoices', function (Blueprint $table) {
        $table->string('search_index')->nullable();
    });

    // Populate data
    DB::table('invoices')->chunkById(1000, function ($invoices) {
        foreach ($invoices as $invoice) {
            DB::table('invoices')
                ->where('id', $invoice->id)
                ->update([
                    'search_index' => $this->generateSearchIndex($invoice)
                ]);
        }
    });

    // Add index after data is populated
    Schema::table('invoices', function (Blueprint $table) {
        $table->index('search_index');
    });
}
```

**2. Safe Column Renaming:**
```php
public function up(): void
{
    // Add new column
    Schema::table('invoices', function (Blueprint $table) {
        $table->decimal('total_amount', 15, 2)->nullable();
    });

    // Copy data
    DB::statement('UPDATE invoices SET total_amount = amount');

    // Make new column not nullable
    Schema::table('invoices', function (Blueprint $table) {
        $table->decimal('total_amount', 15, 2)->nullable(false)->change();
    });

    // Remove old column (in separate migration after deployment)
    // Schema::table('invoices', function (Blueprint $table) {
    //     $table->dropColumn('amount');
    // });
}
```

**3. Zero-Downtime Migrations:**
```php
// Step 1: Add nullable column
public function up(): void
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->string('payment_terms')->nullable();
    });
}

// Step 2: Backfill data (separate migration)
public function up(): void
{
    DB::table('invoices')
        ->whereNull('payment_terms')
        ->update(['payment_terms' => 'net-30']);
}

// Step 3: Make column required (after code deployment)
public function up(): void
{
    Schema::table('invoices', function (Blueprint $table) {
        $table->string('payment_terms')->nullable(false)->change();
    });
}
```

## Integration with Other Tools

### GrumPHP Integration
Generated files automatically follow quality standards:
```bash
# After generating files, run quality checks
./vendor/bin/grumphp run
```

### Rector Integration
Apply refactoring to generated code:
```bash
# Refactor generated module
./vendor/bin/rector process modules/Campaign
```

### PHPStan Integration
Validate generated code:
```bash
# Analyze generated module
./vendor/bin/phpstan analyse modules/Campaign
```

---

---

**Developed and Maintained by Go2digit.al**

Specialized in enterprise-grade applications with focus on scalability, security, and maintainability.

Copyright 2025 Go2digit.al - All Rights Reserved