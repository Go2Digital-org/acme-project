#!/bin/sh
# ACME Corp CSR Platform - Health Check Script
# Comprehensive health checks for the application

set -eo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "🏥 Starting health checks..."

# Check if Nginx is responding
if ! curl -sf http://localhost/health > /dev/null; then
    echo -e "${RED}❌ Nginx health check failed${NC}"
    exit 1
fi

# Check if PHP-FPM is running
if ! pgrep -f "php-fpm" > /dev/null; then
    echo -e "${RED}❌ PHP-FPM is not running${NC}"
    exit 1
fi

# Check if Laravel application is responding
if ! curl -sf http://localhost > /dev/null; then
    echo -e "${RED}❌ Laravel application is not responding${NC}"
    exit 1
fi

# Check database connectivity (if available)
if command -v mysql > /dev/null; then
    if ! timeout 5 mysql -h"${DB_HOST:-mysql}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-password}" -e "SELECT 1" > /dev/null 2>&1; then
        echo -e "${YELLOW}⚠️  Database connection check failed (this may be expected in some environments)${NC}"
    fi
fi

# Check Redis connectivity (if available)
if command -v redis-cli > /dev/null; then
    if ! timeout 5 redis-cli -h "${REDIS_HOST:-redis}" -p "${REDIS_PORT:-6379}" ping > /dev/null 2>&1; then
        echo -e "${YELLOW}⚠️  Redis connection check failed (this may be expected in some environments)${NC}"
    fi
fi

# Check if supervisor processes are running
if command -v supervisorctl > /dev/null; then
    if ! supervisorctl status | grep -q "RUNNING"; then
        echo -e "${RED}❌ Some supervisor processes are not running${NC}"
        exit 1
    fi
fi

# Check disk space
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 90 ]; then
    echo -e "${YELLOW}⚠️  Disk usage is high: ${DISK_USAGE}%${NC}"
fi

# Check memory usage
if command -v free > /dev/null; then
    MEMORY_USAGE=$(free | grep Mem | awk '{printf("%.0f", $3/$2 * 100.0)}')
    if [ "$MEMORY_USAGE" -gt 90 ]; then
        echo -e "${YELLOW}⚠️  Memory usage is high: ${MEMORY_USAGE}%${NC}"
    fi
fi

echo -e "${GREEN}✅ All health checks passed${NC}"
exit 0