<?php

declare(strict_types=1);

namespace Modules\Localization\Infrastructure\Laravel\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

final class LocaleSwitchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'in:en,nl,fr'],
        ];
    }

    public function locale(): string
    {
        return $this->string('locale', 'en')->toString();
    }
}
