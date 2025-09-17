# Go2digit.al ACME Corp CSR Platform - Docker Deployment Guide

## Modern 2025 Architecture Overview

This deployment guide covers the modernized Docker setup for the Go2digit.al ACME Corp CSR Platform, featuring:

- **FrankenPHP + Laravel Octane**: High-performance PHP application server
- **Unified Container Architecture**: Single application container with Supervisor process management
- **Multi-tenant Support**: Wildcard domain routing with tenant isolation
- **Production-ready**: Comprehensive monitoring, health checks, and security features

## Prerequisites

- Docker 20.10+ with Compose V2
- 4GB+ RAM for development, 8GB+ for production
- Git
- Basic understanding of Laravel and Docker

## Architecture Components

### Container Stack

| Service | Description | Ports | Platform |
|---------|-------------|-------|----------|
| **acme-app** | FrankenPHP + Laravel Octane + Supervisor | 80, 443, 443/udp | Multi-platform |
| **mysql** | MySQL 8.4 Database | 3306 | AMD64 |
| **redis** | Redis Cache & Queue Backend | 6379 | ARM64 |
| **meilisearch** | Full-text Search Engine | 7700 | ARM64 |
| **mailpit** | Email Testing (dev only) | 1025, 8025 | ARM64 |
| **redisinsight** | Redis Management (dev only) | 5540 | ARM64 |

### Process Management (Supervisor)

The unified application container runs multiple processes:

- **FrankenPHP**: Web server with Octane workers
- **Laravel Horizon**: Queue management and monitoring
- **Laravel Scheduler**: Cron job equivalent
- **Queue Workers**: Priority-based queue processing
  - High Priority: payments, notifications
  - Medium Priority: exports, reports, default
  - Low Priority: bulk, maintenance, cache-warming

## Quick Start

### Development Environment

```bash
# 1. Clone and navigate to project
git clone <repository-url>
cd acme-corp-optimy

# 2. Start development environment
docker-compose --env-file .env.docker up -d

# 3. Run initial setup
docker exec acme-app php artisan migrate
docker exec acme-app php artisan db:seed

# 4. Access the application
open http://app.acme-corp-optimy.orb.local
```

### Production Deployment

```bash
# 1. Start production environment with production overrides
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# 2. Run production setup
docker exec acme-app php artisan migrate --force
docker exec acme-app php artisan config:cache
docker exec acme-app php artisan route:cache
docker exec acme-app php artisan view:cache
```

## Configuration

### Docker Compose Files

The project uses a clean two-file Docker Compose setup:

- **`docker-compose.yml`** - Base configuration for development and staging
  - Complete service definitions (app, mysql, redis, meilisearch, mailpit, redisinsight)
  - Multi-tenancy support with OrbStack domain
  - Development-optimized settings

- **`docker-compose.prod.yml`** - Production overrides
  - Production environment variables (APP_ENV=production, APP_DEBUG=false)
  - SSL/HTTPS configuration (OCTANE_HTTPS=true)
  - Production domain configuration
  - Security enhancements

### Environment Files

| File | Purpose | Usage |
|------|---------|-------|
| `.env` | Current active configuration | Auto-managed |
| `docker/env/.env.production` | Production template | Copy to `.env` for production |
| `docker/env/.env.staging` | Staging template | Copy to `.env` for staging |

### Multi-tenant Domain Configuration

The platform supports multiple domain configurations:

#### Development
- **Central Domain**: `localhost`, `127.0.0.1`, `acme-corp-optimy.test`
- **Tenant Domains**: `*.localhost` (e.g., `tenant1.localhost`)

#### Production
- **Central Domain**: `acme-corp.com`
- **Tenant Domains**: `*.acme-corp.com` (e.g., `tenant1.acme-corp.com`)

### DNS Configuration

For production multi-tenancy, configure DNS:

```dns
# A Records
acme-corp.com.     3600  IN  A  YOUR_SERVER_IP

# Wildcard for tenant subdomains
*.acme-corp.com.   3600  IN  A  YOUR_SERVER_IP
```

## Management Commands

The `docker-manager.sh` script provides comprehensive management:

### Basic Operations
```bash
# Start platform
./docker/scripts/docker-manager.sh start [environment]

# Stop platform
./docker/scripts/docker-manager.sh stop

# Restart platform
./docker/scripts/docker-manager.sh restart

# Check status
./docker/scripts/docker-manager.sh status
```

### Application Management
```bash
# Run Laravel commands
./docker/scripts/docker-manager.sh artisan migrate
./docker/scripts/docker-manager.sh artisan queue:work

# Run Composer commands
./docker/scripts/docker-manager.sh composer install
./docker/scripts/docker-manager.sh composer update

# Run NPM commands
./docker/scripts/docker-manager.sh npm install
./docker/scripts/docker-manager.sh npm run build
```

### Monitoring & Debugging
```bash
# View logs
./docker/scripts/docker-manager.sh logs [service]

# Open shell
./docker/scripts/docker-manager.sh shell

# Check health
./docker/scripts/docker-manager.sh health

# Monitor queues
./docker/scripts/docker-manager.sh queues

# Access Horizon
./docker/scripts/docker-manager.sh horizon
```

### Backup & Maintenance
```bash
# Create backup
./docker/scripts/docker-manager.sh backup [environment]

# Reset platform (DESTRUCTIVE)
./docker/scripts/docker-manager.sh reset
```

## Monitoring & Health Checks

### Health Check Endpoints

| Endpoint | Purpose | Usage |
|----------|---------|-------|
| `/health` | Basic health check | Load balancer health checks |
| `/health/detailed` | Comprehensive system status | Monitoring dashboards |
| `/health/ready` | Kubernetes readiness probe | K8s readiness |
| `/health/live` | Kubernetes liveness probe | K8s liveness |
| `/metrics` | Prometheus metrics | Metrics collection |

