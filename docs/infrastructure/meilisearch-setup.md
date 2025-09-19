# Meilisearch Setup and Configuration Guide

## Overview

Meilisearch is the primary search engine for the ACME Corp CSR platform, providing fast full-text search capabilities for campaigns, organizations, and donations. This guide covers installation, configuration, and implementation details.

## Architecture Overview

### Search Engine Selection
- **Primary**: Meilisearch (recommended for development and production)
- **Fallback**: Algolia support available
- **Local Development**: File-based search via Scout
- **Performance**: Sub-100ms search response times for 500k+ indexed documents

### Search Scope
- **Campaigns**: 500k active/completed campaigns (of 1M total)
- **Organizations**: All verified organizations
- **Donations**: Public donations with privacy controls
- **Users**: Admin search only (privacy compliant)

## Installation and Setup

### Docker Environment (Recommended)

```bash
# Meilisearch service is pre-configured in docker-compose.yml
docker-compose --env-file .env.docker up -d meilisearch

# Verify service startup
curl http://localhost:7700/health

# Expected response: {"status":"available"}
```

**Docker configuration**:
```yaml
# docker-compose.yml
meilisearch:
  image: getmeili/meilisearch:v1.10
  container_name: acme-meilisearch
  ports:
    - "7700:7700"
  environment:
    - MEILI_MASTER_KEY=${MEILISEARCH_KEY}
    - MEILI_ENV=production
    - MEILI_DB_PATH=/meili_data
    - MEILI_HTTP_ADDR=0.0.0.0:7700
    - MEILI_LOG_LEVEL=INFO
    - MEILI_MAX_INDEXING_MEMORY=2gb
    - MEILI_MAX_INDEXING_THREADS=4
  volumes:
    - ./storage/meilisearch:/meili_data
  restart: unless-stopped
```

### Local Development Setup

```bash
# Install Meilisearch (macOS)
brew install meilisearch

# Start Meilisearch service
meilisearch --master-key=your_development_key --db-path ./storage/meilisearch

# Alternative: Use file-based search for local development
# Set SCOUT_DRIVER=file in .env for minimal setup
```

### Production Installation

```bash
# Install via official installer (Ubuntu/Debian)
curl -L https://install.meilisearch.com | sh

# Move to system path
sudo mv ./meilisearch /usr/local/bin/

# Create system user
sudo useradd -r -s /bin/false meilisearch

# Create data directory
sudo mkdir -p /var/lib/meilisearch
sudo chown meilisearch:meilisearch /var/lib/meilisearch

# Create systemd service
sudo tee /etc/systemd/system/meilisearch.service > /dev/null << EOF
[Unit]
Description=Meilisearch
After=network.target

[Service]
Type=simple
User=meilisearch
Group=meilisearch
ExecStart=/usr/local/bin/meilisearch --db-path /var/lib/meilisearch --env production --master-key CHANGE_THIS_KEY
Restart=on-failure
RestartSec=3
TimeoutStopSec=10
KillMode=mixed

[Install]
WantedBy=multi-user.target
EOF

# Enable and start service
sudo systemctl enable meilisearch
sudo systemctl start meilisearch
```

## Laravel Scout Configuration

### Environment Variables

```env
# Search Engine Configuration
SCOUT_DRIVER=meilisearch
SCOUT_PREFIX=acme_
SCOUT_QUEUE=true
SCOUT_CHUNK_SIZE=1000

# Meilisearch Connection
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=your_master_key_here

# Alternative: Algolia Configuration
ALGOLIA_APP_ID=your_app_id
ALGOLIA_SECRET=your_secret_key
ALGOLIA_SEARCH_KEY=your_search_only_key

# Development: File-based search
# SCOUT_DRIVER=file
```

### Scout Service Configuration

