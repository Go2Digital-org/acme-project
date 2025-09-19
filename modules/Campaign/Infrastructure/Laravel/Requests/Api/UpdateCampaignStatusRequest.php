<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Campaign\Domain\ValueObject\CampaignStatus;

final class UpdateCampaignStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $validStatuses = array_map(
            fn (CampaignStatus $status): string => $status->value,
            CampaignStatus::cases()
        );

        return [
            'status' => ['required', 'string', 'in:' . implode(',', $validStatuses)],
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
            'status.required' => 'Campaign status is required.',
            'status.string' => 'Campaign status must be a string.',
            'status.in' => 'Invalid campaign status provided.',
        ];
    }

    /**
     * Get the validated status as CampaignStatus enum.
     */
    public function getStatus(): CampaignStatus
    {
        /** @var string $status */
        $status = $this->validated()['status'];

        return CampaignStatus::from($status);
    }
}
