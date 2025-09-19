<?php

declare(strict_types=1);

namespace Modules\Auth\Application\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\Auth\Application\Query\GetUserSessionsQuery;
use Modules\Auth\Application\Query\GetUserSessionsQueryHandler;
use Modules\User\Infrastructure\Laravel\Models\User;

final readonly class SessionManagementService
{
    public function __construct(
        private GetUserSessionsQueryHandler $getUserSessionsHandler,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getUserSessions(int $userId): array
    {
        // Validate user ID
        if ($userId <= 0) {
            throw new InvalidArgumentException('Invalid user ID provided');
        }

        // Security check: Only allow access to own sessions unless admin
        $currentUser = auth()->user();
        if (! $currentUser || ($currentUser->id !== $userId && ! $currentUser->hasRole('admin'))) {
            Log::warning('Unauthorized session access attempt', [
                'user_id' => $userId,
                'current_user_id' => $currentUser?->id,
                'ip_address' => request()->ip(),
            ]);
            throw new InvalidArgumentException('Unauthorized access to sessions');
        }

        return $this->getUserSessionsHandler->handle(
            new GetUserSessionsQuery($userId),
        );
    }

    public function deleteSession(string $sessionId, int $userId, ?string $password = null): void
    {
        // Validate inputs
        if ($sessionId === '' || $sessionId === '0' || $userId <= 0) {
            throw new InvalidArgumentException('Invalid session ID or user ID provided');
        }

        // Security check: Verify session belongs to user
        $session = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (! $session) {
            Log::warning('Attempt to delete non-existent or unauthorized session', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'ip_address' => request()->ip(),
            ]);
            throw new InvalidArgumentException('Session not found or unauthorized');
        }

        // Require password confirmation for security-critical operation
        if ($password !== null) {
            $user = User::findOrFail($userId);
            if (! Hash::check($password, $user->password)) {
                Log::warning('Session deletion attempt with invalid password', [
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                    'ip_address' => request()->ip(),
                ]);
                throw new InvalidArgumentException('Invalid password provided');
            }
        }

        /** @var Builder $queryBuilder */
        $queryBuilder = DB::table('sessions');
        $deleted = $queryBuilder->where('id', $sessionId)->delete();

        if ($deleted) {
            Log::info('Session deleted', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'ip_address' => request()->ip(),
            ]);
        }
    }

    public function deleteOtherSessions(int $userId, string $currentSessionId, string $password): void
    {
        // Validate inputs
        if ($userId <= 0 || ($currentSessionId === '' || $currentSessionId === '0')) {
            throw new InvalidArgumentException('Invalid user ID or current session ID provided');
        }

        $user = User::findOrFail($userId);

        // Require password confirmation for security-critical operation
        if (! Hash::check($password, $user->password)) {
            Log::warning('Other sessions deletion attempt with invalid password', [
                'user_id' => $userId,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            throw new InvalidArgumentException('Invalid password provided');
        }

        /** @var Builder $queryBuilder */
        $queryBuilder = DB::table('sessions');
        $deleted = $queryBuilder
            ->where('user_id', $userId)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        Log::info('Other sessions deleted', [
            'user_id' => $userId,
            'current_session_id' => $currentSessionId,
            'sessions_deleted' => $deleted,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public function validateSessionSecurity(string $sessionId, int $userId): bool
    {
        // Check if session exists and belongs to user
        $session = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $userId)
            ->first();

        if (! $session) {
            return false;
        }

        // Validate session hasn't expired
        $lastActivity = $session->last_activity ?? 0;
        $sessionLifetime = config('session.lifetime', 120) * 60; // Convert minutes to seconds

        if (time() - $lastActivity > $sessionLifetime) {
            // Session expired, delete it
            DB::table('sessions')->where('id', $sessionId)->delete();

            Log::info('Expired session deleted', [
                'session_id' => $sessionId,
                'user_id' => $userId,
                'last_activity' => $lastActivity,
            ]);

            return false;
        }

        return true;
    }
}
