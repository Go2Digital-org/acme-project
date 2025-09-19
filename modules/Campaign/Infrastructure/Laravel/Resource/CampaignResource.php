<?php

declare(strict_types=1);

namespace Modules\Campaign\Infrastructure\Laravel\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Campaign\Domain\Model\Campaign;

/**
 * @mixin Campaign
 */
final class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'goal_amount' => $this->goal_amount,
            'current_amount' => $this->current_amount,
            'progress_percentage' => $this->getProgressPercentage(),
            'remaining_amount' => $this->getRemainingAmount(),
            'start_date' => $this->start_date?->toISOString(),
            'end_date' => $this->end_date?->toISOString(),
            'status' => $this->status->value,
            'organization_id' => $this->organization_id,
            'user_id' => $this->user_id,
            'completed_at' => $this->completed_at?->toISOString(),
            'is_active' => $this->isActive(),
            'can_accept_donation' => $this->canAcceptDonation(),
            'days_remaining' => $this->getDaysRemaining(),
            'has_reached_goal' => $this->hasReachedGoal(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Include relationships when loaded
            'organization' => $this->whenLoaded('organization'),
            'employee' => $this->whenLoaded('employee', function (): ?array {
                if (! $this->employee) {
                    return null;
                }

                return [
                    'id' => $this->employee->getId(),
                    'name' => $this->employee->getName(),
                    'email' => $this->employee->getEmail(),
                ];
            }),
        ];
    }
}
