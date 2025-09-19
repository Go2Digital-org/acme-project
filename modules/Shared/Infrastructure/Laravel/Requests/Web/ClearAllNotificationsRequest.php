<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class ClearAllNotificationsRequest extends FormRequest
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
     */
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // No specific validation rules for clearing all notifications
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [];
    }
}
