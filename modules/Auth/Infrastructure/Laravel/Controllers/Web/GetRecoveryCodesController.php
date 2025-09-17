<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Application\Services\TwoFactorService;
use Modules\Auth\Infrastructure\Laravel\Requests\Web\GetRecoveryCodesRequest;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class GetRecoveryCodesController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private TwoFactorService $twoFactorService,
    ) {}

    public function __invoke(GetRecoveryCodesRequest $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);

        $codes = $this->twoFactorService->getRecoveryCodes($user->getId());

        return response()->json([
            'codes' => $codes,
        ]);
    }
}
