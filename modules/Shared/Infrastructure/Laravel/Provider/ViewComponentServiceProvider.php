<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Provider;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Shared\Infrastructure\Laravel\View\Components\Forms\FormInput;
use Modules\Shared\Infrastructure\Laravel\View\Components\Forms\Input;
use Modules\Shared\Infrastructure\Laravel\View\Components\Forms\Select;
use Modules\Shared\Infrastructure\Laravel\View\Components\Forms\Textarea;
use Modules\Shared\Infrastructure\Laravel\View\Components\Special\LanguageSelector;
use Modules\Shared\Infrastructure\Laravel\View\Components\Special\ShareModal;
use Modules\Shared\Infrastructure\Laravel\View\Components\UI\Breadcrumbs;
use Modules\Shared\Infrastructure\Laravel\View\Components\UI\Button;
use Modules\Shared\Infrastructure\Laravel\View\Components\UI\Card;
use Modules\Shared\Infrastructure\Laravel\View\Components\UI\DonationCard;

final class ViewComponentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Form components
        Blade::component('input', Input::class);
        Blade::component('form-input', FormInput::class);
        Blade::component('textarea', Textarea::class);
        Blade::component('select', Select::class);

        // UI components
        Blade::component('breadcrumbs', Breadcrumbs::class);
        Blade::component('button', Button::class);
        Blade::component('card', Card::class);
        Blade::component('donation-card', DonationCard::class);

        // Special components
        Blade::component('language-selector', LanguageSelector::class);
        Blade::component('share-modal', ShareModal::class);
    }
}
