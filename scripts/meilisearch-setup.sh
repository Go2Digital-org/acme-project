#!/bin/bash
# ================================================================
# ACME Corp CSR Platform - Meilisearch Index Setup Script
# ================================================================
# Creates and configures Meilisearch indexes for the platform
# Supports multi-tenancy with tenant-aware indexing

set -euo pipefail

# Configuration
MEILISEARCH_HOST="${MEILISEARCH_HOST:-http://meilisearch:7700}"
MEILISEARCH_KEY="${MEILISEARCH_KEY}"
if [ -z "$MEILISEARCH_KEY" ]; then
    echo "Error: MEILISEARCH_KEY environment variable is required"
    exit 1
fi
APP_ENV="${APP_ENV:-local}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Print functions
print_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
print_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
print_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Wait for Meilisearch to be ready
wait_for_meilisearch() {
    local max_attempts=30
    local attempt=0

    print_info "Waiting for Meilisearch to be ready at ${MEILISEARCH_HOST}..."

    while [ $attempt -lt $max_attempts ]; do
        if curl -sf "${MEILISEARCH_HOST}/health" > /dev/null; then
            print_success "Meilisearch is ready!"
            return 0
        fi

        attempt=$((attempt + 1))
        sleep 2
        echo -n "."
    done

    print_error "Meilisearch failed to become ready after ${max_attempts} attempts"
    return 1
}

# Create or update an index with settings
create_index() {
    local index_name="$1"
    local primary_key="$2"

    print_info "Creating index: ${index_name}"

    # Create index
    curl -s -X POST "${MEILISEARCH_HOST}/indexes" \
        -H "Authorization: Bearer ${MEILISEARCH_KEY}" \
        -H "Content-Type: application/json" \
        -d "{\"uid\": \"${index_name}\", \"primaryKey\": \"${primary_key}\"}" \
        > /dev/null 2>&1 || true

    # Wait for task to complete
    sleep 1

    print_success "Index ${index_name} created/updated"
}

# Configure index settings
configure_index() {
    local index_name="$1"

    print_info "Configuring settings for index: ${index_name}"

    # Configure searchable attributes
    curl -s -X PATCH "${MEILISEARCH_HOST}/indexes/${index_name}/settings/searchable-attributes" \
        -H "Authorization: Bearer ${MEILISEARCH_KEY}" \
        -H "Content-Type: application/json" \
        -d "$2" > /dev/null

    # Configure filterable attributes
    curl -s -X PATCH "${MEILISEARCH_HOST}/indexes/${index_name}/settings/filterable-attributes" \
        -H "Authorization: Bearer ${MEILISEARCH_KEY}" \
        -H "Content-Type: application/json" \
        -d "$3" > /dev/null

    # Configure sortable attributes
    curl -s -X PATCH "${MEILISEARCH_HOST}/indexes/${index_name}/settings/sortable-attributes" \
        -H "Authorization: Bearer ${MEILISEARCH_KEY}" \
        -H "Content-Type: application/json" \
        -d "$4" > /dev/null

    # Configure displayed attributes
    if [ -n "${5:-}" ]; then
        curl -s -X PATCH "${MEILISEARCH_HOST}/indexes/${index_name}/settings/displayed-attributes" \
            -H "Authorization: Bearer ${MEILISEARCH_KEY}" \
            -H "Content-Type: application/json" \
            -d "$5" > /dev/null
    fi

    # Configure ranking rules
    if [ -n "${6:-}" ]; then
        curl -s -X PATCH "${MEILISEARCH_HOST}/indexes/${index_name}/settings/ranking-rules" \
            -H "Authorization: Bearer ${MEILISEARCH_KEY}" \
            -H "Content-Type: application/json" \
            -d "$6" > /dev/null
    fi

    print_success "Index ${index_name} configured"
}

# Setup central indexes (shared across all tenants)
setup_central_indexes() {
    print_info "Setting up central indexes..."

    # Users index
    create_index "users" "id"
    configure_index "users" \
        '["name", "email", "bio"]' \
        '["role", "status", "organization_id", "created_at"]' \
        '["created_at", "name"]' \
        '["id", "name", "email", "role", "organization_id", "avatar_url", "created_at"]' \
        '["words", "typo", "proximity", "attribute", "sort", "exactness"]'

    # Organizations index
    create_index "organizations" "id"
    configure_index "organizations" \
        '["name", "description", "website"]' \
        '["status", "type", "country", "created_at"]' \
        '["created_at", "name", "total_donations"]' \
        '["id", "name", "description", "logo_url", "website", "country", "status", "created_at"]'

    # FAQ/Help index
    create_index "help_articles" "id"
    configure_index "help_articles" \
        '["title", "content", "tags"]' \
        '["category", "status", "language"]' \
        '["created_at", "views", "helpful_count"]' \
        '["id", "title", "excerpt", "category", "tags", "created_at"]'

    print_success "Central indexes setup completed"
}

