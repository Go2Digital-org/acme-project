#!/bin/bash

# ACME Corp CSR Platform - Export Cleanup Script
# This script maintains export files and prevents storage bloat

set -e

# Configuration
PROJECT_ROOT="/Users/vin/projects/acme-corp-optimy"
EXPORTS_DIR="${PROJECT_ROOT}/storage/app/private/exports/final"
ARCHIVE_DIR="${PROJECT_ROOT}/storage/archives/exports"
KEEP_RECENT=5  # Keep only the 5 most recent exports
MAX_EXPORT_AGE_DAYS=30

echo "=== ACME Corp CSR Platform - Export Cleanup Script ==="
echo "Started at: $(date)"

# Create archive directory if it doesn't exist
mkdir -p "${ARCHIVE_DIR}"

cd "${PROJECT_ROOT}"

# Check if exports directory exists
if [ ! -d "${EXPORTS_DIR}" ]; then
    echo "Exports directory does not exist: ${EXPORTS_DIR}"
    exit 0
fi

# Count current exports
EXPORT_COUNT=$(ls -1 "${EXPORTS_DIR}"/*.csv 2>/dev/null | wc -l || echo 0)
echo "Current export count: ${EXPORT_COUNT}"

if [ ${EXPORT_COUNT} -gt ${KEEP_RECENT} ]; then
    echo "Archiving old exports (keeping ${KEEP_RECENT} most recent)..."
    
    # Archive older exports (keep only the N most recent)
    cd "${EXPORTS_DIR}"
    ls -t *.csv | tail -n +$((KEEP_RECENT + 1)) | while read file; do
        echo "Archiving: ${file}"
        mv "${file}" "${ARCHIVE_DIR}/"
    done
    
    cd "${PROJECT_ROOT}"
else
    echo "Export count is within limits (${EXPORT_COUNT} <= ${KEEP_RECENT})"
fi

# Clean up very old exports from archive (older than MAX_EXPORT_AGE_DAYS)
echo "Cleaning up old archived exports (older than ${MAX_EXPORT_AGE_DAYS} days)..."
find "${ARCHIVE_DIR}" -name "*.csv" -mtime +${MAX_EXPORT_AGE_DAYS} -delete

# Show storage usage
echo ""
echo "=== Current Exports Directory Size ==="
du -sh "${EXPORTS_DIR}" 2>/dev/null || echo "Directory is empty or does not exist"

echo ""
echo "=== Archive Directory Size ==="
du -sh "${ARCHIVE_DIR}" 2>/dev/null || echo "No archives yet"

echo ""
echo "Export cleanup completed at: $(date)"