<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\View\Components\UI;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Shared\Application\ViewPresenter\UIComponentPresenter;

final class Card extends Component
{
    private readonly UIComponentPresenter $presenter;

    public function __construct(
        public string $variant = 'default',
        public string $size = 'md',
        public bool $elevated = false,
        public bool $bordered = false,
        public bool $clickable = false,
        public bool $selected = false,
        public string $class = '',
    ) {
        $this->presenter = UIComponentPresenter::card(
            variant: $variant,
            size: $size,
            options: [
                'elevated' => $elevated,
                'bordered' => $bordered,
                'clickable' => $clickable,
                'selected' => $selected,
                'class' => $class,
            ],
        );
    }

    public function render(): View
    {
        return view('components.card');
    }

    public function classes(): string
    {
        return $this->presenter->getCardClasses();
    }
}
