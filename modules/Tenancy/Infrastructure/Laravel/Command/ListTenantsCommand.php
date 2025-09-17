<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Modules\Organization\Domain\Model\Organization;

/**
 * List Tenants Command.
 *
 * Lists all tenant organizations with their status.
 */
class ListTenantsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:list 
        {--status= : Filter by provisioning status}
        {--active : Show only active tenants}
        {--inactive : Show only inactive tenants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all tenant organizations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $query = Organization::query();

        // Apply filters
        if ($status = $this->option('status')) {
            $query->where('provisioning_status', $status);
        }

        if ($this->option('active')) {
            $query->where('is_active', true);
        }

        if ($this->option('inactive')) {
            $query->where('is_active', false);
        }

        $tenants = $query->get();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found matching the criteria.');

            return Command::SUCCESS;
        }

        $this->info("Found {$tenants->count()} tenant(s):");
        $this->newLine();

        $rows = $tenants->map(fn (Organization $tenant): array => [
            'ID' => $tenant->id,
            'Name' => $tenant->getName(),
            'Subdomain' => $tenant->subdomain ?: 'N/A',
            'Status' => $this->formatStatus($tenant->provisioning_status ?? 'unknown'),
            'Active' => $tenant->is_active ? '✅' : '❌',
            'Verified' => $tenant->is_verified ? '✅' : '❌',
            'Created' => $tenant->created_at?->format('Y-m-d H:i'),
        ])->toArray();

        $this->table(
            ['ID', 'Name', 'Subdomain', 'Status', 'Active', 'Verified', 'Created'],
            $rows
        );

        // Show summary
        $this->newLine();
        $this->info('Summary:');
        $this->info('  Total: ' . $tenants->count());
        $this->info('  Active: ' . $tenants->where('provisioning_status', 'active')->count());
        $this->info('  Pending: ' . $tenants->where('provisioning_status', 'pending')->count());
        $this->info('  Provisioning: ' . $tenants->where('provisioning_status', 'provisioning')->count());
        $this->info('  Failed: ' . $tenants->where('provisioning_status', 'failed')->count());
        $this->info('  Suspended: ' . $tenants->where('provisioning_status', 'suspended')->count());

        return Command::SUCCESS;
    }

    /**
     * Format status with color.
     */
    protected function formatStatus(string $status): string
    {
        return match ($status) {
            'active' => "<fg=green>{$status}</>",
            'pending' => "<fg=yellow>{$status}</>",
            'provisioning' => "<fg=cyan>{$status}</>",
            'failed' => "<fg=red>{$status}</>",
            'suspended' => "<fg=magenta>{$status}</>",
            default => $status,
        };
    }
}
