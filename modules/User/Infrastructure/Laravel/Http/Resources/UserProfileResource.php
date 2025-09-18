<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\Laravel\Http\Resources;

use Illuminate\Http\Request;
use Modules\Shared\Infrastructure\Laravel\Http\Resources\BaseApiResource;

class UserProfileResource extends BaseApiResource
{
    /**
     * Transform the resource into an array optimized for profile views.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $user = $this->resource;

        $data = [
            'id' => $user->getId(),
            'name' => $user->getFullName(),
            'email' => $user->getEmailString(),
        ];

        // Add profile information
        if ($this->shouldIncludeField($request, 'profile')) {
            $data['profile'] = [
                'title' => $user->title ?? null,
                'department' => $user->department ?? null,
                'avatar_url' => $user->profile_photo_url ?? null,
                'phone' => $user->phone ?? null,
                'bio' => $user->bio ?? null,
            ];
        }

        // Add account information
        if ($this->shouldIncludeField($request, 'account')) {
            $data['account'] = [
                'email_verified_at' => $this->transformDate($user->getEmailVerifiedAt(), true),
                'created_at' => $this->transformDate($user->getCreatedAt(), true),
                'updated_at' => $this->transformDate($user->getUpdatedAt(), true),
                'status' => $user->status ?? 'active',
                'locale' => $user->locale ?? 'en',
                'timezone' => $user->timezone ?? 'UTC',
            ];
        }

        // Add organization information (lazy loaded)
        if ($this->shouldIncludeField($request, 'organization')) {
            $data['organization'] = $this->whenLoadedRelation('organization', fn ($organization) => [
                'id' => $organization->id,
                'name' => $organization->getName(),
                'logo_url' => $organization->logo_url ?? null,
            ]);
        }

        // Add role information (lazy loaded)
        if ($this->shouldIncludeField($request, 'roles')) {
            $data['roles'] = $this->whenLoadedRelation('roles', fn ($roles) => $roles->map(fn ($role) => [
                'name' => $role->name,
                'display_name' => $role->display_name ?? $role->name,
            ])->toArray());
        }

        // Add donation statistics (only if specifically requested)
        if ($this->shouldIncludeRelation($request, 'donation_stats')) {
            $data['donation_stats'] = [
                'total_donated' => $this->transformMoney($user->total_donated ?? 0.0, true),
                'donations_count' => $user->donations_count ?? 0,
                'campaigns_created' => $user->campaigns_count ?? 0,
                'last_donation_date' => $this->transformDate($user->last_donation_at ?? null, true),
            ];
        }

        // Add preferences (only if specifically requested)
        if ($this->shouldIncludeRelation($request, 'preferences')) {
            $data['preferences'] = [
                'notification_email' => $user->notification_email ?? true,
                'notification_push' => $user->notification_push ?? true,
                'newsletter_subscribed' => $user->newsletter_subscribed ?? false,
                'public_profile' => $user->public_profile ?? false,
                'currency_preference' => $user->currency_preference ?? 'USD',
            ];
        }

        return $data;
    }

    /**
     * Add caching headers for profile data.
     */
    public function toResponse($request)
    {
        return parent::toResponse($request)->withHeaders([
            'Cache-Control' => 'private, max-age=600, s-maxage=0',
            'Vary' => 'Accept, Accept-Encoding, Authorization',
        ]);
    }

    /**
     * Add additional metadata for profile response.
     */
    public function with($request)
    {
        return array_merge(parent::with($request), [
            'meta' => [
                'optimized_for' => 'profile',
                'available_fields' => [
                    'profile', 'account', 'organization', 'roles',
                ],
                'available_includes' => ['donation_stats', 'preferences'],
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }
}
