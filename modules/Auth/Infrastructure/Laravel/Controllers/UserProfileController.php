<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;
use Modules\User\Infrastructure\Laravel\Http\Resources\UserProfileResource;

final class UserProfileController
{
    use AuthenticatedUserTrait;

    /**
     * Get the authenticated user's profile information with optimized field selection.
     */
    public function __invoke(Request $request): JsonResponse
    {
        // Validate request parameters for field selection
        $validated = $request->validate([
            'fields' => 'array',
            'fields.*' => 'string|in:profile,account,organization,roles',
            'include' => 'string|in:donation_stats,preferences',
        ]);

        $user = $this->getAuthenticatedUser($request);

        // Optimize relationship loading based on requested fields/includes
        $relationships = [];
        $requestedFields = $validated['fields'] ?? ['profile', 'account'];
        $requestedIncludes = $validated['include'] ? explode(',', (string) $validated['include']) : [];

        if (in_array('organization', $requestedFields)) {
            $relationships[] = 'organization:id,name,logo_url';
        }

        if (in_array('roles', $requestedFields)) {
            $relationships[] = 'roles:id,name,display_name';
        }

        // Load donation stats only if specifically requested
        if (in_array('donation_stats', $requestedIncludes)) {
            $relationships[] = 'donations:id,amount,created_at';
            $relationships[] = 'campaigns:id,title,current_amount';
        }

        // Eager load relationships to prevent N+1 queries
        if ($relationships !== []) {
            $user->load($relationships);
        }

        // Use optimized resource transformation
        $userResource = new UserProfileResource($user);

        $headers = [
            'Cache-Control' => 'private, max-age=600, s-maxage=0', // 10 minutes for private user data
            'Vary' => 'Accept, Accept-Encoding, Authorization',
            'X-User-ID' => (string) $user->getId(),
            'X-Profile-Version' => md5($user->getUpdatedAt() instanceof Carbon ? $user->getUpdatedAt()->format('c') : ''),
        ];

        return ApiResponse::success(
            data: $userResource->toArray($request),
            message: 'User profile retrieved successfully',
            headers: $headers
        );
    }
}
