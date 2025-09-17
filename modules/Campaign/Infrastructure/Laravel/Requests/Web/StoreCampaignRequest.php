<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Organization\Domain\Model\Organization;
use Modules\User\Infrastructure\Laravel\Models\User;

final class StoreCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check(); // User must be authenticated to create campaigns
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // English translations are required
            'title.en' => ['required', 'string', 'max:255'],
            'description.en' => ['required', 'string', 'max:2000'],

            // Other language translations are optional
            'title.nl' => ['nullable', 'string', 'max:255'],
            'title.fr' => ['nullable', 'string', 'max:255'],
            'description.nl' => ['nullable', 'string', 'max:2000'],
            'description.fr' => ['nullable', 'string', 'max:2000'],

            'goal_amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'organization_id' => ['sometimes', 'integer', 'exists:organizations,id'],
            'locale' => ['sometimes', 'string', 'max:5'],

            // Additional fields from the form
            'category_id' => ['required', 'integer', 'exists:categories,id'], // Category ID is required and must exist
            'creator_note' => ['nullable', 'string', 'max:1000'],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'organization_website' => ['nullable', 'url', 'max:255'],
            'allow_anonymous_donations' => ['nullable', 'string'],
            'show_donation_comments' => ['nullable', 'string'],
            'email_notifications' => ['nullable', 'string'],
            'agree_to_terms' => ['required', 'accepted'],
            'action' => ['required', 'string', 'in:draft,submit'],
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
            'title.en.required' => __('Campaign title in English is required.'),
            'title.en.max' => __('Campaign title in English must not exceed 255 characters.'),
            'title.nl.max' => __('Campaign title in Dutch must not exceed 255 characters.'),
            'title.fr.max' => __('Campaign title in French must not exceed 255 characters.'),
            'description.en.required' => __('Campaign description in English is required.'),
            'description.en.max' => __('Campaign description in English must not exceed 2000 characters.'),
            'description.nl.max' => __('Campaign description in Dutch must not exceed 2000 characters.'),
            'description.fr.max' => __('Campaign description in French must not exceed 2000 characters.'),
            'goal_amount.required' => __('Goal amount is required.'),
            'goal_amount.numeric' => __('Goal amount must be a valid number.'),
            'goal_amount.min' => __('Goal amount must be at least $0.01.'),
            'goal_amount.max' => __('Goal amount cannot exceed $1,000,000.'),
            'start_date.required' => __('Start date is required.'),
            'start_date.date' => __('Start date must be a valid date.'),
            'start_date.after_or_equal' => __('Start date cannot be in the past.'),
            'end_date.required' => __('End date is required.'),
            'end_date.date' => __('End date must be a valid date.'),
            'end_date.after' => __('End date must be after start date.'),
            'organization_id.exists' => __('Selected organization does not exist.'),
            'category_id.required' => __('Please select a category for your campaign.'),
            'category_id.exists' => __('Please select a valid category.'),
            'agree_to_terms.accepted' => __('You must agree to the terms and conditions.'),
        ];
    }

    /**
     * Get the validated data for the request.
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        // Transform the translation arrays into the single-column format expected by HasTranslations trait
        // The form sends title[en], title[nl], etc. We need to put this directly in title/description
        $titleTranslations = [];
        $descriptionTranslations = [];

        if (isset($validated['title']) && is_array($validated['title'])) {
            foreach ($validated['title'] as $locale => $value) {
                if ($value !== null && ! in_array(trim((string) $value), ['', '0'], true)) {
                    $titleTranslations[$locale] = trim((string) $value);
                }
            }
        }

        if (isset($validated['description']) && is_array($validated['description'])) {
            foreach ($validated['description'] as $locale => $value) {
                if ($value !== null && ! in_array(trim((string) $value), ['', '0'], true)) {
                    $descriptionTranslations[$locale] = trim((string) $value);
                }
            }
        }

        // Set the translation arrays directly on title/description fields (single-column system)
        $validated['title'] = $titleTranslations === [] ? ['en' => ''] : $titleTranslations;
        $validated['description'] = $descriptionTranslations === [] ? ['en' => ''] : $descriptionTranslations;

        // Category ID is now properly validated as integer from categories table
        // The category_id is stored directly in the campaigns table

        // Convert checkbox values to boolean
        $validated['allow_anonymous_donations'] = isset($validated['allow_anonymous_donations']) && $validated['allow_anonymous_donations'] === 'on';
        $validated['show_donation_comments'] = isset($validated['show_donation_comments']) && $validated['show_donation_comments'] === 'on';
        $validated['email_notifications'] = isset($validated['email_notifications']) && $validated['email_notifications'] === 'on';

        // Ensure organization_id is set from authenticated user if not provided
        $user = auth()->user();

        if (! isset($validated['organization_id']) && $user instanceof User) {
            // For now, use ACME Corp as the default organization for all employees
            // In a real system, users would be properly linked to organizations
            $acmeOrg = Organization::where('name', 'ACME Corporation')->first();
            $validated['organization_id'] = $acmeOrg ? $acmeOrg->id : 1; // Default to ID 1 if not found
        }

        return $key === null ? $validated : data_get($validated, $key, $default);
    }
}
