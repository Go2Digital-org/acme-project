<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

final class CancelDonationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check(); // Must be authenticated
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
            'reason' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    /**
     * @return array<string, mixed>
     */
    public function messages(): array
    {
        return [
            'reason.max' => 'The cancellation reason cannot exceed 500 characters.',
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    /**
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'reason' => 'cancellation reason',
        ];
    }
}