```php
// config/scout.php
return [
    'driver' => env('SCOUT_DRIVER', 'meilisearch'),
    'prefix' => env('SCOUT_PREFIX', 'acme_'),
    'queue' => env('SCOUT_QUEUE', true),
    'chunk' => [
        'searchable' => env('SCOUT_CHUNK_SIZE', 1000),
        'unsearchable' => 500,
    ],

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            'campaigns' => [
                'filterableAttributes' => [
                    'status',
                    'organization_id',
                    'category',
                    'is_featured',
                    'start_date',
                    'end_date',
                    'goal_amount',
                    'current_amount',
                    'goal_percentage',
                ],
                'sortableAttributes' => [
                    'created_at',
                    'updated_at',
                    'start_date',
                    'end_date',
                    'goal_amount',
                    'current_amount',
                    'goal_percentage',
                    'donations_count',
                    'is_featured',
                    'title',
                ],
                'searchableAttributes' => [
                    'title',
                    'description',
                    'organization_name',
                    'category',
                    'tags',
                ],
                'displayedAttributes' => ['*'],
                'rankingRules' => [
                    'words',
                    'typo',
                    'proximity',
                    'attribute',
                    'sort',
                    'exactness',
                    'current_amount:desc',
                ],
                'stopWords' => ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'],
                'synonyms' => [
                    'donate' => ['give', 'contribute', 'support'],
                    'help' => ['aid', 'assist', 'support'],
                    'children' => ['kids', 'youth', 'minors'],
                    'education' => ['learning', 'school', 'academic'],
                    'health' => ['medical', 'healthcare', 'wellness'],
                ],
                'typoTolerance' => [
                    'enabled' => true,
                    'minWordSizeForTypos' => [
                        'oneTypo' => 5,
                        'twoTypos' => 9,
                    ],
                    'disableOnWords' => [],
                    'disableOnAttributes' => [],
                ],
                'pagination' => [
                    'maxTotalHits' => 100000,
                ],
            ],
            'organizations' => [
                'filterableAttributes' => ['category', 'verified', 'country', 'city'],
                'sortableAttributes' => ['name', 'created_at', 'campaigns_count'],
                'searchableAttributes' => ['name', 'description', 'category', 'city', 'country'],
            ],
            'donations' => [
                'filterableAttributes' => ['campaign_id', 'anonymous', 'amount_range'],
                'sortableAttributes' => ['created_at', 'amount'],
                'searchableAttributes' => ['message', 'donor_name'],
            ],
        ],
    ],

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET'),
        'search_only_key' => env('ALGOLIA_SEARCH_KEY', ''),
    ],

    'file' => [
        'path' => storage_path('app/scout'),
    ],
];
```

## Search Model Implementation

### Campaign Search Model

```php
namespace Modules\Campaign\Domain\Model;

use Laravel\Scout\Searchable;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Attributes\SearchUsingPrefix;

final class Campaign extends Model
{
    use Searchable;

    /**
     * Get the indexable data array for the model
     */
    public function toSearchableArray(): array
    {
        // Prevent N+1 queries during bulk indexing
        $organizationName = $this->relationLoaded('organization') && $this->organization
            ? $this->organization->getName()
            : null;

        $categoryName = $this->relationLoaded('organization') && $this->organization
            ? $this->organization->category
            : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->getCleanDescription(),
            'organization_name' => $organizationName,
            'organization_id' => $this->organization_id,
            'category' => $categoryName,
            'status' => $this->status,
            'goal_amount' => (float) $this->goal_amount,
            'current_amount' => (float) $this->current_amount,
            'goal_percentage' => $this->getGoalPercentage(),
            'donations_count' => $this->donations_count ?? 0,
            'start_date' => $this->start_date?->timestamp,
            'end_date' => $this->end_date?->timestamp,
            'is_featured' => $this->is_featured,
            'tags' => $this->getTagsArray(),
            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at->timestamp,
        ];
    }

    /**
     * Modify the query used to retrieve models when making all searchable
     */
    protected function makeAllSearchableUsing($query)
    {
        return $query->with(['organization:id,name,category']);
    }

    /**
     * Determine if the model should be searchable
     */
    public function shouldBeSearchable(): bool
    {
        return in_array($this->status, ['ACTIVE', 'COMPLETED']);
    }

    /**
     * Get the name of the index associated with the model
     */
    public function searchableAs(): string
    {
        return 'campaigns';
    }

    private function getCleanDescription(): ?string
    {
        return $this->description ? strip_tags($this->description) : null;
    }

    private function getGoalPercentage(): float
    {
        return $this->goal_amount > 0
            ? round(($this->current_amount / $this->goal_amount) * 100, 2)
            : 0.0;
    }

    private function getTagsArray(): array
    {
        return $this->relationLoaded('tags')
            ? $this->tags->pluck('name')->toArray()
            : [];
    }
}
```

### Organization Search Model

```php
namespace Modules\Organization\Domain\Model;

use Laravel\Scout\Searchable;

final class Organization extends Model
{
    use Searchable;

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => strip_tags($this->description ?? ''),
            'category' => $this->category,
            'verified' => $this->verified,
            'country' => $this->country,
            'city' => $this->city,
            'campaigns_count' => $this->campaigns_count ?? 0,
            'created_at' => $this->created_at->timestamp,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->verified === true;
    }

    public function searchableAs(): string
    {
        return 'organizations';
    }
}
```

## Search Implementation

### Campaign Search Query Handler

