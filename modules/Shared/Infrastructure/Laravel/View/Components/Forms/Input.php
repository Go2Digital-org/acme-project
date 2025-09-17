<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\View\Components\Forms;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Shared\Application\ViewPresenter\FormComponentPresenter;

final class Input extends Component
{
    /** @var array<string, mixed> */
    public array $componentData;

    public function __construct(
        public string $type = 'text',
        public string $name = '',
        public ?string $id = null,
        public string $value = '',
        public string $placeholder = '',
        public bool $required = false,
        public bool $disabled = false,
        public bool $readonly = false,
        public ?string $error = null,
        public ?string $label = null,
        public ?string $hint = null,
        public ?string $icon = null,
    ) {
        $this->componentData = FormComponentPresenter::generateInputData([
            'type' => $this->type,
            'name' => $this->name,
            'id' => $this->id,
            'value' => $this->value,
            'placeholder' => $this->placeholder,
            'required' => $this->required,
            'disabled' => $this->disabled,
            'readonly' => $this->readonly,
            'error' => $this->error,
            'label' => $this->label,
            'hint' => $this->hint,
            'icon' => $this->icon,
        ]);
    }

    public function render(): View
    {
        return view('components.input');
    }
}
