# Job Monitoring & Troubleshooting Guide

## Overview

This guide provides comprehensive monitoring strategies, troubleshooting procedures, and performance optimization techniques for the ACME Corp queue system. It covers real-time monitoring, alerting, debugging failed jobs, and maintaining optimal performance across all queues.

## Monitoring Dashboard

### Horizon Dashboard
Access the Horizon dashboard at `/admin/horizon` (requires `super_admin` role).

**Key Metrics Displayed**:
- **Throughput**: Jobs processed per minute across all queues
- **Wait Times**: Average time jobs spend waiting in each queue
- **Failed Jobs**: Recent failures with stack traces
- **Worker Status**: Active workers and their current jobs
- **Memory Usage**: Real-time memory consumption per worker

### Queue Health Indicators

#### Healthy Status
- Wait times under threshold
- Success rate > 98%
- Workers actively processing
- Memory usage stable

#### Warning Status
- Wait times approaching threshold
- Success rate 95-98%
- Some worker restarts
- Memory usage elevated

#### Critical Status
- Wait times exceeding threshold
- Success rate < 95%
- Workers failing/restarting frequently
- Memory leaks detected

## Real-Time Monitoring Commands

### Queue Status Commands
```bash
# Overall queue status
php artisan queue:monitor

# Specific queue monitoring
php artisan queue:monitor --queue=payments

# Failed jobs overview
php artisan queue:failed

# Worker process status
php artisan horizon:status

# Queue length for specific queue
redis-cli llen "queues:payments"
```

### Detailed Queue Analytics
```bash
# Queue statistics script
#!/bin/bash
echo "=== ACME Corp Queue Status ==="
echo "Timestamp: $(date)"
echo ""

# High Priority Queues
echo "HIGH PRIORITY QUEUES:"
echo "Payments: $(redis-cli llen 'queues:payments') jobs"
echo "Notifications: $(redis-cli llen 'queues:notifications') jobs"
echo ""

# Medium Priority Queues
echo "MEDIUM PRIORITY QUEUES:"
echo "Exports: $(redis-cli llen 'queues:exports') jobs"
echo "Reports: $(redis-cli llen 'queues:reports') jobs"
echo "Cache Warming: $(redis-cli llen 'queues:cache-warming') jobs"
echo ""

# Low Priority Queues
echo "LOW PRIORITY QUEUES:"
echo "Bulk: $(redis-cli llen 'queues:bulk') jobs"
echo "Maintenance: $(redis-cli llen 'queues:maintenance') jobs"
echo ""

# Worker Status
echo "ACTIVE WORKERS:"
ps aux | grep "queue:work" | grep -v grep | wc -l
```

## Performance Metrics

### Key Performance Indicators (KPIs)

#### Throughput Metrics
- **Jobs/minute per queue**: Target throughput rates
- **Processing time**: Average job execution duration
- **Queue velocity**: Rate of job completion vs. job addition

#### Reliability Metrics
- **Success rate**: Percentage of successful job completions
- **Retry rate**: Percentage of jobs requiring retries
- **Failure rate**: Critical failures requiring manual intervention

#### Resource Metrics
- **Memory utilization**: Per-worker and total memory usage
- **CPU usage**: Worker process CPU consumption
- **Redis memory**: Queue storage utilization

### Performance Targets

```php
// Expected performance baselines
$performanceTargets = [
    'payments' => [
        'max_wait_time' => 60,      // seconds
        'target_throughput' => 100,  // jobs/minute
        'success_rate' => 0.99,     // 99%
        'max_memory_mb' => 256,     // MB per worker
    ],
    'notifications' => [
        'max_wait_time' => 120,     // seconds
        'target_throughput' => 200,  // jobs/minute
        'success_rate' => 0.98,     // 98%
        'max_memory_mb' => 128,     // MB per worker
    ],
    'exports' => [
        'max_wait_time' => 600,     // seconds
        'target_throughput' => 10,   // jobs/minute
        'success_rate' => 0.95,     // 95%
        'max_memory_mb' => 1024,    // MB per worker
    ],
    // ... other queues
];
```

## Alerting System

### Critical Alerts (Immediate Response Required)

#### Payment Queue Issues
```bash
# Alert trigger conditions
PAYMENT_WAIT_TIME_THRESHOLD=60          # seconds
PAYMENT_FAILURE_RATE_THRESHOLD=0.02     # 2%
PAYMENT_QUEUE_LENGTH_THRESHOLD=100      # jobs

# Sample alert script
if [ $(redis-cli llen "queues:payments") -gt $PAYMENT_QUEUE_LENGTH_THRESHOLD ]; then
    echo "CRITICAL: Payment queue length exceeded threshold"
    # Send notification
    # Configure your notification system here
    echo "CRITICAL: Payment queue critical: $(redis-cli llen "queues:payments") jobs waiting"
fi
```

