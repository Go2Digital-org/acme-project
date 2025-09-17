#!/usr/bin/env bash

# =============================================================================
# check-migrations.sh
#
# Validates migration/schema consistency for ACME Corp CSR Platform
#
# Usage: ./scripts/check-migrations.sh [options]
#   -h, --help     Show this help message
#   -v, --verbose  Enable verbose output
#   -q, --quiet    Suppress non-critical output
#
# =============================================================================

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
SCHEMA_DIR="${PROJECT_ROOT}/database/schema"
MIGRATIONS_DIR="${PROJECT_ROOT}/database/migrations"
VERBOSE=false
QUIET=false
ERRORS_FOUND=0

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -h|--help)
            grep "^# " "$0" | sed 's/^# //'
            exit 0
            ;;
        -v|--verbose)
            VERBOSE=true
            shift
            ;;
        -q|--quiet)
            QUIET=true
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
    if [[ "$QUIET" != "true" ]]; then
        echo -e "$1"
    fi
}

log_verbose() {
    if [[ "$VERBOSE" == "true" ]]; then
        echo -e "$1"
    fi
}

log_error() {
    echo -e "${RED}✗ $1${NC}" >&2
    ERRORS_FOUND=$((ERRORS_FOUND + 1))
}

log_success() {
    log "${GREEN}✓ $1${NC}"
}

log_warning() {
    log "${YELLOW}⚠ $1${NC}"
}

# =============================================================================
# 1. Check Schema Files Exist
# =============================================================================
check_schema_files() {
    log "\n📋 Checking schema files..."

    local main_schema="${SCHEMA_DIR}/mysql-schema.sql"
    local test_schema="${SCHEMA_DIR}/mysql-testing-schema.sql"

    if [[ ! -f "$main_schema" ]]; then
        log_error "Main schema file not found: $main_schema"
        log "  Run: php artisan schema:dump"
    else
        log_success "Main schema file exists"
        log_verbose "  Path: $main_schema"
        log_verbose "  Size: $(du -h "$main_schema" | cut -f1)"
    fi

    if [[ ! -f "$test_schema" ]]; then
        log_warning "Testing schema file not found: $test_schema"
        log "  Create with: cp $main_schema $test_schema"
    else
        log_success "Testing schema file exists"
        log_verbose "  Path: $test_schema"
    fi
}

# =============================================================================
# 2. Verify Schema Contains Required Columns
# =============================================================================
check_required_columns() {
    log "\n🔍 Checking for required columns in schema..."

    local schema_file="${SCHEMA_DIR}/mysql-schema.sql"

    if [[ ! -f "$schema_file" ]]; then
        log_warning "Schema file not found, skipping column check"
        return
    fi

    # Check for campaigns table columns
    local required_columns=(
        "views_count.*int.*unsigned"
        "shares_count.*int.*unsigned"
        "goal_percentage.*decimal\(8,2\)"
    )

    for column_pattern in "${required_columns[@]}"; do
        if grep -qE "$column_pattern" "$schema_file"; then
            log_success "Found required column pattern: ${column_pattern%%.*}"
        else
            log_error "Missing column pattern in schema: ${column_pattern%%.*}"
        fi
    done

    # Check for LEAST function in goal_percentage
    if grep -q "LEAST" "$schema_file"; then
        log_success "Found LEAST() function in goal_percentage calculation"
    else
        log_warning "Missing LEAST() function in goal_percentage - may cause overflow"
    fi
}

