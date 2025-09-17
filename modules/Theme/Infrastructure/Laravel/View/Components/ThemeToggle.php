<?php

declare(strict_types=1);

namespace Modules\Theme\Infrastructure\Laravel\View\Components;

use Illuminate\View\Component;

class ThemeToggle extends Component
{
    public function __construct(
        public string $variant = 'default',
        public bool $showLabel = true,
    ) {}

    public function render()
    {
        return view('components.theme-toggle');
    }
}