#### Worker Failures
```bash
# Monitor worker processes
EXPECTED_WORKERS=12
ACTUAL_WORKERS=$(pgrep -c "queue:work")

if [ $ACTUAL_WORKERS -lt $EXPECTED_WORKERS ]; then
    echo "CRITICAL: Worker shortage detected ($ACTUAL_WORKERS/$EXPECTED_WORKERS)"
    # Auto-restart workers
    supervisorctl restart acme-workers:*
fi
```

### Warning Alerts (Monitor Closely)

#### Queue Length Monitoring
```bash
# Warning thresholds
NOTIFICATION_QUEUE_WARNING=500
EXPORT_QUEUE_WARNING=50
BULK_QUEUE_WARNING=20

# Monitor and alert
for queue in notifications exports bulk; do
    length=$(redis-cli llen "queues:$queue")
    threshold_var="${queue}_QUEUE_WARNING"
    threshold=${!threshold_var}

    if [ $length -gt $threshold ]; then
        echo "WARNING: $queue queue length: $length (threshold: $threshold)"
    fi
done
```

### Custom Monitoring Scripts

#### Queue Health Check
```bash
#!/bin/bash
# /usr/local/bin/queue-health-check.sh

LOG_FILE="/var/log/queue-health.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# Function to check queue health
check_queue_health() {
    local queue_name=$1
    local max_length=$2
    local current_length=$(redis-cli llen "queues:$queue_name")

    if [ $current_length -gt $max_length ]; then
        echo "[$TIMESTAMP] WARNING: $queue_name queue length: $current_length (max: $max_length)" >> $LOG_FILE
        return 1
    else
        echo "[$TIMESTAMP] OK: $queue_name queue length: $current_length" >> $LOG_FILE
        return 0
    fi
}

# Check all queues
check_queue_health "payments" 50
check_queue_health "notifications" 200
check_queue_health "exports" 20
check_queue_health "reports" 30
check_queue_health "bulk" 10
check_queue_health "maintenance" 5

# Check worker processes
EXPECTED_WORKERS=12
ACTUAL_WORKERS=$(pgrep -c "queue:work")

if [ $ACTUAL_WORKERS -lt $EXPECTED_WORKERS ]; then
    echo "[$TIMESTAMP] CRITICAL: Worker shortage: $ACTUAL_WORKERS/$EXPECTED_WORKERS" >> $LOG_FILE
else
    echo "[$TIMESTAMP] OK: Workers active: $ACTUAL_WORKERS/$EXPECTED_WORKERS" >> $LOG_FILE
fi
```

## Troubleshooting Guide

### Common Issues and Solutions

#### High Queue Wait Times

**Symptoms**:
- Jobs waiting longer than expected
- Horizon dashboard showing red wait time indicators
- User complaints about delayed notifications/payments

**Diagnosis Steps**:
```bash
# 1. Check queue lengths
redis-cli llen "queues:payments"
redis-cli llen "queues:notifications"

# 2. Check active workers
ps aux | grep "queue:work" | grep -v grep

# 3. Check worker memory usage
ps -eo pid,ppid,cmd,%mem,%cpu --sort=-%mem | grep "queue:work"

# 4. Check Redis memory
redis-cli info memory
```

**Solutions**:
```bash
# Scale up workers
supervisorctl start acme-workers:payments-worker_03
supervisorctl start acme-workers:notifications-worker_03

# Clear stuck jobs if necessary
php artisan queue:clear payments

# Restart all workers to clear memory leaks
php artisan queue:restart
```

#### Job Failures

**Symptoms**:
- Increasing number of failed jobs
- Error notifications from `JobFailureNotificationJob`
- Specific functionality not working

**Diagnosis Steps**:
```bash
# 1. List recent failed jobs
php artisan queue:failed

# 2. Get detailed failure information
php artisan tinker
>>> \Illuminate\Support\Facades\Queue::failing()->first();

# 3. Check application logs
tail -f storage/logs/laravel.log | grep ERROR

# 4. Check specific job logs
tail -f /var/log/supervisor/payments-worker.log
```

**Solutions**:
```bash
# Retry specific failed job
php artisan queue:retry 123

# Retry all failed jobs for a queue
php artisan queue:failed | grep "payments" | awk '{print $1}' | xargs -I {} php artisan queue:retry {}

# Clear failed jobs if unrecoverable
php artisan queue:flush
```

#### Memory Issues

**Symptoms**:
- Workers being killed by OOM killer
- Steadily increasing memory usage
- Slow job processing

**Diagnosis Steps**:
```bash
# 1. Monitor memory usage over time
while true; do
    echo "$(date): $(ps -eo pid,ppid,cmd,%mem --sort=-%mem | grep 'queue:work' | head -5)"
    sleep 60
done

# 2. Check for memory leaks
php artisan tinker
>>> memory_get_usage(true)
>>> // Run a job manually
>>> memory_get_usage(true)

# 3. Check worker configuration
grep -r "memory" config/queue.php
```