### Accessing Monitoring Tools

- **Horizon Dashboard**: http://localhost/admin/horizon
- **Mailpit (dev)**: http://localhost:8025
- **RedisInsight (dev)**: http://localhost:5540
- **Meilisearch**: http://localhost:7700

## Security Considerations

### Production Security Checklist

- [ ] Generate secure `APP_KEY`
- [ ] Set strong database passwords
- [ ] Configure Redis authentication
- [ ] Set up SSL certificates
- [ ] Configure firewall rules
- [ ] Enable security headers
- [ ] Set up log monitoring
- [ ] Configure backup strategy

### SSL/TLS Configuration

FrankenPHP automatically handles SSL with Let's Encrypt:

```yaml
# Production domain configuration
SERVER_NAME: "acme-corp.com, *.acme-corp.com:443"
OCTANE_HTTPS: true
LETS_ENCRYPT_EMAIL: admin@acme-corp.com
```

## Performance Optimization

### Resource Allocation

#### Development
- **CPU**: 2 cores minimum
- **Memory**: 4GB minimum
- **Workers**: 8 total processes

#### Production
- **CPU**: 4+ cores recommended
- **Memory**: 8GB+ recommended
- **Workers**: 25+ processes (auto-scaling)

### Queue Configuration

The platform uses priority-based queue processing:

```yaml
High Priority (urgent):
  - payments: 2-8 workers, 256MB each
  - notifications: 1-4 workers, 128MB each

Medium Priority (important):
  - exports: 2 workers, 1024MB each
  - reports: 2 workers, 512MB each
  - default: variable workers

Low Priority (background):
  - bulk: 1 worker, 1024MB
  - maintenance: 1 worker, 256MB
  - cache-warming: variable workers
```

### Caching Strategy

- **Redis**: Primary cache backend
- **OPcache**: PHP bytecode caching
- **Route/Config/View**: Laravel optimization caching
- **Multi-tenant**: Tenant-aware cache isolation

## Troubleshooting

### Common Issues

#### Container Won't Start
```bash
# Check logs
./docker/scripts/docker-manager.sh logs app

# Check system resources
docker system df
docker stats
```

#### Database Connection Issues
```bash
# Check MySQL health
docker exec acme-mysql mysqladmin ping -h localhost -u root -p

# Verify network connectivity
docker exec acme-app ping mysql
```

#### Queue Workers Not Processing
```bash
# Check Horizon status
./docker/scripts/docker-manager.sh horizon

# Check supervisor processes
docker exec acme-app supervisorctl status

# Restart queue workers
docker exec acme-app supervisorctl restart queue-high:*
```

#### Performance Issues
```bash
# Check resource usage
docker stats

# Monitor health endpoints
curl http://localhost/health/detailed

# Check application metrics
curl http://localhost/metrics
```

### Log Locations

- **Application**: `docker logs acme-app`
- **MySQL**: `docker logs acme-mysql`
- **Redis**: `docker logs acme-redis`
- **Supervisor**: `docker exec acme-app supervisorctl tail -f <process_name>`

## Migration from Legacy Setup

### From Multi-Container to Unified

The new setup consolidates the previous 4-container architecture:

| Legacy | New (Unified) |
|--------|---------------|
| `app` container | `app` container (FrankenPHP + Octane) |
| `queue-worker` container | Supervisor process in `app` |
| `horizon` container | Supervisor process in `app` |
| `scheduler` container | Supervisor process in `app` |

### Migration Steps

1. **Backup existing data**:
   ```bash
   ./docker/scripts/docker-manager.sh backup
   ```

2. **Stop legacy containers**:
   ```bash
   docker-compose down
   ```

3. **Start new unified setup**:
   ```bash
   ./docker/scripts/docker-manager.sh start
   ```

4. **Verify functionality**:
   ```bash
   ./docker/scripts/docker-manager.sh health
   ```

## Scaling & Production Deployment

### Horizontal Scaling

For high-traffic scenarios:

1. **Load Balancer**: Use nginx/HAProxy in front of multiple app containers
2. **Database**: Consider read replicas and connection pooling
3. **Redis**: Implement Redis Cluster for queue scaling
4. **File Storage**: Use S3/MinIO for shared storage

### Container Orchestration

#### Docker Swarm
```bash
# Deploy stack
docker stack deploy -c docker-compose.yml -c docker-compose.prod.yml acme-csr
```

#### Kubernetes
Use the provided health check endpoints for:
- **Readiness Probe**: `/health/ready`
- **Liveness Probe**: `/health/live`
- **Metrics**: `/metrics` (Prometheus)

### CI/CD Integration

Example GitHub Actions workflow:

```yaml
- name: Deploy to Production
  run: |
    ./docker/scripts/docker-manager.sh build production
    ./docker/scripts/docker-manager.sh start production
    ./docker/scripts/docker-manager.sh health
```

## Additional Resources

- [FrankenPHP Documentation](https://frankenphp.dev)
- [Laravel Octane Documentation](https://laravel.com/docs/octane)
- [Laravel Horizon Documentation](https://laravel.com/docs/horizon)
- [Supervisor Documentation](http://supervisord.org)

## Support

For issues related to the Docker setup:

1. Check the health endpoints first
2. Review the logs using the manager script
3. Consult this documentation
4. Create an issue with logs and configuration details

---

**Developed and Maintained by Go2digit.al**

Specialized in enterprise-grade applications with focus on scalability, security, and maintainability.

Copyright 2025 Go2digit.al - All Rights Reserved