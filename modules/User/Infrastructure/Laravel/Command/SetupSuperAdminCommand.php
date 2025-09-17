<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Command;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Modules\User\Infrastructure\Laravel\Seeder\AdminUserSeeder;

class SetupSuperAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-super-admin 
                            {--skip-seeder : Skip running the AdminUserSeeder}
                            {--skip-restore : Skip running the restore-admin command}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup super admin user by running seeder and restore privileges';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Setting up Super Admin user...');
        $this->newLine();

        $skipSeeder = $this->option('skip-seeder');
        $skipRestore = $this->option('skip-restore');

        if (! $skipSeeder) {
            $this->info('Step 1: Running AdminUserSeeder...');
            $this->line('This will create admin users if they don\'t exist.');

            try {
                Artisan::call('db:seed', [
                    '--class' => AdminUserSeeder::class,
                    '--force' => true,
                ]);

                $output = Artisan::output();
                if ($output) {
                    $this->line($output);
                }

                $this->info('✅ AdminUserSeeder completed successfully!');
            } catch (Exception $e) {
                $this->error('❌ Failed to run AdminUserSeeder: ' . $e->getMessage());

                return Command::FAILURE;
            }

            $this->newLine();
        }

        if (! $skipRestore) {
            $this->info('Step 2: Restoring super admin privileges...');
            $this->line('This ensures admin@acme.com has full super_admin role and permissions.');

            try {
                Artisan::call('app:restore-admin', [
                    '--email' => 'admin@acme.com',
                    '--role' => 'super_admin',
                ]);

                $output = Artisan::output();
                if ($output) {
                    $this->line($output);
                }

                $this->info('✅ Super admin privileges restored successfully!');
            } catch (Exception $e) {
                $this->error('❌ Failed to restore admin privileges: ' . $e->getMessage());

                return Command::FAILURE;
            }
        }

        $this->newLine();
        $this->info('🎉 Super Admin setup completed successfully!');
        $this->newLine();
        $this->table(
            ['Action', 'Status'],
            [
                ['Create admin users', $skipSeeder ? 'Skipped' : '✅ Completed'],
                ['Restore super admin privileges', $skipRestore ? 'Skipped' : '✅ Completed'],
            ]
        );

        $this->newLine();
        $this->info('Login Credentials:');
        $this->line('  URL: ' . url('/admin'));
        $this->line('  Email: admin@acme.com');
        $this->line('  Password: password');

        return Command::SUCCESS;
    }
}
