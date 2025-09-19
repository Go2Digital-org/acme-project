<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\View\Components\Forms;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Shared\Application\ViewPresenter\FormComponentPresenter;

final class FormSelect extends Component
{
    /** @var array<string, mixed> */
    public array $componentData;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(
        public string $label = '',
        public string $name = '',
        public string $value = '',
        public string $placeholder = 'Select an option',
        public bool $required = false,
        public bool $disabled = false,
        public string $error = '',
        public string $help = '',
        public ?string $icon = null,
        /** @var array<string, mixed> */
        public array $options = [],
        public bool $emptyOption = true,
    ) {
        $this->componentData = FormComponentPresenter::generateEnhancedSelectData([
            'label' => $this->label,
            'name' => $this->name,
            'value' => $this->value,
            'placeholder' => $this->placeholder,
            'required' => $this->required,
            'disabled' => $this->disabled,
            'error' => $this->error,
            'help' => $this->help,
            'icon' => $this->icon,
            'options' => $this->options,
            'emptyOption' => $this->emptyOption,
        ]);
    }

    public function render(): View
    {
        return view('components.form-select');
    }
}