# Setup tenant-specific indexes
setup_tenant_indexes() {
    local tenant_id="${1:-}"

    if [ -z "$tenant_id" ]; then
        print_warning "No tenant ID provided, skipping tenant index setup"
        return 0
    fi

    print_info "Setting up indexes for tenant: ${tenant_id}"

    local prefix="tenant_${tenant_id}_"

    # Campaigns index (tenant-specific)
    create_index "${prefix}campaigns" "id"
    configure_index "${prefix}campaigns" \
        '["title", "description", "organization_name", "tags"]' \
        '["status", "category", "target_amount", "current_amount", "start_date", "end_date", "organization_id"]' \
        '["created_at", "current_amount", "end_date", "popularity"]' \
        '["id", "title", "description", "organization_name", "image_url", "target_amount", "current_amount", "status", "start_date", "end_date"]' \
        '["words", "typo", "proximity", "attribute", "sort", "exactness", "current_amount:desc"]'

    # Donations index (tenant-specific)
    create_index "${prefix}donations" "id"
    configure_index "${prefix}donations" \
        '["donor_name", "donor_email", "campaign_title", "reference_number"]' \
        '["status", "payment_method", "amount", "created_at", "campaign_id", "donor_id"]' \
        '["created_at", "amount"]' \
        '["id", "reference_number", "donor_name", "campaign_title", "amount", "status", "payment_method", "created_at"]'

    # Reports index (tenant-specific)
    create_index "${prefix}reports" "id"
    configure_index "${prefix}reports" \
        '["title", "description", "type"]' \
        '["status", "type", "period", "created_at"]' \
        '["created_at", "period"]' \
        '["id", "title", "type", "status", "period", "file_url", "created_at"]'

    # Activities/Audit log index (tenant-specific)
    create_index "${prefix}activities" "id"
    configure_index "${prefix}activities" \
        '["description", "user_name", "resource_type"]' \
        '["action", "user_id", "resource_type", "resource_id", "created_at"]' \
        '["created_at"]' \
        '["id", "description", "user_name", "action", "resource_type", "created_at"]'

    print_success "Tenant ${tenant_id} indexes setup completed"
}

# List all tenants from database
get_all_tenants() {
    # This would normally query the database for all tenant IDs
    # For now, we'll use environment variable or return empty
    echo "${TENANT_IDS:-}"
}

# Main execution
main() {
    print_info "Starting Meilisearch setup for ACME CSR Platform..."
    print_info "Environment: ${APP_ENV}"
    print_info "Meilisearch Host: ${MEILISEARCH_HOST}"

    # Wait for Meilisearch
    if ! wait_for_meilisearch; then
        print_error "Failed to connect to Meilisearch"
        exit 1
    fi

    # Setup central indexes
    setup_central_indexes

    # Setup tenant indexes if in production or if tenants exist
    if [ "$APP_ENV" == "production" ] || [ "$APP_ENV" == "staging" ]; then
        print_info "Setting up tenant-specific indexes..."

        # Get all tenant IDs
        tenant_ids=$(get_all_tenants)

        if [ -n "$tenant_ids" ]; then
            # Loop through each tenant
            IFS=',' read -ra TENANTS <<< "$tenant_ids"
            for tenant_id in "${TENANTS[@]}"; do
                setup_tenant_indexes "$tenant_id"
            done
        else
            print_warning "No tenants found to setup indexes for"
        fi
    else
        print_info "Skipping tenant index setup in ${APP_ENV} environment"
    fi

    # Verify setup
    print_info "Verifying Meilisearch setup..."

    # Get index stats
    indexes=$(curl -s -H "Authorization: Bearer ${MEILISEARCH_KEY}" \
        "${MEILISEARCH_HOST}/indexes" | grep -o '"uid"' | wc -l)

    print_success "Meilisearch setup completed with ${indexes} indexes"

    # Output index information
    if [ "$APP_ENV" == "local" ] || [ "$APP_ENV" == "development" ]; then
        print_info "Access Meilisearch at: ${MEILISEARCH_HOST}"
        print_info "API Key: ${MEILISEARCH_KEY}"
    fi
}

# Run main function
main "$@"