```php
namespace Modules\Campaign\Application\Query;

use Modules\Campaign\Domain\Model\Campaign;
use Laravel\Scout\Builder;

final class SearchCampaignsQueryHandler
{
    public function handle(SearchCampaignsQuery $query): SearchCampaignsResult
    {
        $builder = Campaign::search($query->searchTerm ?: '');

        // Apply filters
        $this->applyFilters($builder, $query);

        // Apply sorting
        $this->applySorting($builder, $query);

        // Execute search with pagination
        $results = $builder->paginate($query->perPage ?? 20);

        return new SearchCampaignsResult(
            campaigns: $results->items(),
            total: $results->total(),
            currentPage: $results->currentPage(),
            perPage: $results->perPage(),
            hasMorePages: $results->hasMorePages(),
        );
    }

    private function applyFilters(Builder $builder, SearchCampaignsQuery $query): void
    {
        if ($query->status) {
            $builder->where('status', $query->status);
        }

        if ($query->category) {
            $builder->where('category', $query->category);
        }

        if ($query->organizationId) {
            $builder->where('organization_id', $query->organizationId);
        }

        if ($query->isFeatured !== null) {
            $builder->where('is_featured', $query->isFeatured);
        }

        if ($query->minAmount || $query->maxAmount) {
            $builder->whereBetween('goal_amount', [
                $query->minAmount ?? 0,
                $query->maxAmount ?? PHP_INT_MAX,
            ]);
        }

        if ($query->startDate) {
            $builder->where('start_date', '>=', $query->startDate->timestamp);
        }

        if ($query->endDate) {
            $builder->where('end_date', '<=', $query->endDate->timestamp);
        }
    }

    private function applySorting(Builder $builder, SearchCampaignsQuery $query): void
    {
        $sortField = match($query->sortBy) {
            'newest' => 'created_at',
            'oldest' => 'created_at',
            'ending_soon' => 'end_date',
            'most_funded' => 'current_amount',
            'least_funded' => 'current_amount',
            'goal_percentage' => 'goal_percentage',
            'featured' => 'is_featured',
            'title' => 'title',
            default => '_score', // Relevance scoring
        };

        $direction = match($query->sortBy) {
            'oldest', 'ending_soon', 'least_funded' => 'asc',
            default => 'desc',
        };

        if ($sortField !== '_score') {
            $builder->orderBy($sortField, $direction);
        }
    }
}
```

### Autocomplete Implementation

```php
final class CampaignAutocompleteQueryHandler
{
    public function handle(CampaignAutocompleteQuery $query): array
    {
        if (strlen($query->searchTerm) < 2) {
            return [];
        }

        // Cache autocomplete results for 5 minutes
        return Cache::remember(
            "autocomplete:campaigns:{$query->searchTerm}",
            300,
            fn() => $this->getAutocompleteSuggestions($query->searchTerm)
        );
    }

    private function getAutocompleteSuggestions(string $term): array
    {
        $campaigns = Campaign::search($term)
            ->take(5)
            ->get(['id', 'title', 'organization_name'])
            ->map(fn($campaign) => [
                'type' => 'campaign',
                'id' => $campaign->id,
                'title' => $campaign->title,
                'subtitle' => $campaign->organization_name,
                'url' => route('campaigns.show', $campaign),
            ]);

        $organizations = Organization::search($term)
            ->take(3)
            ->get(['id', 'name', 'category'])
            ->map(fn($org) => [
                'type' => 'organization',
                'id' => $org->id,
                'title' => $org->name,
                'subtitle' => $org->category,
                'url' => route('organizations.show', $org),
            ]);

        return $campaigns->concat($organizations)->toArray();
    }
}
```

## Indexing Strategy

### Initial Setup and Configuration

```bash
# 1. Configure index settings
php artisan scout:sync-index-settings

# 2. Initial bulk indexing (async for large datasets)
php artisan scout:import-async 'Modules\Campaign\Domain\Model\Campaign'
php artisan scout:import-async 'Modules\Organization\Domain\Model\Organization'
php artisan scout:import-async 'Modules\Donation\Domain\Model\Donation'

# 3. Monitor indexing progress
php artisan scout:index-monitor 'Modules\Campaign\Domain\Model\Campaign'
```

### Automated Indexing

The platform uses queue-based auto-indexing for real-time search updates:

```php
// Auto-indexing is triggered by model events
class Campaign extends Model
{
    use Searchable;

    protected static function boot()
    {
        parent::boot();

        // Auto-index on create/update if shouldBeSearchable() returns true
        static::saved(function ($campaign) {
            if ($campaign->shouldBeSearchable()) {
                $campaign->searchable();
            } else {
                $campaign->unsearchable();
            }
        });

        // Remove from index on delete
        static::deleted(function ($campaign) {
            $campaign->unsearchable();
        });
    }
}
```

