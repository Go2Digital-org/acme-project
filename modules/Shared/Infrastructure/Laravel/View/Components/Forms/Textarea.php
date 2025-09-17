<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\View\Components\Forms;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use Modules\Shared\Application\ViewPresenter\FormComponentPresenter;

final class Textarea extends Component
{
    /** @var array<string, mixed> */
    public array $componentData;

    public function __construct(
        public string $name = '',
        public ?string $id = null,
        public string $value = '',
        public string $placeholder = '',
        public bool $required = false,
        public bool $disabled = false,
        public bool $readonly = false,
        public int $rows = 4,
        public ?string $error = null,
        public ?string $label = null,
        public ?string $hint = null,
    ) {
        $this->componentData = FormComponentPresenter::generateTextareaData([
            'name' => $this->name,
            'id' => $this->id,
            'value' => $this->value,
            'placeholder' => $this->placeholder,
            'required' => $this->required,
            'disabled' => $this->disabled,
            'readonly' => $this->readonly,
            'rows' => $this->rows,
            'error' => $this->error,
            'label' => $this->label,
            'hint' => $this->hint,
        ]);
    }

    public function render(): View
    {
        return view('components.textarea');
    }
}
