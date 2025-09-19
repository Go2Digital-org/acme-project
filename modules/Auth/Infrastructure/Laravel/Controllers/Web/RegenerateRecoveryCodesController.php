<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Application\Services\TwoFactorService;
use Modules\Auth\Infrastructure\Laravel\Requests\Web\RegenerateRecoveryCodesRequest;
use Modules\Shared\Infrastructure\Laravel\Controllers\Traits\AuthenticatedUserTrait;

final readonly class RegenerateRecoveryCodesController
{
    use AuthenticatedUserTrait;

    public function __construct(
        private TwoFactorService $twoFactorService,
    ) {}

    public function __invoke(RegenerateRecoveryCodesRequest $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser($request);
        $password = $request->input('password', '');

        $codes = $this->twoFactorService->regenerateRecoveryCodes($user->getId(), $password);

        return response()->json([
            'codes' => $codes,
        ]);
    }
}
