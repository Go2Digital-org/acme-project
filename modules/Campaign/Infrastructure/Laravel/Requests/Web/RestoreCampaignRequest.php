<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Campaign\Application\Service\CampaignService;
use Modules\Campaign\Domain\Model\Campaign;

final class RestoreCampaignRequest extends FormRequest
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
            // No additional validation rules needed for restoration
            // Authorization is handled in the authorize() method
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
            // No custom messages needed for restoration
        ];
    }
}
