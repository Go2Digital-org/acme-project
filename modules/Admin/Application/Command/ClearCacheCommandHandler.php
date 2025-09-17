<?php

declare(strict_types=1);

namespace Modules\Admin\Application\Command;

use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Modules\Admin\Application\Event\CacheClearedEvent;

final class ClearCacheCommandHandler
{
    private const VALID_CACHE_TYPES = [
        'config',
        'route',
        'view',
        'application',
        'queue',
        'compiled',
        'event',
    ];

    /**
     * @return array<string, array<string, bool|string|int>>
     */
    public function handle(ClearCacheCommand $command): array
    {
        $results = [];

        if ($command->clearAll) {
            $results = $this->clearAllCaches();
        } else {
            $this->validateCacheTypes($command->cacheTypes);
            $results = $this->clearSpecificCaches($command->cacheTypes);
        }

        // Dispatch domain event
        Event::dispatch(new CacheClearedEvent(
            cacheTypes: $command->clearAll ? self::VALID_CACHE_TYPES : $command->cacheTypes,
            triggeredBy: $command->triggeredBy,
            results: $results
        ));

        return $results;
    }

    /**
     * @param  array<string>  $types
     */
    private function validateCacheTypes(array $types): void
    {
        foreach ($types as $type) {
            if (! in_array($type, self::VALID_CACHE_TYPES)) {
                throw new InvalidArgumentException("Invalid cache type: {$type}");
            }
        }
    }

    /**
     * @return array<string, array<string, bool|string|int>>
     */
    private function clearAllCaches(): array
    {
        return $this->clearSpecificCaches(self::VALID_CACHE_TYPES);
    }

    /**
     * @param  array<string>  $types
     * @return array<string, array<string, bool|string|int>>
     */
    private function clearSpecificCaches(array $types): array
    {
        $results = [];

        foreach ($types as $type) {
            try {
                $results[$type] = $this->clearCacheByType($type);
            } catch (Exception $e) {
                $results[$type] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * @return array<string, bool|string|int>
     */
    private function clearCacheByType(string $type): array
    {
        return match ($type) {
            'config' => $this->runArtisanCommand('config:clear'),
            'route' => $this->runArtisanCommand('route:clear'),
            'view' => $this->runArtisanCommand('view:clear'),
            'compiled' => $this->runArtisanCommand('clear-compiled'),
            'event' => $this->runArtisanCommand('event:clear'),
            'queue' => $this->runArtisanCommand('queue:clear'),
            'application' => $this->clearApplicationCache(),
            default => throw new InvalidArgumentException("Unknown cache type: {$type}")
        };
    }

    /**
     * @return array<string, bool|string|int>
     */
    private function runArtisanCommand(string $command): array
    {
        $exitCode = Artisan::call($command);

        return [
            'success' => $exitCode === 0,
            'command' => $command,
            'output' => Artisan::output(),
            'exit_code' => $exitCode,
        ];
    }

    /**
     * @return array<string, bool|string>
     */
    private function clearApplicationCache(): array
    {
        try {
            Cache::flush();

            return [
                'success' => true,
                'message' => 'Application cache cleared successfully',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
