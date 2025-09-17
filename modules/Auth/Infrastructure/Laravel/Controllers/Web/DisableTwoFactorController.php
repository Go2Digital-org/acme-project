<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Application\Services\TwoFactorService;
use Modules\Auth\Infrastructure\Laravel\Requests\Web\DisableTwoFactorRequest;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class DisableTwoFactorController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private TwoFactorService $twoFactorService,
    ) {}

    public function __invoke(DisableTwoFactorRequest $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        $this->twoFactorService->disableTwoFactor($user->getId());

        return response()->json([
            'message' => 'Two-factor authentication disabled successfully.',
        ]);
    }
}
