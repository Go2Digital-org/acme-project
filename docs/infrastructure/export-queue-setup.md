# Export Queue Worker Setup Guide

## Overview

The ACME Corp CSR Platform's export system allows users to export large datasets (donations, campaigns, reports) in CSV and Excel formats. This guide covers the complete setup for the export queue worker system.

## Architecture

```
User Request → Export Job → Queue → Worker → File Generation → Notification
```

### Components

1. **Export Controller**: Handles export requests from UI (`/exports/manage`)
2. **Export Jobs**: `ProcessDonationExportJob` processes exports in chunks
3. **Queue System**: Uses `exports` queue with database/Redis backend
4. **File Storage**: Saves to `storage/app/private/exports/final/`
5. **Notifications**: Email notifications on completion/failure

## Queue Configuration

### Export Queue Specifications

- **Queue Name**: `exports`
- **Priority**: Medium (processes after payments/notifications)
- **Timeout**: 900 seconds (15 minutes)
- **Memory Limit**: 1GB
- **Max Tries**: 3
- **Chunk Size**: 1000 records per batch

## Local Development Setup

### 1. Basic Queue Worker

Run the queue worker in your terminal:

```bash
# Process export jobs continuously
php artisan queue:work --queue=exports,default

# Process with specific timeout for large exports
php artisan queue:work --queue=exports,default --timeout=900 --memory=1024

# Process a single job (for testing)
php artisan queue:work --queue=exports --once

# Process and exit when queue is empty
php artisan queue:work --queue=exports --stop-when-empty
```

### 2. Using Laravel Sail (Docker)

```bash
# Inside sail container
sail artisan queue:work --queue=exports,default

# Or from host
./vendor/bin/sail artisan queue:work --queue=exports,default
```

### 3. Queue Monitoring

```bash
# Check pending jobs
php artisan queue:monitor exports

# Check failed jobs
php artisan queue:failed

# Retry failed export jobs
php artisan queue:retry all
```

## Production Setup with Supervisor

### 1. Install Supervisor

```bash
# Ubuntu/Debian
sudo apt-get install supervisor

# CentOS/RHEL
sudo yum install supervisor
```

### 2. Create Supervisor Configuration

Create file: `/etc/supervisor/conf.d/laravel-export-worker.conf`

```ini
[program:laravel-export-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/acme/acme-corp-optimy/artisan queue:work --queue=exports,default --sleep=3 --tries=3 --timeout=900 --memory=1024
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/export-worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=10
stopwaitsecs=3600
stopsignal=TERM
```

### 3. Configuration Explanation

- **numprocs=2**: Runs 2 worker processes for parallel processing
- **timeout=900**: 15-minute timeout for large exports
- **memory=1024**: 1GB memory limit per worker
- **autostart=true**: Starts automatically with supervisor
- **autorestart=true**: Restarts if worker crashes
- **user=www-data**: Runs as web server user
- **stopwaitsecs=3600**: Allows 1 hour for graceful shutdown

### 4. Start and Manage Workers

```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start export workers
sudo supervisorctl start laravel-export-worker:*

# Check status
sudo supervisorctl status laravel-export-worker:*

# Stop workers (for deployment)
sudo supervisorctl stop laravel-export-worker:*

# Restart workers
sudo supervisorctl restart laravel-export-worker:*

# View logs
tail -f /var/log/supervisor/export-worker.log
```

## Docker Production Setup

### 1. Docker Compose Configuration

Add to `docker-compose.prod.yml`:

```yaml
  export-worker:
    image: ${APP_IMAGE}
    restart: unless-stopped
    command: php artisan queue:work --queue=exports,default --sleep=3 --tries=3 --timeout=900
    volumes:
      - ./storage:/var/www/html/storage
    environment:
      - QUEUE_CONNECTION=redis
      - REDIS_HOST=redis
    deploy:
      replicas: 2
      resources:
        limits:
          memory: 1G
    depends_on:
      - redis
      - mysql
```

### 2. Kubernetes Deployment

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: export-worker
spec:
  replicas: 2
  selector:
    matchLabels:
      app: export-worker
  template:
    metadata:
      labels:
        app: export-worker
    spec:
      containers:
      - name: worker
        image: acme-corp:latest
        command: ["php", "artisan", "queue:work", "--queue=exports,default", "--sleep=3", "--tries=3", "--timeout=900"]
        resources:
          limits:
            memory: "1Gi"
          requests:
            memory: "512Mi"