### Bulk Operations

```bash
# Reindex all campaigns (handles 500k+ records)
php artisan scout:import-async 'Modules\Campaign\Domain\Model\Campaign'

# Clear and rebuild index
php artisan scout:flush 'Modules\Campaign\Domain\Model\Campaign'
php artisan scout:import-async 'Modules\Campaign\Domain\Model\Campaign'

# Reindex specific status campaigns
php artisan tinker
>>> Campaign::where('status', 'ACTIVE')->searchable();
```

## Performance Optimization

### Index Configuration

```json
{
  "pagination": {
    "maxTotalHits": 100000
  },
  "rankingRules": [
    "words",
    "typo",
    "proximity",
    "attribute",
    "sort",
    "exactness",
    "current_amount:desc"
  ],
  "typoTolerance": {
    "enabled": true,
    "minWordSizeForTypos": {
      "oneTypo": 5,
      "twoTypos": 9
    }
  }
}
```

### Query Optimization

```php
// Use specific fields for better performance
Campaign::search($term)->take(20)->get(['id', 'title', 'organization_name']);

// Use filters instead of post-query filtering
Campaign::search($term)->where('status', 'ACTIVE')->get();

// Cache frequently used searches
Cache::remember("search:campaigns:{$term}", 300, fn() =>
    Campaign::search($term)->take(10)->get()
);
```

### Memory Management

For large dataset indexing:

```php
// Custom chunk size for memory optimization
Campaign::chunkById(1000, function ($campaigns) {
    $campaigns->searchable();

    // Force garbage collection for large datasets
    if (memory_get_usage() > 1073741824) { // 1GB
        gc_collect_cycles();
    }
});
```

## Monitoring and Maintenance

### Health Checks

```bash
# Check Meilisearch service status
curl -s http://127.0.0.1:7700/health

# Check index statistics
curl -s 'http://127.0.0.1:7700/indexes/acme_campaigns/stats' \
  -H 'Authorization: Bearer YOUR_MASTER_KEY'

# Monitor index document count
php artisan scout:index-monitor 'Modules\Campaign\Domain\Model\Campaign'
```

### Regular Maintenance Tasks

```bash
# Weekly index health check
0 2 * * 0 /usr/bin/php /path/to/artisan scout:index-monitor 'Modules\Campaign\Domain\Model\Campaign'

# Monthly full reindex (if needed)
0 3 1 * * /usr/bin/php /path/to/artisan scout:import-async 'Modules\Campaign\Domain\Model\Campaign'

# Daily log cleanup
0 4 * * * find /var/log/meilisearch -name "*.log" -mtime +7 -delete
```

### Performance Metrics

Target performance benchmarks:
- **Search Response Time**: < 100ms for 95th percentile
- **Index Document Count**: 500k+ campaigns, 50k+ organizations
- **Indexing Throughput**: 1000+ documents/second
- **Memory Usage**: < 2GB for Meilisearch process
- **Disk Usage**: < 5GB for complete search index

## Security Configuration

### Access Control

```bash
# Set strong master key
MEILISEARCH_KEY=$(openssl rand -base64 32)

# Configure API key permissions
curl -X POST 'http://127.0.0.1:7700/keys' \
  -H 'Authorization: Bearer MASTER_KEY' \
  -H 'Content-Type: application/json' \
  -d '{
    "description": "Search only key",
    "actions": ["search"],
    "indexes": ["acme_campaigns", "acme_organizations"],
    "expiresAt": null
  }'
```

### Network Security

```nginx
# Nginx proxy configuration for production
location /search/ {
    proxy_pass http://127.0.0.1:7700/;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;

    # Rate limiting
    limit_req zone=search burst=20 nodelay;

    # Only allow specific endpoints
    location ~ ^/search/(indexes/[^/]+/search|health)$ {
        proxy_pass http://127.0.0.1:7700$request_uri;
    }

    # Block admin endpoints
    location ~ ^/search/(keys|dumps|stats)$ {
        return 403;
    }
}
```

## Troubleshooting

For detailed troubleshooting information, see [meilisearch-troubleshooting.md](meilisearch-troubleshooting.md).

Common quick fixes:

```bash
# Fix sortable attributes error
php artisan scout:sync-index-settings

# Reindex if search returns no results
php artisan scout:import-async 'Modules\Campaign\Domain\Model\Campaign'

# Check service connectivity
curl http://127.0.0.1:7700/health
```

---

Developed and Maintained by Go2Digital

Copyright 2025 Go2Digital - All Rights Reserved