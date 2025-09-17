<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Provider;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Blade Service Provider for currency formatting directives.
 * Registers custom Blade directives for consistent currency formatting.
 */
final class BladeServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerCurrencyDirectives();
    }

    /**
     * Register custom Blade directives for currency formatting.
     */
    private function registerCurrencyDirectives(): void
    {
        // @formatCurrency($amount) - Format with current user's currency
        Blade::directive('formatCurrency', fn ($expression): string => "<?php
                \$currentCurrency = session('currency', 'EUR');
                \$currency = \Modules\Currency\Domain\Model\Currency::findByCode(\$currentCurrency);
                echo \$currency ? \$currency->formatAmount({$expression}) : '€' . number_format({$expression}, 2, ',', '.');
            ?>");

        // @currencySymbol - Display current currency symbol
        Blade::directive('currencySymbol', fn (): string => "<?php
                \$currentCurrency = session('currency', 'EUR');
                \$currency = \Modules\Currency\Domain\Model\Currency::findByCode(\$currentCurrency);
                echo \$currency ? \$currency->symbol : '€';
            ?>");

        // @currencyCode - Display current currency code
        Blade::directive('currencyCode', fn (): string => "<?php echo session('currency', 'EUR'); ?>");
    }
}
