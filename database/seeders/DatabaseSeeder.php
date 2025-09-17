<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Shared\Infrastructure\Laravel\Seeder\DatabaseSeeder as HexDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     * Delegates to hexagonal architecture database seeder.
     */
    public function run(): void
    {
        $this->call(HexDatabaseSeeder::class);
    }
}
