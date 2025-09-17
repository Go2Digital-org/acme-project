<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\View\Components\Forms;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Shared\Application\ViewPresenter\FormComponentPresenter;

final class Select extends Component
{
    /** @var array<string, mixed> */
    public array $componentData;

    public function __construct(
        public string $name = '',
        public ?string $id = null,
        public ?string $value = null,
        public bool $required = false,
        public bool $disabled = false,
        public ?string $error = null,
        public ?string $label = null,
        public ?string $hint = null,
        public ?string $placeholder = null,
        /** @var array<string, string> */
        public array $options = [],
    ) {
        // Convert null value to empty string for consistency
        $this->value = $value ?? '';

        $this->componentData = FormComponentPresenter::generateSelectData([
            'name' => $this->name,
            'id' => $this->id,
            'value' => $this->value,
            'required' => $this->required,
            'disabled' => $this->disabled,
            'error' => $this->error,
            'label' => $this->label,
            'hint' => $this->hint,
            'placeholder' => $this->placeholder,
            'options' => $this->options,
        ]);
    }

    public function render(): View
    {
        return view('components.select');
    }
}