```

## Environment Configuration

### Required .env Variables

```env
# Queue Connection
QUEUE_CONNECTION=database  # or 'redis' for production

# Redis Configuration (if using Redis)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Export Settings
EXPORT_CHUNK_SIZE=1000
EXPORT_MAX_RECORDS=100000
EXPORT_TIMEOUT=900
EXPORT_RETENTION_DAYS=7

# Mail Configuration (for notifications)
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
```

## Troubleshooting

### Common Issues

#### 1. Exports Stay "Pending"

**Cause**: No queue worker running
**Solution**:
```bash
# Check if worker is running
ps aux | grep queue:work

# Start worker
php artisan queue:work --queue=exports,default
```

#### 2. Export Jobs Failing

**Cause**: Memory limit or timeout
**Solution**:
```bash
# Increase memory and timeout
php artisan queue:work --queue=exports --memory=2048 --timeout=1800
```

#### 3. No Email Notifications

**Current Issue**: Notification service not integrated in `ProcessDonationExportJob`
**Temporary Workaround**: Check export status at `/exports/manage`

#### 4. Files Not Found

**Cause**: Incorrect storage path
**Check**:
```bash
# Files are stored in private storage
ls storage/app/private/exports/final/
```

### Monitoring Commands

```bash
# Check queue size
php artisan tinker
>>> DB::table('jobs')->where('queue', 'exports')->count();

# Check export job status
>>> DB::table('export_jobs')->orderBy('created_at', 'desc')->first();

# Clear stuck jobs (use carefully)
>>> DB::table('jobs')->where('queue', 'exports')->where('created_at', '<', now()->subHours(2))->delete();
```

### Log Locations

- **Laravel Logs**: `storage/logs/laravel.log`
- **Worker Logs**: `/var/log/supervisor/export-worker.log`
- **Failed Jobs**: Database table `failed_jobs`

## Performance Optimization

### 1. Queue Priority

Process exports after critical operations:
```bash
php artisan queue:work --queue=payments,notifications,exports,default
```

### 2. Chunked Processing

Exports process in 1000-record chunks to prevent memory issues.

### 3. Parallel Workers

Run multiple workers for faster processing:
```bash
# Terminal 1
php artisan queue:work --queue=exports

# Terminal 2
php artisan queue:work --queue=exports
```

## Testing Exports

### 1. Create Test Export

```bash
# Via tinker
php artisan tinker
>>> dispatch(new \Modules\Export\Infrastructure\Laravel\Jobs\ProcessDonationExportJob(
    'test-export-id',
    ['status' => 'completed'],
    'csv',
    1, // user_id
    1  // organization_id
));
```

### 2. Monitor Progress

```bash
# Watch queue processing
php artisan queue:listen --queue=exports

# Check export status
mysql -u root -p acme_corp_csr -e "SELECT * FROM export_jobs ORDER BY created_at DESC LIMIT 5;"
```

## Security Considerations

1. **File Access**: Exports stored in `private` storage directory
2. **User Isolation**: Each user can only access their own exports
3. **Expiration**: Exports auto-expire after 7 days
4. **Rate Limiting**: Maximum 10 exports per day per user
5. **Size Limits**: Maximum 100,000 records per export

## Related Documentation

- [Queue Worker System](./queue-worker-system.md) - Complete queue architecture
- [Redis Queue Setup](./redis-queue.md) - Redis configuration for production
- [Deployment Guide](./deployment.md) - Full deployment instructions

## Quick Reference

```bash
# Development - Start export worker
php artisan queue:work --queue=exports,default

# Production - Supervisor commands
sudo supervisorctl start laravel-export-worker:*
sudo supervisorctl status
sudo supervisorctl restart laravel-export-worker:*

# Monitoring
php artisan queue:failed
php artisan queue:retry all
tail -f storage/logs/laravel.log
```

---

**Note**: Email notifications are pending integration. Currently, check export status at `/exports/manage`.

---

**Developed and Maintained by [Go2digit.al](https://go2digit.al)**

Specialized in enterprise-grade applications with focus on scalability, security, and maintainability.

Copyright 2025 Go2digit.al - All Rights Reserved