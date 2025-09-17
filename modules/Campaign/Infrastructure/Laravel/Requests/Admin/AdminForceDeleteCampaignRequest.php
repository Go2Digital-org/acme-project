<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

final class AdminForceDeleteCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        $user = auth()->user();

        // Only super_admin role can force delete campaigns
        return $user?->hasRole('super_admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirmation' => ['required', 'accepted'],
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
            'confirmation.required' => __('You must confirm this dangerous action.'),
            'confirmation.accepted' => __('You must confirm this dangerous action.'),
        ];
    }
}
