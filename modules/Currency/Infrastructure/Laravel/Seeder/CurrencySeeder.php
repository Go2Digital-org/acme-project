<?php

declare(strict_types=1);

namespace Modules\Currency\Infrastructure\Laravel\Seeder;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding currencies...');

        $currencies = [
            [
                'code' => 'EUR',
                'name' => 'Euro',
                'symbol' => '€',
                'flag' => null,
                'exchange_rate' => 1.00,
                'is_active' => true,
                'is_default' => true,
                'decimal_places' => 2,
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'symbol_position' => 'before',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'USD',
                'name' => 'US Dollar',
                'symbol' => '$',
                'flag' => null,
                'exchange_rate' => 1.08,
                'is_active' => true,
                'is_default' => false,
                'decimal_places' => 2,
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'symbol_position' => 'before',
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($currencies as $currency) {
            DB::table('currencies')->updateOrInsert(
                ['code' => $currency['code']],
                $currency
            );
        }

        $this->command->info('Successfully seeded ' . count($currencies) . ' currencies');
    }
}
