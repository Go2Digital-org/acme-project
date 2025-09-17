<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDonationRequest extends FormRequest
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
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'campaign_id' => [
                'required',
                'integer',
                'exists:campaigns,id',
            ],
            'amount' => [
                'required',
                'numeric',
                'min:1',
                'max:1000000',
            ],
            'currency' => [
                'sometimes',
                'string',
                Rule::in(['EUR', 'USD', 'GBP']),
            ],
            'payment_method' => [
                'required',
                'string',
                Rule::in(['stripe', 'mollie', 'paypal', 'bank_transfer']),
            ],
            'payment_gateway' => [
                'nullable',
                'string',
                'max:50',
            ],
            'anonymous' => [
                'sometimes',
                'boolean',
            ],
            'recurring' => [
                'sometimes',
                'boolean',
            ],
            'recurring_frequency' => [
                'sometimes',
                'nullable',
                Rule::requiredIf(fn () => $this->boolean('recurring')),
                Rule::in(['weekly', 'monthly', 'quarterly', 'yearly']),
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
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
            'campaign_id.required' => 'Please select a campaign to donate to.',
            'campaign_id.exists' => 'The selected campaign does not exist.',
            'amount.required' => 'Please enter a donation amount.',
            'amount.min' => 'The donation amount must be at least €1.',
            'amount.max' => 'The donation amount cannot exceed €1,000,000.',
            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'Please select a valid payment method.',
            'recurring_frequency.required_if' => 'Please select a frequency for recurring donations.',
            'notes.max' => 'Notes cannot exceed 1,000 characters.',
        ];
    }

    /**
     * Get custom attribute names for error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'campaign_id' => 'campaign',
            'payment_method' => 'payment method',
            'recurring_frequency' => 'donation frequency',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'anonymous' => $this->boolean('anonymous'),
            'recurring' => $this->boolean('recurring'),
            'currency' => $this->get('currency', 'EUR'),
        ]);
    }
}
