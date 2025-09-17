<?php

declare(strict_types=1);

namespace Modules\Search\Domain\Exception;

use Exception;

class SearchException extends Exception
{
    public static function searchFailed(string $query, string $reason): self
    {
        return new self(sprintf(
            'Search failed for query "%s": %s',
            $query,
            $reason,
        ));
    }

    public static function indexingFailed(string $indexName, string $reason): self
    {
        return new self(sprintf(
            'Indexing failed for index "%s": %s',
            $indexName,
            $reason,
        ));
    }

    public static function indexNotFound(string $indexName): self
    {
        return new self(sprintf(
            'Index "%s" not found',
            $indexName,
        ));
    }

    public static function indexCreationFailed(string $indexName, string $reason): self
    {
        return new self(sprintf(
            'Failed to create index "%s": %s',
            $indexName,
            $reason,
        ));
    }

    public static function invalidConfiguration(string $indexName, string $reason): self
    {
        return new self(sprintf(
            'Invalid configuration for index "%s": %s',
            $indexName,
            $reason,
        ));
    }

    public static function searchEngineUnavailable(string $reason): self
    {
        return new self(sprintf(
            'Search engine is unavailable: %s',
            $reason,
        ));
    }

    public static function bulkIndexingFailed(string $indexName, int $failedCount, string $reason): self
    {
        return new self(sprintf(
            'Bulk indexing failed for index "%s". %d documents failed: %s',
            $indexName,
            $failedCount,
            $reason,
        ));
    }

    public static function cacheOperationFailed(string $operation, string $reason): self
    {
        return new self(sprintf(
            'Cache operation "%s" failed: %s',
            $operation,
            $reason,
        ));
    }
}
