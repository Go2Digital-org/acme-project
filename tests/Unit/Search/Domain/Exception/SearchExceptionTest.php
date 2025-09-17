<?php

declare(strict_types=1);

use Modules\Search\Domain\Exception\SearchException;

describe('SearchException', function () {
    it('creates search failed exception with query and reason', function () {
        $exception = SearchException::searchFailed('environmental campaigns', 'Connection timeout');

        expect($exception)->toBeInstanceOf(SearchException::class)
            ->and($exception->getMessage())->toBe('Search failed for query "environmental campaigns": Connection timeout')
            ->and($exception->getCode())->toBe(0);
    });

    it('creates search failed exception with empty query', function () {
        $exception = SearchException::searchFailed('', 'Invalid parameters');

        expect($exception->getMessage())->toBe('Search failed for query "": Invalid parameters');
    });

    it('creates search failed exception with special characters in query', function () {
        $exception = SearchException::searchFailed('test "quoted" AND (group)', 'Syntax error');

        expect($exception->getMessage())->toBe('Search failed for query "test "quoted" AND (group)": Syntax error');
    });

    it('creates indexing failed exception with index name and reason', function () {
        $exception = SearchException::indexingFailed('campaigns', 'Document format invalid');

        expect($exception)->toBeInstanceOf(SearchException::class)
            ->and($exception->getMessage())->toBe('Indexing failed for index "campaigns": Document format invalid')
            ->and($exception->getCode())->toBe(0);
    });

    it('creates indexing failed exception with complex index name', function () {
        $exception = SearchException::indexingFailed('campaigns_2024_01', 'Memory limit exceeded');

        expect($exception->getMessage())->toBe('Indexing failed for index "campaigns_2024_01": Memory limit exceeded');
    });

    it('creates index not found exception', function () {
        $exception = SearchException::indexNotFound('donations');

        expect($exception)->toBeInstanceOf(SearchException::class)
            ->and($exception->getMessage())->toBe('Index "donations" not found')
            ->and($exception->getCode())->toBe(0);
    });

    it('creates index not found exception with namespaced index', function () {
        $exception = SearchException::indexNotFound('tenant_123.campaigns');

        expect($exception->getMessage())->toBe('Index "tenant_123.campaigns" not found');
    });

    it('creates index creation failed exception', function () {
        $exception = SearchException::indexCreationFailed('organizations', 'Insufficient permissions');

        expect($exception)->toBeInstanceOf(SearchException::class)
            ->and($exception->getMessage())->toBe('Failed to create index "organizations": Insufficient permissions')
            ->and($exception->getCode())->toBe(0);
    });

    it('creates index creation failed exception with detailed reason', function () {
        $exception = SearchException::indexCreationFailed(
            'user_profiles',
            'Index already exists with different schema'
        );

        expect($exception->getMessage())->toBe('Failed to create index "user_profiles": Index already exists with different schema');
    });

    it('creates invalid configuration exception', function () {
        $exception = SearchException::invalidConfiguration('campaigns', 'Missing searchable attributes');

        expect($exception)->toBeInstanceOf(SearchException::class)
            ->and($exception->getMessage())->toBe('Invalid configuration for index "campaigns": Missing searchable attributes')
            ->and($exception->getCode())->toBe(0);
    });

    it('creates invalid configuration exception with multiple errors', function () {
        $exception = SearchException::invalidConfiguration(
            'donations',
            'Invalid ranking rules: ["invalid_rule"], Missing primary key'
        );

        expect($exception->getMessage())->toBe('Invalid configuration for index "donations": Invalid ranking rules: ["invalid_rule"], Missing primary key');
    });

    it('creates search engine unavailable exception', function () {
        $exception = SearchException::searchEngineUnavailable('Service is down for maintenance');

        expect($exception)->toBeInstanceOf(SearchException::class)
            ->and($exception->getMessage())->toBe('Search engine is unavailable: Service is down for maintenance')
            ->and($exception->getCode())->toBe(0);
    });

    it('creates search engine unavailable exception with connection details', function () {
        $exception = SearchException::searchEngineUnavailable('Cannot connect to localhost:7700 - Connection refused');

        expect($exception->getMessage())->toBe('Search engine is unavailable: Cannot connect to localhost:7700 - Connection refused');
    });

    it('creates bulk indexing failed exception', function () {
        $exception = SearchException::bulkIndexingFailed('campaigns', 15, 'Validation errors in documents');

        expect($exception)->toBeInstanceOf(SearchException::class)
            ->and($exception->getMessage())->toBe('Bulk indexing failed for index "campaigns". 15 documents failed: Validation errors in documents')
            ->and($exception->getCode())->toBe(0);
    });

    it('creates bulk indexing failed exception with zero failures', function () {
        $exception = SearchException::bulkIndexingFailed('donations', 0, 'Unexpected error during processing');

        expect($exception->getMessage())->toBe('Bulk indexing failed for index "donations". 0 documents failed: Unexpected error during processing');
    });

    it('creates bulk indexing failed exception with large failure count', function () {
        $exception = SearchException::bulkIndexingFailed('organizations', 10000, 'Rate limit exceeded');

        expect($exception->getMessage())->toBe('Bulk indexing failed for index "organizations". 10000 documents failed: Rate limit exceeded');
    });

    it('creates cache operation failed exception', function () {
        $exception = SearchException::cacheOperationFailed('SET', 'Redis connection lost');

        expect($exception)->toBeInstanceOf(SearchException::class)
            ->and($exception->getMessage())->toBe('Cache operation "SET" failed: Redis connection lost')
            ->and($exception->getCode())->toBe(0);
    });

    it('creates cache operation failed exception for different operations', function () {
        $getException = SearchException::cacheOperationFailed('GET', 'Key not found');
        $deleteException = SearchException::cacheOperationFailed('DELETE', 'Permission denied');
        $flushException = SearchException::cacheOperationFailed('FLUSH', 'Operation timeout');

        expect($getException->getMessage())->toBe('Cache operation "GET" failed: Key not found')
            ->and($deleteException->getMessage())->toBe('Cache operation "DELETE" failed: Permission denied')
            ->and($flushException->getMessage())->toBe('Cache operation "FLUSH" failed: Operation timeout');
    });

    it('inherits from base Exception class', function () {
        $exception = SearchException::searchFailed('test', 'error');

        expect($exception)->toBeInstanceOf(Exception::class)
            ->and($exception)->toBeInstanceOf(SearchException::class);
    });

    it('can be thrown and caught', function () {
        expect(function () {
            throw SearchException::indexNotFound('test_index');
        })->toThrow(SearchException::class, 'Index "test_index" not found');
    });

    it('can be caught as base Exception', function () {
        expect(function () {
            throw SearchException::searchEngineUnavailable('Service down');
        })->toThrow(Exception::class);
    });

    it('maintains exception hierarchy', function () {
        $exception = SearchException::invalidConfiguration('test', 'error');

        expect($exception instanceof Exception)->toBeTrue()
            ->and($exception instanceof SearchException)->toBeTrue()
            ->and(get_parent_class($exception))->toBe(Exception::class);
    });

    it('handles empty strings in exception messages', function () {
        $searchException = SearchException::searchFailed('', '');
        $indexException = SearchException::indexNotFound('');
        $configException = SearchException::invalidConfiguration('', '');

        expect($searchException->getMessage())->toBe('Search failed for query "": ')
            ->and($indexException->getMessage())->toBe('Index "" not found')
            ->and($configException->getMessage())->toBe('Invalid configuration for index "": ');
    });

    it('handles special characters in exception parameters', function () {
        $searchException = SearchException::searchFailed('test & search', 'Error: invalid <xml>');
        $indexException = SearchException::indexNotFound('index-with-hyphens_and_underscores');
        $configException = SearchException::invalidConfiguration('test.index', 'Error with "quotes" and \'apostrophes\'');

        expect($searchException->getMessage())->toContain('test & search')
            ->and($searchException->getMessage())->toContain('Error: invalid <xml>')
            ->and($indexException->getMessage())->toContain('index-with-hyphens_and_underscores')
            ->and($configException->getMessage())->toContain('Error with "quotes" and \'apostrophes\'');
    });

    it('handles unicode characters in exception messages', function () {
        $searchException = SearchException::searchFailed('café résumé', 'Erreur de connexion');
        $indexException = SearchException::indexNotFound('индекс');
        $configException = SearchException::invalidConfiguration('索引', '配置错误');

        expect($searchException->getMessage())->toContain('café résumé')
            ->and($searchException->getMessage())->toContain('Erreur de connexion')
            ->and($indexException->getMessage())->toContain('индекс')
            ->and($configException->getMessage())->toContain('索引')
            ->and($configException->getMessage())->toContain('配置错误');
    });

    it('creates exceptions with consistent formatting', function () {
        $exceptions = [
            SearchException::searchFailed('query', 'reason'),
            SearchException::indexingFailed('index', 'reason'),
            SearchException::indexNotFound('index'),
            SearchException::indexCreationFailed('index', 'reason'),
            SearchException::invalidConfiguration('index', 'reason'),
            SearchException::searchEngineUnavailable('reason'),
            SearchException::bulkIndexingFailed('index', 10, 'reason'),
            SearchException::cacheOperationFailed('operation', 'reason'),
        ];

        foreach ($exceptions as $exception) {
            expect($exception->getMessage())->toBeString()
                ->and(strlen($exception->getMessage()))->toBeGreaterThan(0)
                ->and($exception->getCode())->toBe(0)
                ->and($exception->getFile())->toBeString()
                ->and($exception->getLine())->toBeInt();
        }
    });

    it('preserves stack trace information', function () {
        try {
            throw SearchException::searchFailed('test', 'error');
        } catch (SearchException $e) {
            expect($e->getTrace())->toBeArray()
                ->and($e->getTraceAsString())->toBeString()
                ->and($e->getFile())->toBeString()
                ->and($e->getLine())->toBeInt();
        }
    });

    it('maintains exception properties correctly', function () {
        $originalException = SearchException::indexingFailed('campaigns', 'Connection timeout');

        expect($originalException)->toBeInstanceOf(SearchException::class)
            ->and($originalException->getMessage())->toContain('campaigns')
            ->and($originalException->getMessage())->toContain('Connection timeout')
            ->and($originalException->getCode())->toBe(0);
    });

    it('handles very long error messages', function () {
        $longReason = str_repeat('This is a very long error message. ', 100);
        $exception = SearchException::searchFailed('query', $longReason);

        expect(strlen($exception->getMessage()))->toBeGreaterThan(1000)
            ->and($exception->getMessage())->toContain('This is a very long error message.')
            ->and($exception->getMessage())->toStartWith('Search failed for query "query":');
    });

    it('validates static factory method return types', function () {
        $searchException = SearchException::searchFailed('test', 'error');
        $indexException = SearchException::indexNotFound('test');
        $configException = SearchException::invalidConfiguration('test', 'error');
        $engineException = SearchException::searchEngineUnavailable('error');
        $bulkException = SearchException::bulkIndexingFailed('test', 5, 'error');
        $cacheException = SearchException::cacheOperationFailed('GET', 'error');

        expect($searchException)->toBeInstanceOf(SearchException::class)
            ->and($indexException)->toBeInstanceOf(SearchException::class)
            ->and($configException)->toBeInstanceOf(SearchException::class)
            ->and($engineException)->toBeInstanceOf(SearchException::class)
            ->and($bulkException)->toBeInstanceOf(SearchException::class)
            ->and($cacheException)->toBeInstanceOf(SearchException::class);
    });

    it('handles negative values in bulk indexing failed', function () {
        $exception = SearchException::bulkIndexingFailed('test', -5, 'Invalid count provided');

        expect($exception->getMessage())->toBe('Bulk indexing failed for index "test". -5 documents failed: Invalid count provided');
    });

    it('handles numeric values as strings in exception parameters', function () {
        $exception1 = SearchException::indexNotFound('123');
        $exception2 = SearchException::searchFailed('456', '789');

        expect($exception1->getMessage())->toBe('Index "123" not found')
            ->and($exception2->getMessage())->toBe('Search failed for query "456": 789');
    });
});
