<?php

declare(strict_types=1);

namespace Modules\Donation\Application\Request;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final class CreateDonationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'campaign_id' => ['required', 'integer', 'exists:campaigns,id'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:50000'],
            'currency' => ['sometimes', 'string', 'in:USD,EUR,GBP'],
            'payment_method' => ['required', 'in:card,ideal,bancontact,sofort,stripe,paypal,bank_transfer,corporate_account'],
            'anonymous' => ['sometimes', 'boolean'],
            'recurring' => ['sometimes', 'boolean'],
            'recurring_frequency' => ['required_if:recurring,true', 'nullable', 'in:weekly,monthly,quarterly,yearly'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
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
            'campaign_id.required' => 'Campaign selection is required.',
            'campaign_id.exists' => 'Selected campaign does not exist.',
            'amount.required' => 'Donation amount is required.',
            'amount.numeric' => 'Donation amount must be a valid number.',
            'amount.min' => 'Minimum donation amount is $0.01.',
            'amount.max' => 'Maximum donation amount is $50,000.',
            'currency.in' => 'Currency must be USD, EUR, or GBP.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Invalid payment method selected.',
            'recurring_frequency.required_if' => 'Recurring frequency is required for recurring donations.',
            'recurring_frequency.in' => 'Invalid recurring frequency. Choose from weekly, monthly, quarterly, or yearly.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
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
                message: 'Donation validation failed.',
            ),
        );
    }
}