**Solutions**:
```bash
# 1. Restart workers to clear memory
php artisan queue:restart

# 2. Adjust worker memory limits
# Update config/queue.php memory settings

# 3. Implement job chunking for large operations
# Modify jobs to process data in smaller chunks

# 4. Add memory monitoring to workers
php artisan queue:work --memory=512 --max-jobs=100
```

#### Redis Connection Issues

**Symptoms**:
- Connection timeouts
- Jobs not being processed
- Redis connection errors in logs

**Diagnosis Steps**:
```bash
# 1. Test Redis connectivity
redis-cli ping

# 2. Check Redis memory and connections
redis-cli info stats
redis-cli info clients

# 3. Check network connectivity
telnet redis-host 6379

# 4. Review Redis logs
tail -f /var/log/redis/redis-server.log
```

**Solutions**:
```bash
# 1. Restart Redis (if safe to do so)
sudo service redis-server restart

# 2. Clear Redis memory if needed
redis-cli flushall  # WARNING: This clears all data

# 3. Adjust Redis configuration
# Update redis.conf for better performance

# 4. Check Redis connection pool settings
# Review config/database.php Redis settings
```

### Advanced Debugging

#### Job Payload Analysis
```bash
# Examine job payload in Redis
redis-cli lindex "queues:payments" 0
```

#### Performance Profiling
```php
// Add to job handle() method for profiling
$startTime = microtime(true);
$startMemory = memory_get_usage(true);

// ... job logic ...

$endTime = microtime(true);
$endMemory = memory_get_usage(true);

Log::info('Job Performance', [
    'job' => get_class($this),
    'execution_time' => $endTime - $startTime,
    'memory_used' => $endMemory - $startMemory,
    'peak_memory' => memory_get_peak_usage(true),
]);
```

#### Database Query Analysis
```php
// Enable query logging in jobs
DB::enableQueryLog();

// ... job logic ...

$queries = DB::getQueryLog();
Log::info('Job Queries', ['count' => count($queries), 'queries' => $queries]);
```

## Performance Optimization

### Job Optimization Strategies

#### Batch Processing
```php
// Instead of processing items individually
foreach ($items as $item) {
    ProcessItemJob::dispatch($item);
}

// Process in batches
$batches = array_chunk($items, 100);
foreach ($batches as $batch) {
    ProcessItemBatchJob::dispatch($batch);
}
```

#### Database Optimization
```php
// Use eager loading in jobs
$campaigns = Campaign::with(['organization', 'creator', 'donations'])->get();

// Use chunking for large datasets
Campaign::chunk(500, function ($campaigns) {
    foreach ($campaigns as $campaign) {
        // Process campaign
    }
});
```

#### Memory Management
```php
class OptimizedJob implements ShouldQueue
{
    public function handle()
    {
        // Process in chunks to control memory
        $processed = 0;

        while ($processed < $this->totalItems) {
            $items = $this->getNextChunk($processed, 100);

            foreach ($items as $item) {
                $this->processItem($item);
            }

            $processed += 100;

            // Force garbage collection
            gc_collect_cycles();
        }
    }
}
```

### Infrastructure Optimization

#### Redis Optimization
```bash
# Redis performance tuning
redis-cli config set maxmemory-policy allkeys-lru
redis-cli config set maxmemory 2gb
redis-cli config set save "900 1 300 10 60 10000"
```

#### Worker Tuning
```bash
# Optimize worker parameters
php artisan queue:work \
    --queue=payments \
    --sleep=0 \           # No sleep for high throughput
    --max-jobs=1000 \     # Restart after 1000 jobs
    --max-time=3600 \     # Restart every hour
    --timeout=180         # Job timeout
```

## Maintenance Procedures

### Daily Maintenance
```bash
#!/bin/bash
# Daily queue maintenance script

# 1. Check queue health
/usr/local/bin/queue-health-check.sh

# 2. Clear old failed jobs (older than 7 days)
php artisan queue:prune-failed --hours=168

# 3. Monitor disk space for logs
du -h /var/log/supervisor/

# 4. Restart workers if needed (during low traffic)
if [ $(date +%H) -eq 3 ]; then  # 3 AM
    php artisan queue:restart
    sleep 30
    supervisorctl restart acme-workers:*
fi
```

### Weekly Maintenance
```bash
#!/bin/bash
# Weekly queue maintenance script

# 1. Full queue metrics report
echo "=== Weekly Queue Report ===" > /tmp/queue-report.txt
echo "Date: $(date)" >> /tmp/queue-report.txt

# 2. Performance analysis
php artisan queue:stats --week >> /tmp/queue-report.txt

# 3. Failed job analysis
php artisan queue:failed | wc -l >> /tmp/queue-report.txt

# 4. Send report to team
mail -s "Weekly Queue Report" admin@yourdomain.com < /tmp/queue-report.txt
```

---

**Developed and Maintained by Go2digit.al**

Specialized in enterprise-grade applications with focus on scalability, security, and maintainability.

Copyright 2025 Go2digit.al - All Rights Reserved