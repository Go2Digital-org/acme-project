<?php

declare(strict_types=1);

namespace Modules\Tenancy\Infrastructure\Laravel\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Infrastructure\Laravel\Job\ProvisionOrganizationTenantJob;

/**
 * Create Tenant Command.
 *
 * Artisan command to create a new tenant/organization with interactive prompts.
 */
class CreateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create 
        {--name= : Organization name}
        {--subdomain= : Subdomain for the tenant}
        {--email= : Organization email}
        {--admin-name= : Admin user name}
        {--admin-email= : Admin user email}
        {--admin-password= : Admin user password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant organization with database and admin user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🏢 Creating new tenant organization...');

        // Gather organization data
        $organizationData = $this->gatherOrganizationData();

        // Gather admin data
        $adminData = $this->gatherAdminData();

        // Validate all data
        if (! $this->validateData($organizationData, $adminData)) {
            return Command::FAILURE;
        }

        // Create organization
        $this->info('Creating organization record...');

        /** @var Organization $organization */
        $organization = Organization::create([
            'name' => ['en' => $organizationData['name']],
            'subdomain' => $organizationData['subdomain'],
            'email' => $organizationData['email'],
            'status' => 'active',
            'is_active' => true,
            'is_verified' => false,
            'provisioning_status' => 'pending',
            'tenant_data' => [
                'admin' => $adminData,
            ],
        ]);

        $this->info("✅ Organization created with ID: {$organization->id}");

        // Dispatch provisioning job
        $this->info('Dispatching provisioning job...');

        ProvisionOrganizationTenantJob::dispatch($organization, $adminData)
            ->onQueue('tenant-provisioning');

        $this->info('🚀 Provisioning job dispatched!');
        $this->newLine();

        $this->table(
            ['Property', 'Value'],
            [
                ['Organization ID', $organization->id],
                ['Name', $organizationData['name']],
                ['Subdomain', $organizationData['subdomain']],
                ['URL', "https://{$organizationData['subdomain']}." . config('tenancy.central_domains.0')],
                ['Admin Email', $adminData['email']],
                ['Status', 'Provisioning...'],
            ]
        );

        $this->newLine();
        $this->info('The tenant is being provisioned. This may take a few minutes.');
        $this->info('Run "php artisan tenant:status ' . $organization->id . '" to check progress.');

        return Command::SUCCESS;
    }

    /**
     * Gather organization data.
     *
     * @return array{name: string, subdomain: string, email: string}
     */
    protected function gatherOrganizationData(): array
    {
        $name = $this->option('name') ?: $this->ask('Organization name');
        $subdomain = $this->option('subdomain') ?: $this->ask('Subdomain (e.g., "acme" for acme.example.com)');
        $email = $this->option('email') ?: $this->ask('Organization email');

        return [
            'name' => $name,
            'subdomain' => strtolower((string) $subdomain),
            'email' => strtolower((string) $email),
        ];
    }

    /**
     * Gather admin user data.
     *
     * @return array{name: string, email: string, password: string}
     */
    protected function gatherAdminData(): array
    {
        $name = $this->option('admin-name') ?: $this->ask('Admin name');
        $email = $this->option('admin-email') ?: $this->ask('Admin email');

        if ($this->option('admin-password')) {
            $password = $this->option('admin-password');
        } else {
            $password = $this->secret('Admin password (min 8 characters)');
            $passwordConfirm = $this->secret('Confirm admin password');

            if ($password !== $passwordConfirm) {
                $this->error('Passwords do not match!');

                return $this->gatherAdminData(); // Recursive retry
            }
        }

        return [
            'name' => $name,
            'email' => strtolower((string) $email),
            'password' => $password,
        ];
    }

    /**
     * Validate organization and admin data.
     *
     * @param  array{name: string, subdomain: string, email: string}  $organizationData
     * @param  array{name: string, email: string, password: string}  $adminData
     */
    protected function validateData(array $organizationData, array $adminData): bool
    {
        // Validate organization data
        $orgValidator = Validator::make($organizationData, [
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => [
                'required',
                'string',
                'min:3',
                'max:63',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                'unique:organizations,subdomain',
            ],
            'email' => ['required', 'email'],
        ]);

        if ($orgValidator->fails()) {
            $this->error('Organization validation failed:');
            foreach ($orgValidator->errors()->all() as $error) {
                $this->error("  - {$error}");
            }

            return false;
        }

        // Check for reserved subdomains
        $reserved = ['www', 'admin', 'api', 'app', 'mail', 'ftp', 'ssh'];
        if (in_array($organizationData['subdomain'], $reserved, true)) {
            $this->error("The subdomain '{$organizationData['subdomain']}' is reserved.");

            return false;
        }

        // Validate admin data
        $adminValidator = Validator::make($adminData, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($adminValidator->fails()) {
            $this->error('Admin validation failed:');
            foreach ($adminValidator->errors()->all() as $error) {
                $this->error("  - {$error}");
            }

            return false;
        }

        return true;
    }
}
