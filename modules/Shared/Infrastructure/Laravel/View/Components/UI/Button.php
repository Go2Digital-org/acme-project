<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\View\Components\UI;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Shared\Application\ViewPresenter\UIComponentPresenter;

final class Button extends Component
{
    private readonly UIComponentPresenter $presenter;

    public function __construct(
        public string $variant = 'primary',
        public string $size = 'md',
        public ?string $tag = null,
        public bool $disabled = false,
        public bool $loading = false,
        public string $type = 'button',
        public string $class = '',
        public ?string $href = null,
        public bool $fullWidth = false,
    ) {
        if ($this->tag === null) {
            $this->tag = $this->href ? 'a' : 'button';
        }

        $this->presenter = UIComponentPresenter::button(
            variant: $variant,
            size: $size,
            options: [
                'disabled' => $disabled,
                'loading' => $loading,
                'type' => $type,
                'class' => $class,
                'full_width' => $fullWidth,
            ],
        );
    }

    public function render(): View
    {
        return view('components.button');
    }

    public function classes(): string
    {
        return $this->presenter->getButtonClasses();
    }

    public function showSpinner(): bool
    {
        return $this->loading;
    }
}
