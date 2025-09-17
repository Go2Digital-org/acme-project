# Go2digit.al ACME Corp CSR Platform - Deployment Guide

## Overview

This project is configured for automated deployment to a Hetzner server using:
- **FrankenPHP** with worker mode for high performance
- **Nginx** as reverse proxy (preserving existing sites)
- **GitHub Actions** for CI/CD
- **Certbot** for SSL certificates
- **Zero-downtime** deployments with automatic rollback

## Quick Start

### 1. Set up GitHub Secrets (One-time setup)

```bash
# Run the setup script
./scripts/setup-github-secrets.sh

# This will:
# - Generate deployment SSH keys
# - Configure all GitHub secrets via CLI
# - Show you the public key to add to your server
```

### 2. Configure Your Server

```bash
# SSH to your Hetzner server
ssh root@78.47.63.156

# Download and run the setup script
wget https://raw.githubusercontent.com/go2digit-al/acme-corp-optimy/main/scripts/server-setup.sh
chmod +x server-setup.sh
./server-setup.sh

# This will install:
# - FrankenPHP with PHP 8.4
# - Nginx (without affecting existing sites)
# - MySQL, Redis
# - Certbot for SSL
```

### 3. Deploy Your Application

```bash
# Deploy to staging
gh workflow run deploy-hetzner.yml -f environment=staging

# Deploy to production
gh workflow run deploy-hetzner.yml -f environment=production

# Or use the interactive script
./scripts/deploy.sh
```

## Architecture

```
Internet

Nginx (Port 443/80)
(Reverse Proxy)
FrankenPHP
    ├── Production (Port 8080)
    └── Staging (Port 8081)
```

## FrankenPHP Features

### Worker Mode
- **4 workers** for production (2 for staging)
- Long-lived processes handle multiple requests
- Automatic worker recycling after 1000 requests or 1 hour
- Graceful reloads for zero-downtime deployments

### OPcache Preloading
- Framework files preloaded into memory
- Application models and controllers preloaded
- Significantly faster request handling

### Performance Configuration
- HTTP/2 support
- Zstd and Gzip compression
- Static file caching
- WebSocket support

## Deployment Workflow

### Automatic Deployments
- **Push to `main`** Deploy to production
- **Push to `dev`** Deploy to staging

### Manual Deployments

#### Via GitHub CLI
```bash
# Deploy production
gh workflow run deploy-hetzner.yml -f environment=production -f action=deploy

# Deploy staging
gh workflow run deploy-hetzner.yml -f environment=staging -f action=deploy

# Rollback production
gh workflow run deploy-hetzner.yml -f environment=production -f action=rollback

# Check status
gh workflow run deploy-hetzner.yml -f environment=production -f action=status

# Reload FrankenPHP
gh workflow run deploy-hetzner.yml -f environment=production -f action=reload

# Renew SSL certificates
gh workflow run deploy-hetzner.yml -f environment=production -f action=renew-ssl
```

#### Via GitHub Actions UI
1. Go to Actions tab in GitHub
2. Select "Deploy to Hetzner Server"
3. Click "Run workflow"
4. Choose options:
   - Environment (production/staging)
   - Action (deploy/rollback/reload/status)
   - Run migrations (yes/no)
   - Clear cache (yes/no)

### Interactive Deployment Script
```bash
./scripts/deploy.sh
```

This provides a menu with all deployment options.

## Directory Structure

### On Server
```
/var/www/
├── acme-corp/                    # Production
│   ├── current -> releases/...   # Symlink to current release
│   ├── releases/
│   │   ├── 20240110120000/      # Timestamped releases
│   │   └── 20240110110000/
│   ├── storage/                  # Shared storage
│   └── .env                      # Environment file
└── acme-staging/                 # Staging (same structure)
```

