<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

final class GetRecoveryCodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }
}
