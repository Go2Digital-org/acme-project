<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Command;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Shared\Application\Command\CommandHandlerInterface;
use Modules\Shared\Application\Command\CommandInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

class EnableTwoFactorCommandHandler implements CommandHandlerInterface
{
    public function handle(CommandInterface $command): null
    {
        if (! $command instanceof EnableTwoFactorCommand) {
            throw new InvalidArgumentException('Invalid command type');
        }

        $user = User::findOrFail($command->userId);

        if (! $command->enable) {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
            ])->save();

            return null;
        }

        /** @var Collection<int, int> $range */
        $range = collect(range(1, 8));

        /** @var array<int, string> $recoveryCodes */
        $recoveryCodes = $range->map(fn (): string => str_replace('-', '', (string) Str::uuid()))->toArray();

        $user->forceFill([
            'two_factor_secret' => encrypt(app('pragmarx.google2fa')->generateSecretKey()),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ])->save();

        return null;
    }
}
