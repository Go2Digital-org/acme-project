<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Query;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;
use Modules\User\Infrastructure\Laravel\Models\User;

final readonly class GetUserProfileQueryHandler implements QueryHandlerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function handle(QueryInterface $query): array
    {
        if (! $query instanceof GetUserProfileQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        $user = User::with(['roles'])->findOrFail($query->userId);

        /** @var Collection<int, string> $roleNames */
        $roleNames = $user->roles->pluck('name');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'profile_photo_url' => $user->profile_photo_url ?? null,
            'two_factor_enabled' => $user->two_factor_secret !== null,
            'roles' => $roleNames->toArray(),
        ];
    }
}
