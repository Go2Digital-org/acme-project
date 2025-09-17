<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class MarkNotificationAsReadRequest extends FormRequest
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
            'id' => ['required', 'string', 'uuid'],
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
            'id' => 'notification ID',
        ];
    }

    /**
     * Get the notification ID.
     */
    public function getNotificationId(): string
    {
        return $this->validated('id');
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Include route parameter in validation
        $this->merge([
            'id' => $this->route('id'),
        ]);
    }
}
