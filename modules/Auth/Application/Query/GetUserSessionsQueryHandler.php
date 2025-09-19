<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Query;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Jenssegers\Agent\Agent;
use Modules\Shared\Application\Query\QueryHandlerInterface;
use Modules\Shared\Application\Query\QueryInterface;
use stdClass;

final readonly class GetUserSessionsQueryHandler implements QueryHandlerInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(QueryInterface $query): array
    {
        if (! $query instanceof GetUserSessionsQuery) {
            throw new InvalidArgumentException('Invalid query type');
        }

        /** @var Builder $queryBuilder */
        $queryBuilder = DB::table('sessions');

        /** @var Collection<int, stdClass> $sessions */
        $sessions = $queryBuilder
            ->where('user_id', $query->userId)
            ->orderBy('last_activity', 'desc')
            ->get();

        $agent = new Agent;
        $sessionStore = session();
        $currentSessionId = $sessionStore->getId();

        return $sessions->map(function (stdClass $session) use ($agent, $currentSessionId): array {
            $sessionId = (string) $session->id;
            $ipAddress = (string) $session->ip_address;
            $userAgent = (string) ($session->user_agent ?? '');
            $lastActivity = (int) $session->last_activity;

            $agent->setUserAgent($userAgent);

            $platform = $agent->platform();
            $browser = $agent->browser();

            return [
                'id' => $sessionId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'last_activity' => Carbon::createFromTimestamp($lastActivity),
                'is_current' => $sessionId === $currentSessionId,
                'device' => [
                    'platform' => $platform,
                    'platform_version' => $platform && is_string($platform) ? $agent->version($platform) : null,
                    'browser' => $browser,
                    'browser_version' => $browser && is_string($browser) ? $agent->version($browser) : null,
                    'is_desktop' => $agent->isDesktop(),
                    'is_mobile' => $agent->isMobile(),
                    'is_tablet' => $agent->isTablet(),
                ],
            ];
        })->toArray();
    }
}
