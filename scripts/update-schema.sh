#!/usr/bin/env bash

# =============================================================================
# update-schema.sh
#
# Automates Laravel schema dump generation and maintenance
# for ACME Corp CSR Platform
#
# Usage: ./scripts/update-schema.sh [options]
#   -h, --help      Show this help message
#   -f, --force     Skip confirmation prompts
#   -b, --backup    Create backup even if schema is current
#   -t, --testing   Also generate testing schema dump
#   -p, --prune     Prune migration records from dump
#   --no-backup     Skip backup creation
#
# =============================================================================

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
SCHEMA_DIR="${PROJECT_ROOT}/database/schema"
BACKUP_DIR="${SCHEMA_DIR}/backups"
MAX_BACKUPS=10

# Options
FORCE=false
CREATE_BACKUP=true
UPDATE_TESTING=false
PRUNE_MIGRATIONS=false

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -h|--help)
            grep "^# " "$0" | sed 's/^# //'
            exit 0
            ;;
        -f|--force)
            FORCE=true
            shift
            ;;
        -b|--backup)
            CREATE_BACKUP=true
            shift
            ;;
        -t|--testing)
            UPDATE_TESTING=true
            shift
            ;;
        -p|--prune)
            PRUNE_MIGRATIONS=true
            shift
            ;;
        --no-backup)
            CREATE_BACKUP=false
            shift
            ;;
        *)
            echo "Unknown option: $1"
            exit 1
            ;;
    esac
done

# Helper functions
log() {
    echo -e "$1"
}

log_error() {
    echo -e "${RED}✗ $1${NC}" >&2
}

log_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

log_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

log_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

confirm() {
    if [[ "$FORCE" == "true" ]]; then
        return 0
    fi

    local prompt="$1"
    local response

    echo -en "${YELLOW}$prompt (y/N): ${NC}"
    read -r response

    case "$response" in
        [yY][eE][sS]|[yY])
            return 0
            ;;
        *)
            return 1
            ;;
    esac
}

# =============================================================================
# Environment Detection
# =============================================================================
detect_environment() {
    if [[ -f "${PROJECT_ROOT}/.env" ]]; then
        source "${PROJECT_ROOT}/.env"
    fi

    # Detect if we're in CI environment
    if [[ "${CI:-false}" == "true" ]] || [[ "${GITHUB_ACTIONS:-false}" == "true" ]]; then
        log_info "CI environment detected"
        FORCE=true
    fi
}

# =============================================================================
# Backup Management
# =============================================================================
create_backup() {
    if [[ "$CREATE_BACKUP" != "true" ]]; then
        log_info "Skipping backup (--no-backup specified)"
        return
    fi

    local schema_file="$1"
    local backup_name="$(basename "$schema_file" .sql)-$(date +%Y%m%d-%H%M%S).sql"

    if [[ ! -f "$schema_file" ]]; then
        log_info "No existing schema to backup"
        return
    fi

    # Create backup directory if it doesn't exist
    mkdir -p "$BACKUP_DIR"

    log "📦 Creating backup..."
    cp "$schema_file" "${BACKUP_DIR}/${backup_name}"
    log_success "Backup created: ${backup_name}"

    # Clean old backups
    clean_old_backups
}

clean_old_backups() {
    local backup_count=$(ls -1 "$BACKUP_DIR" 2>/dev/null | wc -l | tr -d ' ')

    if [[ $backup_count -gt $MAX_BACKUPS ]]; then
        log_info "Cleaning old backups (keeping last $MAX_BACKUPS)..."

        # Remove oldest backups
        ls -1t "$BACKUP_DIR" | tail -n +$((MAX_BACKUPS + 1)) | while read -r old_backup; do
            rm "${BACKUP_DIR}/${old_backup}"
            log "  Removed: $old_backup"
        done
    fi
}

# =============================================================================
# Schema Validation
# =============================================================================
validate_schema() {
    local schema_file="$1"

    if [[ ! -f "$schema_file" ]]; then
        return 1
    fi

    log "🔍 Validating schema..."

    # Check for required columns
    local validation_passed=true

    # Check campaigns table has required columns
    if ! grep -q "views_count.*int.*unsigned" "$schema_file"; then
        log_error "Missing views_count column"
        validation_passed=false
    fi

    if ! grep -q "shares_count.*int.*unsigned" "$schema_file"; then
        log_error "Missing shares_count column"
        validation_passed=false
    fi

    if ! grep -q "goal_percentage.*decimal(8,2)" "$schema_file"; then
        log_error "Missing or incorrect goal_percentage column"
        validation_passed=false
    fi

    if ! grep -q "LEAST" "$schema_file"; then
        log_warning "Missing LEAST() function in goal_percentage"
    fi

    if [[ "$validation_passed" == "true" ]]; then
        log_success "Schema validation passed"
        return 0
    else
        log_error "Schema validation failed"
        return 1
    fi
}