# =============================================================================
# 3. Check for DST-Unsafe Date Generation
# =============================================================================
check_dst_safety() {
    log "\n⏰ Checking for DST-unsafe date generation patterns..."

    local unsafe_patterns=(
        "fake\(\)->dateTime\(\)"
        "fake\(\)->dateTimeBetween.*['\"]0[2-3]:"
        "Carbon::.*->setTime\(2,"
        "Carbon::.*->setTime\(3,"
    )

    local factories_dir="${PROJECT_ROOT}/database/factories"
    local modules_factories="${PROJECT_ROOT}/modules"

    local unsafe_files=()

    # Check database/factories
    if [[ -d "$factories_dir" ]]; then
        for pattern in "${unsafe_patterns[@]}"; do
            while IFS= read -r file; do
                if [[ ! " ${unsafe_files[@]} " =~ " ${file} " ]]; then
                    unsafe_files+=("$file")
                fi
            done < <(grep -l "$pattern" "$factories_dir"/*.php 2>/dev/null || true)
        done
    fi

    # Check module factories
    if [[ -d "$modules_factories" ]]; then
        for pattern in "${unsafe_patterns[@]}"; do
            while IFS= read -r file; do
                if [[ ! " ${unsafe_files[@]} " =~ " ${file} " ]]; then
                    unsafe_files+=("$file")
                fi
            done < <(find "$modules_factories" -name "*Factory.php" -exec grep -l "$pattern" {} \; 2>/dev/null || true)
        done
    fi

    if [[ ${#unsafe_files[@]} -eq 0 ]]; then
        log_success "No DST-unsafe date patterns found in factories"
    else
        log_warning "Found potentially unsafe date generation in ${#unsafe_files[@]} file(s):"
        for file in "${unsafe_files[@]}"; do
            log "  - $(basename "$file")"
            if [[ "$VERBOSE" == "true" ]]; then
                grep -n -E "$(IFS='|'; echo "${unsafe_patterns[*]}")" "$file" | head -3
            fi
        done
        log "  Consider using SafeDateGeneration trait or safeDateTimeBetween() method"
    fi
}

# =============================================================================
# 4. Check Migration Files vs Schema
# =============================================================================
check_migration_consistency() {
    log "\n🔄 Checking migration consistency..."

    local schema_file="${SCHEMA_DIR}/mysql-schema.sql"

    if [[ ! -f "$schema_file" ]]; then
        log_warning "Schema file not found, skipping migration check"
        return
    fi

    # Count migrations in directory
    local migration_count=$(find "$MIGRATIONS_DIR" -name "*.php" 2>/dev/null | wc -l | tr -d ' ')

    # Count migrations in schema dump
    local schema_migrations=$(grep -c "INSERT INTO \`migrations\`" "$schema_file" 2>/dev/null || echo "0")

    log_verbose "  Migration files: $migration_count"
    log_verbose "  Migrations in schema: $schema_migrations"

    if [[ $migration_count -gt $schema_migrations ]]; then
        local diff=$((migration_count - schema_migrations))
        log_warning "Found $diff migration(s) not included in schema dump"
        log "  Run: php artisan schema:dump to update"

        if [[ "$VERBOSE" == "true" ]]; then
            log "\n  Recent migrations not in schema:"
            find "$MIGRATIONS_DIR" -name "*.php" -newer "$schema_file" -exec basename {} \; | head -5
        fi
    else
        log_success "All migrations are included in schema dump"
    fi
}

# =============================================================================
# 5. Check for Schema Drift
# =============================================================================
check_schema_drift() {
    log "\n🎯 Checking for schema drift..."

    # This would ideally connect to the database and compare
    # For now, we check modification times

    local schema_file="${SCHEMA_DIR}/mysql-schema.sql"

    if [[ ! -f "$schema_file" ]]; then
        return
    fi

    # Find the most recent migration
    local latest_migration=$(find "$MIGRATIONS_DIR" -name "*.php" -type f -exec stat -f "%m %N" {} \; 2>/dev/null | sort -rn | head -1 | cut -d' ' -f2-)

    if [[ -n "$latest_migration" ]]; then
        # Compare timestamps
        if [[ "$latest_migration" -nt "$schema_file" ]]; then
            log_warning "Schema dump is older than latest migration"
            log "  Latest migration: $(basename "$latest_migration")"
            log "  Consider running: php artisan schema:dump"
        else
            log_success "Schema dump is up-to-date with migrations"
        fi
    fi
}

# =============================================================================
# 6. Validate Schema SQL Syntax
# =============================================================================
validate_schema_syntax() {
    log "\n✅ Validating schema SQL syntax..."

    local schema_file="${SCHEMA_DIR}/mysql-schema.sql"

    if [[ ! -f "$schema_file" ]]; then
        return
    fi

    # Basic SQL syntax checks
    local syntax_errors=0

    # Check for unclosed quotes
    if grep -E "^[^']*'([^']*'[^']*')*[^']*$" "$schema_file" | grep -v "^--" | grep -v "^/\*" > /dev/null 2>&1; then
        log_warning "Potential unclosed quotes detected in schema"
        syntax_errors=$((syntax_errors + 1))
    fi

    # Check for missing semicolons (basic check)
    if grep -E "^\s*(CREATE|ALTER|DROP|INSERT)\s+" "$schema_file" | grep -v ";" > /dev/null 2>&1; then
        log_warning "Potential missing semicolons in SQL statements"
        syntax_errors=$((syntax_errors + 1))
    fi

    if [[ $syntax_errors -eq 0 ]]; then
        log_success "No obvious SQL syntax errors detected"
    else
        log_warning "Found $syntax_errors potential syntax issue(s)"
    fi
}

# =============================================================================
# Main Execution
# =============================================================================
main() {
    log "🔍 ACME Corp CSR Platform - Migration & Schema Validation"
    log "========================================================="

    cd "$PROJECT_ROOT"

    check_schema_files
    check_required_columns
    check_dst_safety
    check_migration_consistency
    check_schema_drift
    validate_schema_syntax

    log "\n========================================================="

    if [[ $ERRORS_FOUND -gt 0 ]]; then
        log_error "Found $ERRORS_FOUND error(s) that need attention"
        exit 1
    else
        log_success "All checks passed successfully! ✨"
    fi
}

# Run main function
main "$@"