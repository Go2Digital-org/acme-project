# Meilisearch Troubleshooting Guide

## Overview

This guide covers common Meilisearch indexing and search issues encountered in the ACME Corp CSR platform, along with their solutions and prevention strategies.

## Common Issues and Solutions

### 1. Empty Search Results / "No campaigns found"

#### Symptoms
- Campaigns page shows "No campaigns found"
- Search returns 0 results despite having data in database
- API calls return empty result sets

#### Root Causes & Solutions

##### A. Missing Sortable Attributes Configuration

**Problem**: Meilisearch index doesn't have sortable attributes configured, causing searches with sort parameters to fail.

**Error in logs**:
```
Meilisearch search failed for public campaigns
"error":"Index `campaigns`: Attribute `created_at` is not sortable. This index does not have configured sortable attributes."
```

**Solution**:
```bash
# Option 1: Use Scout sync command (Recommended)
php artisan scout:sync-index-settings

# Option 2: Use custom configure command
php artisan meilisearch:configure

# Option 3: Manual verification and configuration
curl -s 'http://127.0.0.1:7700/indexes/acme_campaigns/settings/sortable-attributes' \
  -H 'Authorization: Bearer YOUR_MASTER_KEY'
```

**Expected sortable attributes**:
- `created_at`, `updated_at`
- `start_date`, `end_date`
- `goal_amount`, `current_amount`
- `goal_percentage`, `donations_count`
- `is_featured`, `title`

##### B. Index Not Populated

**Problem**: Meilisearch index exists but has no documents.

**Diagnosis**:
```bash
# Check index stats
curl -s 'http://127.0.0.1:7700/indexes/acme_campaigns/stats' \
  -H 'Authorization: Bearer YOUR_MASTER_KEY' | jq

# Check document count
php artisan scout:index-monitor 'Modules\Campaign\Domain\Model\Campaign'
```

**Solution**:
```bash
# Reindex all campaigns asynchronously
php artisan scout:import-async 'Modules\Campaign\Domain\Model\Campaign'

# Monitor indexing progress
php artisan scout:index-monitor 'Modules\Campaign\Domain\Model\Campaign'
```

##### C. Configuration Mismatch

**Problem**: Scout configuration doesn't match Meilisearch setup.

**Check configuration**:
```bash
# Verify Scout settings
grep -E 'SCOUT_|MEILISEARCH_' .env

# Expected configuration:
# SCOUT_DRIVER=meilisearch
# SCOUT_PREFIX=acme_
# SCOUT_QUEUE=true
# MEILISEARCH_HOST=http://127.0.0.1:7700
# MEILISEARCH_KEY=your_master_key
```

### 2. Indexing Performance Issues

#### Symptoms
- Slow indexing of large datasets (1M+ records)
- Memory exhaustion during indexing
- Queue jobs timing out

#### Solutions

##### A. Use Async Indexing for Large Datasets

**Problem**: Synchronous indexing causes memory issues with large datasets.

**Solution**:
```bash
# Use async indexing instead of regular import
php artisan scout:import-async 'Modules\Campaign\Domain\Model\Campaign'

# NOT: php artisan scout:import (synchronous - causes memory issues)
```

**Configuration optimization**:
```env
# Optimize chunk sizes for large datasets
SCOUT_CHUNK_SIZE=1000
SCOUT_QUEUE=true

# Ensure adequate memory for workers
php.ini: memory_limit=2048M (for worker processes)
```

##### B. Memory Exhaustion Prevention

**Problem**: Scout's `searchable()` method loads all relationships causing memory exhaustion.

**Solution**: The platform implements a bypass in `Campaign::toSearchableArray()`:
```php
// Avoids N+1 queries and memory issues
$organizationName = $this->relationLoaded('organization') && $this->organization
    ? $this->organization->getName()
    : null; // Fallback instead of lazy loading
```

### 3. Auto-Indexing Issues

#### Symptoms
- New campaigns don't appear in search results
- Updates to campaigns don't sync to Meilisearch
- Queue workers not processing search jobs

#### Solutions

##### A. Verify Auto-Indexing Configuration

**Check Scout queue configuration**:
```bash
# Should be enabled for async indexing
grep SCOUT_QUEUE .env
# Expected: SCOUT_QUEUE=true
```

**Verify queue workers are running**:
```bash
# Check worker status
supervisorctl status | grep -E 'scout|worker'

# Should see workers like:
# acme-staging-workers:acme-staging-scout-indexer_00   RUNNING
```

##### B. Test Auto-Indexing

**Create test campaign**:
1. Create a campaign with status "ACTIVE" or "COMPLETED"
2. Check if it appears in search within 30 seconds
3. Only ACTIVE and COMPLETED campaigns are indexed (see `shouldBeSearchable()`)

**Manual trigger if needed**:
```bash
# Force reindex specific campaign
php artisan tinker
>>> $campaign = \Modules\Campaign\Domain\Model\Campaign::find(123);
>>> $campaign->searchable();
```

### 4. Index Corruption or Inconsistency

#### Symptoms
- Search results don't match database data
- Some campaigns missing from search results
- Outdated information in search results

#### Solutions

##### A. Full Index Rebuild