### In Repository
```
.github/workflows/
├── deploy-hetzner.yml            # Main deployment workflow

scripts/
├── setup-github-secrets.sh       # GitHub secrets setup
├── server-setup.sh              # Server preparation
└── deploy.sh                    # Interactive deployment

frankenphp/
├── worker.php                   # Worker mode script
├── preload.php                  # OPcache preloading
├── Caddyfile.production        # Production config
└── Caddyfile.staging           # Staging config

configs/nginx/
├── acme-corp.conf              # Production Nginx config
└── acme-staging.conf           # Staging Nginx config
```

## SSL Certificates

### Initial Setup
After DNS is configured, generate certificates:
```bash
# On the server
certbot --nginx -d acme-corp.com -d www.acme-corp.com
certbot --nginx -d staging.acme-corp.com
```

### Auto-renewal
Certificates auto-renew via cron job. Manual renewal:
```bash
gh workflow run deploy-hetzner.yml -f action=renew-ssl
```

## Monitoring

### Health Checks
- Production: https://acme-corp.com/health
- Staging: https://staging.acme-corp.com/health
- Nginx: https://acme-corp.com/nginx-health

### Logs
```bash
# On server
# FrankenPHP logs
journalctl -u frankenphp-production -f
journalctl -u frankenphp-staging -f

# Nginx logs
tail -f /var/log/nginx/acme-corp_access.log
tail -f /var/log/nginx/acme-corp_error.log

# Application logs
tail -f /var/www/acme-corp/storage/logs/laravel.log
```

### Metrics
FrankenPHP exposes Prometheus metrics at `/metrics` endpoint.

## Rollback

### Automatic Rollback
Deployment automatically rolls back if health checks fail.

### Manual Rollback
```bash
# Via GitHub CLI
gh workflow run deploy-hetzner.yml -f environment=production -f action=rollback

# On server (emergency)
cd /var/www/acme-corp
ln -nfs releases/[previous-timestamp] current
systemctl reload frankenphp-production
```

## Security

### GitHub Secrets
All sensitive data stored in GitHub Secrets:
- `HETZNER_HOST`: Server IP
- `HETZNER_USER`: SSH user
- `HETZNER_SSH_KEY`: Deployment SSH key
- `HETZNER_PORT`: SSH port

### Server Security
- SSL/TLS with Let's Encrypt
- Security headers configured
- Firewall rules (UFW)
- No credentials in codebase

## Troubleshooting

### Deployment Fails
1. Check GitHub Actions logs
2. Verify SSH key is added to server
3. Check server disk space
4. Verify all services are running

### FrankenPHP Won't Start
```bash
# Check service status
systemctl status frankenphp-production

# Check logs
journalctl -u frankenphp-production -n 100

# Test configuration
/usr/local/bin/frankenphp run --config /path/to/Caddyfile --validate
```

### SSL Issues
```bash
# Test certificate renewal
certbot renew --dry-run

# Force renewal
certbot renew --force-renewal
```

### Performance Issues
1. Check worker count matches CPU cores
2. Verify OPcache is enabled
3. Check Redis connection
4. Monitor memory usage

## Advanced Configuration

### Adjust Worker Count
Edit `frankenphp/Caddyfile.production`:
```caddyfile
frankenphp {
    worker ./frankenphp/worker.php 8  # Increase for more CPUs
}
```

### Enable Basic Auth for Staging
Uncomment in `configs/nginx/acme-staging.conf`:
```nginx
auth_basic "Staging Environment";
auth_basic_user_file /etc/nginx/.htpasswd.staging;
```

Create password file:
```bash
htpasswd -c /etc/nginx/.htpasswd.staging username
```

### Custom Domain Setup
1. Update DNS to point to server
2. Update GitHub secrets
3. Modify Nginx configs
4. Generate SSL certificate
5. Deploy application

## Support

For issues or questions:
1. Check deployment logs in GitHub Actions
2. Review server logs
3. Run status check: `gh workflow run deploy-hetzner.yml -f action=status`

---

**Developed and Maintained by Go2digit.al**

Specialized in enterprise-grade applications with focus on scalability, security, and maintainability.

Copyright 2025 Go2digit.al - All Rights Reserved