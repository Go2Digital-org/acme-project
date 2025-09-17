<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\View\Components\Forms;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Shared\Application\ViewPresenter\FormComponentPresenter;

final class FormInput extends Component
{
    /** @var array<string, mixed> */
    public array $componentData;

    public function __construct(
        public string $label = '',
        public string $name = '',
        public string $type = 'text',
        public string $value = '',
        public string $placeholder = '',
        public bool $required = false,
        public bool $disabled = false,
        public bool $readonly = false,
        public string $error = '',
        public string $help = '',
        public ?string $icon = null,
        public ?string $suffix = null,
        public ?string $prefix = null,
        public ?string $min = null,
        public ?string $max = null,
        public ?string $step = null,
        public ?string $autocomplete = null,
    ) {
        $this->componentData = FormComponentPresenter::generateEnhancedInputData([
            'label' => $this->label,
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
            'placeholder' => $this->placeholder,
            'required' => $this->required,
            'disabled' => $this->disabled,
            'readonly' => $this->readonly,
            'error' => $this->error,
            'help' => $this->help,
            'icon' => $this->icon,
            'suffix' => $this->suffix,
            'prefix' => $this->prefix,
            'min' => $this->min,
            'max' => $this->max,
            'step' => $this->step,
            'autocomplete' => $this->autocomplete,
        ]);
    }

    public function render(): View
    {
        return view('components.form-input');
    }
}