```bash
# 1. Clear existing index
php artisan scout:flush 'Modules\Campaign\Domain\Model\Campaign'

# 2. Reindex everything
php artisan scout:import-async 'Modules\Campaign\Domain\Model\Campaign'

# 3. Monitor completion
php artisan scout:index-monitor 'Modules\Campaign\Domain\Model\Campaign'
```

##### B. Selective Reindexing

```bash
# Reindex only active campaigns
php artisan tinker
>>> \Modules\Campaign\Domain\Model\Campaign::where('status', 'active')->searchable();
```

### 5. Configuration Sync Issues

#### Symptoms
- Settings update commands fail
- Meilisearch rejects filter/sort requests
- Index settings don't match config

#### Solutions

##### A. Automated Configuration

```bash
# Use post-deployment command (comprehensive)
php artisan deploy:post

# This runs:
# - Asset publishing
# - Cache clearing
# - Meilisearch configuration
# - Storage linking
```

##### B. Manual Configuration Verification

```bash
# Check current index settings
curl -s 'http://127.0.0.1:7700/indexes/acme_campaigns/settings' \
  -H 'Authorization: Bearer YOUR_MASTER_KEY' | jq

# Compare with config
cat config/scout.php | grep -A 50 'campaigns'
```

## Monitoring and Diagnostics

### Health Check Commands

```bash
# 1. Database vs Index comparison
php artisan scout:index-monitor 'Modules\Campaign\Domain\Model\Campaign'

# 2. Check Meilisearch connection
curl -s 'http://127.0.0.1:7700/health'

# 3. Test simple search
curl -s 'http://127.0.0.1:7700/indexes/acme_campaigns/search' \
  -H 'Authorization: Bearer YOUR_MASTER_KEY' \
  -H 'Content-Type: application/json' \
  -d '{"q":"","limit":5}' | jq '.hits | length'

# 4. Check queue status
php artisan horizon:status  # If using Horizon
supervisorctl status | grep scout  # Direct supervisor check
```

### Log Monitoring

**Key log locations**:
```bash
# Laravel application logs
tail -f storage/logs/laravel.log | grep -i meilisearch

# Queue worker logs
tail -f storage/logs/worker.log

# Supervisor logs
tail -f /var/log/supervisor/acme-*-scout*.log
```

**Common error patterns to watch for**:
- `Meilisearch search failed for public campaigns`
- `Attribute is not sortable`
- `Index not found`
- `Connection refused` (Meilisearch down)

## Prevention Best Practices

### 1. Deployment Checklist

```bash
# Always run after deployment
php artisan deploy:post

# Or individual commands:
php artisan scout:sync-index-settings
php artisan scout:import-async 'Modules\Campaign\Domain\Model\Campaign'
php artisan cache:clear
```

### 2. Monitoring Setup

**Set up alerts for**:
- Meilisearch service availability
- Index document count drops
- Queue job failures in scout queues
- Search response time degradation

### 3. Regular Maintenance

```bash
# Weekly index health check
php artisan scout:index-monitor 'Modules\Campaign\Domain\Model\Campaign'

# Monthly full reindex (if needed)
php artisan scout:import-async 'Modules\Campaign\Domain\Model\Campaign'
```

### 4. Performance Optimization

**Index settings optimization**:
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
  ]
}
```

## Emergency Recovery

### Complete Meilisearch Recovery

If Meilisearch is completely corrupted or lost:

```bash
# 1. Stop Meilisearch service
systemctl stop meilisearch

# 2. Clear data directory
rm -rf /var/lib/meilisearch/data/*

# 3. Start Meilisearch service
systemctl start meilisearch

# 4. Recreate indices and settings
php artisan scout:sync-index-settings

# 5. Full reindex
php artisan scout:import-async 'Modules\Campaign\Domain\Model\Campaign'
php artisan scout:import-async 'Modules\Donation\Domain\Model\Donation'
php artisan scout:import-async 'Modules\User\Domain\Model\User'
# ... other models

# 6. Verify recovery
php artisan scout:index-monitor 'Modules\Campaign\Domain\Model\Campaign'
```

## Understanding Index Limitations

### Campaign Indexing Rules

**Only indexable campaigns** (via `shouldBeSearchable()`):
- Status: `ACTIVE` or `COMPLETED` only
- Total campaigns: ~1M
- Indexable campaigns: ~500k (Active: ~250k + Completed: ~250k)
- Not indexed: DRAFT and CANCELLED campaigns

**Search behavior**:
- Draft campaigns: Available via database queries for campaign owners
- Public search: Only searches indexed (ACTIVE/COMPLETED) campaigns
- Admin search: May use different search strategies

This is by design - public users should only see active or completed campaigns in search results.

## Contact and Support

For complex Meilisearch issues:
1. Check this troubleshooting guide first
2. Review application logs for specific error messages
3. Test with the diagnostic commands provided
4. Contact development team with specific error messages and steps to reproduce

---

**Developed and Maintained by [Go2digit.al](https://go2digit.al)**

**Last Updated**: September 2025
**Platform Version**: Laravel 12.x with Meilisearch 1.0+

Copyright 2025 Go2digit.al - All Rights Reserved