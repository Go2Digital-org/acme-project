<?php

declare(strict_types=1);

namespace Modules\Campaign\Application\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final class UpdateCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:2000'],
            'goal_amount' => ['sometimes', 'required', 'numeric', 'min:0.01', 'max:1000000'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after:start_date'],
            'organization_id' => ['sometimes', 'required', 'integer', 'exists:organizations,id'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Campaign title is required.',
            'title.max' => 'Campaign title must not exceed 255 characters.',
            'description.required' => 'Campaign description is required.',
            'description.max' => 'Campaign description must not exceed 2000 characters.',
            'goal_amount.required' => 'Goal amount is required.',
            'goal_amount.numeric' => 'Goal amount must be a valid number.',
            'goal_amount.min' => 'Goal amount must be at least $0.01.',
            'goal_amount.max' => 'Goal amount cannot exceed $1,000,000.',
            'start_date.required' => 'Start date is required.',
            'start_date.date' => 'Start date must be a valid date.',
            'end_date.required' => 'End date is required.',
            'end_date.date' => 'End date must be a valid date.',
            'end_date.after' => 'End date must be after start date.',
            'organization_id.required' => 'Organization selection is required.',
            'organization_id.exists' => 'Selected organization does not exist.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::validationError(
                errors: $validator->errors()->toArray(),
                message: 'Campaign update validation failed.',
            ),
        );
    }
}
