<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class ListNotificationsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'per_page' => 'items per page',
            'page' => 'page number',
        ];
    }

    /**
     * Get the per page value with default.
     */
    public function getPerPage(): int
    {
        return (int) $this->validated('per_page', 20);
    }

    /**
     * Get the page number with default.
     */
    public function getPage(): int
    {
        return (int) $this->validated('page', 1);
    }
}