# =============================================================================
# Schema Generation
# =============================================================================
generate_main_schema() {
    log "\n🔨 Generating main database schema..."

    cd "$PROJECT_ROOT"

    # Ensure migrations are up-to-date
    log_info "Running pending migrations..."
    php artisan migrate --force

    # Generate schema dump
    log_info "Dumping database schema..."

    if [[ "$PRUNE_MIGRATIONS" == "true" ]]; then
        php artisan schema:dump --prune
        log_success "Schema generated with migration pruning"
    else
        php artisan schema:dump
        log_success "Schema generated successfully"
    fi

    # Validate the generated schema
    if validate_schema "${SCHEMA_DIR}/mysql-schema.sql"; then
        log_success "Main schema updated successfully"
    else
        log_error "Schema generation completed with validation warnings"
    fi
}

generate_testing_schema() {
    if [[ "$UPDATE_TESTING" != "true" ]]; then
        return
    fi

    log "\n🧪 Generating testing database schema..."

    local main_schema="${SCHEMA_DIR}/mysql-schema.sql"
    local test_schema="${SCHEMA_DIR}/mysql-testing-schema.sql"

    if [[ ! -f "$main_schema" ]]; then
        log_error "Main schema not found. Generate it first."
        return 1
    fi

    # Create testing schema from main schema
    cp "$main_schema" "$test_schema"
    log_success "Testing schema created from main schema"
}

# =============================================================================
# Pre-flight Checks
# =============================================================================
preflight_checks() {
    log "✈️  Running pre-flight checks..."

    # Check PHP is available
    if ! command -v php &> /dev/null; then
        log_error "PHP is not installed or not in PATH"
        exit 1
    fi

    # Check Laravel artisan exists
    if [[ ! -f "${PROJECT_ROOT}/artisan" ]]; then
        log_error "Laravel artisan not found. Are you in the project root?"
        exit 1
    fi

    # Check database connection
    if ! php artisan migrate:status &> /dev/null; then
        log_warning "Cannot connect to database. Check your .env configuration"

        if ! confirm "Continue anyway?"; then
            exit 1
        fi
    fi

    # Ensure schema directory exists
    mkdir -p "$SCHEMA_DIR"

    log_success "Pre-flight checks passed"
}

# =============================================================================
# Main Execution
# =============================================================================
main() {
    log "🚀 ACME Corp CSR Platform - Schema Update Tool"
    log "================================================"

    cd "$PROJECT_ROOT"

    # Detect environment
    detect_environment

    # Run pre-flight checks
    preflight_checks

    # Check if update is needed
    local main_schema="${SCHEMA_DIR}/mysql-schema.sql"
    local needs_update=false

    if [[ ! -f "$main_schema" ]]; then
        log_warning "Schema file does not exist"
        needs_update=true
    else
        # Check if any migrations are newer than schema
        local newest_migration=$(find "${PROJECT_ROOT}/database/migrations" -name "*.php" -newer "$main_schema" 2>/dev/null | head -1)

        if [[ -n "$newest_migration" ]]; then
            log_warning "Found migrations newer than schema dump"
            log "  Latest: $(basename "$newest_migration")"
            needs_update=true
        else
            log_success "Schema is up-to-date"
        fi
    fi

    if [[ "$needs_update" == "false" ]] && [[ "$FORCE" != "true" ]]; then
        log_info "No update needed. Use --force to update anyway."
        exit 0
    fi

    # Confirm update
    if ! confirm "Update schema dump(s) now?"; then
        log "Update cancelled"
        exit 0
    fi

    # Create backup
    create_backup "$main_schema"

    # Generate main schema
    generate_main_schema

    # Generate testing schema if requested
    generate_testing_schema

    # Final validation
    log "\n📊 Summary"
    log "=========="

    if [[ -f "$main_schema" ]]; then
        log_success "Main schema: $(du -h "$main_schema" | cut -f1)"
    fi

    if [[ -f "${SCHEMA_DIR}/mysql-testing-schema.sql" ]]; then
        log_success "Test schema: $(du -h "${SCHEMA_DIR}/mysql-testing-schema.sql" | cut -f1)"
    fi

    log_success "Schema update completed successfully! ✨"

    log "\n💡 Next steps:"
    log "  - Run tests: ./vendor/bin/pest"
    log "  - Validate: ./scripts/check-migrations.sh"
    log "  - Commit changes: git add database/schema && git commit"
}

# Run main function
main "$@"