<?php

declare(strict_types=1);

namespace Modules\Export\Infrastructure\Laravel\Requests;

use Carbon\Carbon;
use DB;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;

final class RequestExportRequest extends FormRequest
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
        $maxDateFrom = Carbon::now()->subYears(2)->format('Y-m-d');
        $maxDateTo = Carbon::now()->format('Y-m-d');

        return [
            'format' => ['required', 'in:csv,excel'],
            'date_range_from' => [
                'nullable',
                'date',
                'after_or_equal:' . $maxDateFrom,
                'before_or_equal:date_range_to',
            ],
            'date_range_to' => [
                'nullable',
                'date',
                'after_or_equal:date_range_from',
                'before_or_equal:' . $maxDateTo,
            ],
            'campaign_ids' => ['nullable', 'array'],
            'campaign_ids.*' => ['integer', 'exists:campaigns,id'],
            'include_anonymous' => ['nullable', 'boolean'],
            'include_recurring' => ['nullable', 'boolean'],
            'filters' => ['nullable', 'array'],
            'filters.status' => ['nullable', 'array'],
            'filters.status.*' => ['in:pending,processing,completed,failed,cancelled,refunded'],
            'filters.payment_method' => ['nullable', 'array'],
            'filters.payment_method.*' => ['in:credit_card,bank_transfer,paypal,stripe,mollie'],
            'filters.min_amount' => ['nullable', 'numeric', 'min:0'],
            'filters.max_amount' => ['nullable', 'numeric', 'min:0', 'gte:filters.min_amount'],
            'filters.currency' => ['nullable', 'in:USD,EUR,GBP'],
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
            'format.required' => 'Export format is required.',
            'format.in' => 'Export format must be either CSV or Excel.',
            'date_range_from.after_or_equal' => 'Date range cannot exceed 2 years in the past.',
            'date_range_from.before_or_equal' => 'Start date must be before or equal to end date.',
            'date_range_to.after_or_equal' => 'End date must be after or equal to start date.',
            'date_range_to.before_or_equal' => 'End date cannot be in the future.',
            'campaign_ids.array' => 'Campaign IDs must be provided as an array.',
            'campaign_ids.*.integer' => 'Each campaign ID must be a valid integer.',
            'campaign_ids.*.exists' => 'One or more selected campaigns do not exist.',
            'include_anonymous.boolean' => 'Include anonymous flag must be true or false.',
            'include_recurring.boolean' => 'Include recurring flag must be true or false.',
            'filters.array' => 'Filters must be provided as an object.',
            'filters.status.array' => 'Status filter must be an array.',
            'filters.status.*.in' => 'Invalid donation status provided in filters.',
            'filters.payment_method.array' => 'Payment method filter must be an array.',
            'filters.payment_method.*.in' => 'Invalid payment method provided in filters.',
            'filters.min_amount.numeric' => 'Minimum amount must be a valid number.',
            'filters.min_amount.min' => 'Minimum amount cannot be negative.',
            'filters.max_amount.numeric' => 'Maximum amount must be a valid number.',
            'filters.max_amount.min' => 'Maximum amount cannot be negative.',
            'filters.max_amount.gte' => 'Maximum amount must be greater than or equal to minimum amount.',
            'filters.currency.in' => 'Currency must be one of: USD, EUR, GBP.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateDateRange($validator);
            $this->validateCampaignAccess($validator);
        });
    }

    /**
     * Validate that date range does not exceed 2 years.
     */
    private function validateDateRange(Validator $validator): void
    {
        $dateFrom = $this->input('date_range_from');
        $dateTo = $this->input('date_range_to');

        if ($dateFrom && $dateTo) {
            $from = Carbon::parse($dateFrom);
            $to = Carbon::parse($dateTo);

            // Check if range exceeds 2 years
            if ($to->diffInDays($from) > 730) { // 2 years = ~730 days
                $validator->errors()->add(
                    'date_range_to',
                    'Date range cannot exceed 2 years.'
                );
            }
        }
    }

    /**
     * Validate that user has access to selected campaigns.
     */
    private function validateCampaignAccess(Validator $validator): void
    {
        $campaignIds = $this->input('campaign_ids', []);

        if (! empty($campaignIds)) {
            $user = $this->user();

            if (! $user) {
                $validator->errors()->add(
                    'campaign_ids',
                    'User must be authenticated to export campaigns.'
                );

                return;
            }

            // Check if user belongs to organization that owns these campaigns
            $campaignCount = DB::table('campaigns')
                ->whereIn('id', $campaignIds)
                ->where('organization_id', $user->organization_id)
                ->count();

            if ($campaignCount !== count($campaignIds)) {
                $validator->errors()->add(
                    'campaign_ids',
                    'You do not have access to one or more selected campaigns.'
                );
            }
        }
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
                message: 'Export request validation failed.',
            ),
        );
    }
}
