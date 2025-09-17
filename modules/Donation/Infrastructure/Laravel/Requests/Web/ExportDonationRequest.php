<?php

declare(strict_types=1);

namespace Modules\Donation\Infrastructure\Laravel\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ExportDonationRequest extends FormRequest
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
            'date_from' => [
                'nullable',
                'date',
                'before_or_equal:date_to',
            ],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
            'campaign_id' => [
                'nullable',
                'integer',
                'exists:campaigns,id',
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded']),
            ],
            'payment_method' => [
                'nullable',
                'string',
                Rule::in(['credit_card', 'bank_transfer', 'paypal', 'mollie', 'stripe']),
            ],
            'anonymous' => [
                'nullable',
                'boolean',
            ],
            'format' => [
                'nullable',
                'string',
                Rule::in(['csv']),
            ],
            'locale' => [
                'nullable',
                'string',
                Rule::in(['en', 'nl', 'fr']),
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
            'date_from.date' => 'The start date must be a valid date.',
            'date_from.before_or_equal' => 'The start date must be before or equal to the end date.',
            'date_to.date' => 'The end date must be a valid date.',
            'date_to.after_or_equal' => 'The end date must be after or equal to the start date.',
            'campaign_id.integer' => 'The campaign ID must be a valid number.',
            'campaign_id.exists' => 'The selected campaign does not exist.',
            'status.in' => 'The status must be one of: pending, processing, completed, failed, cancelled, refunded.',
            'payment_method.in' => 'The payment method must be one of: credit_card, bank_transfer, paypal, mollie, stripe.',
            'format.in' => 'The export format must be CSV.',
            'locale.in' => 'The locale must be one of: en, nl, fr.',
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
            'date_from' => 'start date',
            'date_to' => 'end date',
            'campaign_id' => 'campaign',
            'payment_method' => 'payment method',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'anonymous' => $this->has('anonymous') ? $this->boolean('anonymous') : null,
            'format' => $this->get('format', 'csv'),
            'locale' => $this->get('locale', app()->getLocale()),
        ]);
    }

    /**
     * Get the export filters with proper types.
     *
     * @return array<string, mixed>
     */
    public function getFilters(int $employeeId): array
    {
        $validated = $this->validated();

        $filters = [
            'user_id' => $employeeId,
        ];

        // Add optional filters only if they have values
        if (isset($validated['status'])) {
            $filters['status'] = $validated['status'];
        }

        if (isset($validated['campaign_id'])) {
            $filters['campaign_id'] = (int) $validated['campaign_id'];
        }

        if (isset($validated['date_from'])) {
            $filters['date_from'] = $validated['date_from'];
        }

        if (isset($validated['date_to'])) {
            $filters['date_to'] = $validated['date_to'];
        }

        if (isset($validated['payment_method'])) {
            $filters['payment_method'] = $validated['payment_method'];
        }

        if ($validated['anonymous'] !== null) {
            $filters['anonymous'] = (bool) $validated['anonymous'];
        }

        return $filters;
    }

    /**
     * Get the validated export format.
     */
    public function getExportFormat(): string
    {
        return $this->validated()['format'] ?? 'csv';
    }

    /**
     * Get the validated locale.
     */
    public function getLocale(): string
    {
        return $this->validated()['locale'] ?? app()->getLocale();
    }
}
