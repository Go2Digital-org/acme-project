<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Campaign\Application\Service\CampaignService;
use Modules\Campaign\Domain\Model\Campaign;

final class UpdateCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        /** @var Campaign|null $campaign */
        $campaign = $this->route('campaign');

        if (! $campaign) {
            return false;
        }

        // Use the CampaignService to check authorization
        return app(CampaignService::class)->canManageCampaign($campaign->id, auth()->user());
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
            // English translations are required
            'title.en' => ['required', 'string', 'max:255'],
            'description.en' => ['required', 'string', 'max:2000'],

            // Other language translations are optional
            'title.nl' => ['nullable', 'string', 'max:255'],
            'title.fr' => ['nullable', 'string', 'max:255'],
            'description.nl' => ['nullable', 'string', 'max:2000'],
            'description.fr' => ['nullable', 'string', 'max:2000'],

            'goal_amount' => ['required', 'numeric', 'min:0.01', 'max:1000000'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'locale' => ['sometimes', 'string', 'max:5'],
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
            'end_date.required' => __('End date is required.'),
            'end_date.date' => __('End date must be a valid date.'),
            'end_date.after' => __('End date must be after start date.'),
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
        return [
            'goal_amount' => __('goal amount'),
            'start_date' => __('start date'),
            'end_date' => __('end date'),
        ];
    }

    /**
     * Get the validated data for the request.
     */
    /**
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

        return $key === null ? $validated : data_get($validated, $key, $default);
    }
}
