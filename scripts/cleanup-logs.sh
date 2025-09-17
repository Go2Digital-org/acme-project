#!/bin/bash

# ACME Corp CSR Platform - Log Cleanup Script
# This script maintains log files and prevents them from growing too large

set -e

# Configuration
PROJECT_ROOT="/Users/vin/projects/acme-corp-optimy"
LOG_DIR="${PROJECT_ROOT}/storage/logs"
ARCHIVE_DIR="${PROJECT_ROOT}/storage/archives/logs"
MAX_LOG_LINES=1000
MAX_LOG_AGE_DAYS=30

echo "=== ACME Corp CSR Platform - Log Cleanup Script ==="
echo "Started at: $(date)"

# Create archive directory if it doesn't exist
mkdir -p "${ARCHIVE_DIR}"

cd "${PROJECT_ROOT}"

# Archive old log files (older than 30 days)
echo "Archiving old log files..."
find "${LOG_DIR}" -name "*.log" -mtime +${MAX_LOG_AGE_DAYS} -exec mv {} "${ARCHIVE_DIR}/" \;

# Truncate large laravel.log if it exists and is too large
if [ -f "${LOG_DIR}/laravel.log" ]; then
    LOG_LINES=$(wc -l < "${LOG_DIR}/laravel.log")
    if [ ${LOG_LINES} -gt ${MAX_LOG_LINES} ]; then
        echo "Truncating laravel.log (${LOG_LINES} lines) to ${MAX_LOG_LINES} lines..."
        # Backup first
        cp "${LOG_DIR}/laravel.log" "${ARCHIVE_DIR}/laravel-$(date +%Y%m%d-%H%M%S).log"
        # Keep only last lines
        tail -n ${MAX_LOG_LINES} "${LOG_DIR}/laravel.log" > "${LOG_DIR}/laravel.log.tmp"
        mv "${LOG_DIR}/laravel.log.tmp" "${LOG_DIR}/laravel.log"
        echo "Laravel log truncated successfully"
    else
        echo "Laravel log is within size limits (${LOG_LINES} lines)"
    fi
fi

# Clean up old auth logs (keep only current and previous day)
echo "Cleaning up old auth logs..."
find "${LOG_DIR}" -name "auth-*.log" -mtime +1 -exec mv {} "${ARCHIVE_DIR}/" \;

# Show storage usage
echo ""
echo "=== Current Log Directory Size ==="
du -sh "${LOG_DIR}"

echo ""
echo "=== Archive Directory Size ==="
du -sh "${ARCHIVE_DIR}" 2>/dev/null || echo "No archives yet"

echo ""
echo "Log cleanup completed at: $(date)"