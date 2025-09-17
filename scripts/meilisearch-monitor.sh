#!/bin/bash

# ================================================================
# ACME Corp CSR Platform - Meilisearch Monitoring Script
# ================================================================
# This script monitors Meilisearch health, queue status, and index
# statistics for the ACME Corp CSR platform.
#
# Usage:
#   ./scripts/meilisearch-monitor.sh [OPTIONS]
#
# Options:
#   --json         Output results in JSON format
#   --quiet        Suppress normal output (useful for cron jobs)
#   --check=TYPE   Run specific check (health|queue|indexes|workers|all)
#   --threshold=N  Set queue threshold for alerts (default: 1000)
#   --help         Show help message
#
# Exit Codes:
#   0  - All checks passed
#   1  - General error
#   2  - Meilisearch connection failed
#   3  - Queue backlog threshold exceeded
#   4  - Scout workers not running
#   5  - Index health issues detected
#
# Environment Variables:
#   MEILISEARCH_HOST     - Meilisearch server URL
#   MEILISEARCH_KEY      - Meilisearch master key
#   SCOUT_PREFIX         - Index prefix (default: acme_)
#   MONITOR_QUEUE_THRESHOLD - Queue backlog threshold (default: 1000)
# ================================================================

set -euo pipefail

# Colors for output
readonly RED='\033[0;31m'
readonly GREEN='\033[0;32m'
readonly YELLOW='\033[1;33m'
readonly BLUE='\033[0;34m'
readonly NC='\033[0m' # No Color

# Script configuration
readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Default values
JSON_OUTPUT=false
QUIET_MODE=false
CHECK_TYPE="all"
QUEUE_THRESHOLD=1000
TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

# Global status tracking
OVERALL_STATUS="healthy"
ISSUES_FOUND=()
CHECK_RESULTS=()

# Output functions
output() {
    if [[ "$QUIET_MODE" != "true" ]]; then
        echo -e "$*"
    fi
}

info() {
    output "${BLUE}[INFO]${NC} $*"
}

success() {
    output "${GREEN}[OK]${NC} $*"
}

warning() {
    output "${YELLOW}[WARNING]${NC} $*"
    OVERALL_STATUS="warning"
    ISSUES_FOUND+=("WARNING: $*")
}

error() {
    output "${RED}[ERROR]${NC} $*"
    OVERALL_STATUS="error"
    ISSUES_FOUND+=("ERROR: $*")
}

# JSON output helpers
add_check_result() {
    local check_name="$1"
    local status="$2"
    local message="$3"
    local details="${4:-{}}"
    
    local result="{\"check\":\"$check_name\",\"status\":\"$status\",\"message\":\"$message\",\"details\":$details,\"timestamp\":\"$TIMESTAMP\"}"
    CHECK_RESULTS+=("$result")
}

# Help function
show_help() {
    cat << EOF
ACME Corp CSR Platform - Meilisearch Monitoring Script

USAGE:
    $0 [OPTIONS]

OPTIONS:
    --json              Output results in JSON format
    --quiet             Suppress normal output (useful for cron jobs)
    --check=TYPE        Run specific check (health|queue|indexes|workers|all)
    --threshold=N       Set queue threshold for alerts (default: 1000)
    --help              Show this help message

CHECK TYPES:
    health              Check Meilisearch connectivity and health
    queue               Check Laravel queue depth and status
    indexes             Check index statistics and health
    workers             Check if scout-indexer workers are running
    all                 Run all checks (default)

EXIT CODES:
    0                   All checks passed
    1                   General error
    2                   Meilisearch connection failed
    3                   Queue backlog threshold exceeded
    4                   Scout workers not running
    5                   Index health issues detected

EXAMPLES:
    $0                              # Run all checks with normal output
    $0 --json                       # Run all checks with JSON output
    $0 --check=health --quiet       # Check only Meilisearch health silently
    $0 --threshold=500              # Use custom queue threshold

EOF
}

# Parse command line arguments
parse_args() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --json)
                JSON_OUTPUT=true
                shift
                ;;
            --quiet)
                QUIET_MODE=true
                shift
                ;;
            --check=*)
                CHECK_TYPE="${1#*=}"
                shift
                ;;
            --threshold=*)
                QUEUE_THRESHOLD="${1#*=}"
                shift
                ;;
            --help)
                show_help
                exit 0
                ;;
            *)
                echo "Unknown option: $1. Use --help for usage information." >&2
                exit 1
                ;;
        esac
    done
    
    # Validate check type
    case $CHECK_TYPE in
        health|queue|indexes|workers|all)
            ;;
        *)
            echo "Invalid check type: $CHECK_TYPE. Use --help for valid options." >&2
            exit 1
            ;;
    esac
}

# Load environment variables
load_environment() {
    # Change to project root
    cd "$PROJECT_ROOT"
    
    # Source the .env file if it exists
    if [[ -f ".env" ]]; then
        set -a  # Export all variables
        source .env
        set +a  # Stop exporting
    fi
    
    # Set defaults
    MEILISEARCH_HOST="${MEILISEARCH_HOST:-http://localhost:7700}"
    SCOUT_PREFIX="${SCOUT_PREFIX:-acme_}"
    QUEUE_THRESHOLD="${MONITOR_QUEUE_THRESHOLD:-$QUEUE_THRESHOLD}"
}

# Check Meilisearch health
check_meilisearch_health() {
    info "Checking Meilisearch health..."
    
    local health_url="$MEILISEARCH_HOST/health"
    local stats_url="$MEILISEARCH_HOST/stats"
    local auth_header=""
    
    if [[ -n "${MEILISEARCH_KEY:-}" ]]; then
        auth_header="-H Authorization: Bearer $MEILISEARCH_KEY"
    fi
    
    # Check basic connectivity
    local health_response
    if ! health_response=$(curl -s -w "%{http_code}" $auth_header "$health_url" 2>/dev/null); then
        error "Cannot connect to Meilisearch at $MEILISEARCH_HOST"
        add_check_result "meilisearch_health" "error" "Connection failed" "{\"url\":\"$MEILISEARCH_HOST\"}"
        return 2
    fi
    
    local http_code="${health_response: -3}"
    local health_body="${health_response%???}"
    
    if [[ "$http_code" != "200" ]]; then
        error "Meilisearch health check failed (HTTP $http_code)"
        add_check_result "meilisearch_health" "error" "Health check failed" "{\"http_code\":$http_code}"
        return 2
    fi
    
    # Get detailed stats
    local stats_response
    if stats_response=$(curl -s $auth_header "$stats_url" 2>/dev/null); then
        local database_size indexes_count
        database_size=$(echo "$stats_response" | grep -o '"databaseSize":[0-9]*' | cut -d':' -f2 || echo "0")
        indexes_count=$(echo "$stats_response" | grep -o '"indexes":{[^}]*}' | grep -o '"[^"]*":[0-9]*' | wc -l || echo "0")
        
        success "Meilisearch is healthy (Database: ${database_size} bytes, Indexes: ${indexes_count})"
        add_check_result "meilisearch_health" "healthy" "Meilisearch is responsive" "{\"database_size\":$database_size,\"indexes_count\":$indexes_count}"
    else
        warning "Meilisearch is responsive but stats unavailable"
        add_check_result "meilisearch_health" "warning" "Stats unavailable" "{}"
    fi
    
    return 0
}

# Check Laravel queue status
check_queue_status() {
    info "Checking Laravel queue status..."
    
    # Check if Laravel is available
    if ! php artisan --version >/dev/null 2>&1; then
        error "Laravel artisan not available"
        add_check_result "queue_status" "error" "Laravel not available" "{}"
        return 1
    fi
    
    # Get queue status using Redis (assuming Redis queue driver)
    local queue_size=0
    local failed_jobs=0
    
    # Try to get queue size from Redis
    if command -v redis-cli >/dev/null 2>&1; then
        local redis_host="${REDIS_HOST:-127.0.0.1}"
        local redis_port="${REDIS_PORT:-6379}"
        local redis_db="${REDIS_QUEUE_DB:-2}"
        
        # Get queue sizes for different queues
        local default_queue="$SCOUT_PREFIX:queue:default"
        local high_queue="$SCOUT_PREFIX:queue:high"
        
        if redis-cli -h "$redis_host" -p "$redis_port" -n "$redis_db" ping >/dev/null 2>&1; then
            local default_size high_size
            default_size=$(redis-cli -h "$redis_host" -p "$redis_port" -n "$redis_db" llen "$default_queue" 2>/dev/null || echo "0")
            high_size=$(redis-cli -h "$redis_host" -p "$redis_port" -n "$redis_db" llen "$high_queue" 2>/dev/null || echo "0")
            queue_size=$((default_size + high_size))
            
            # Get failed jobs count
            failed_jobs=$(redis-cli -h "$redis_host" -p "$redis_port" -n "$redis_db" llen "failed_jobs" 2>/dev/null || echo "0")
        fi
    fi
    
    # Check queue threshold
    if [[ $queue_size -gt $QUEUE_THRESHOLD ]]; then
        error "Queue backlog exceeded threshold: $queue_size > $QUEUE_THRESHOLD"
        add_check_result "queue_status" "error" "Queue backlog too high" "{\"queue_size\":$queue_size,\"threshold\":$QUEUE_THRESHOLD,\"failed_jobs\":$failed_jobs}"
        return 3
    elif [[ $queue_size -gt $((QUEUE_THRESHOLD / 2)) ]]; then
        warning "Queue backlog approaching threshold: $queue_size (threshold: $QUEUE_THRESHOLD)"
        add_check_result "queue_status" "warning" "Queue backlog high" "{\"queue_size\":$queue_size,\"threshold\":$QUEUE_THRESHOLD,\"failed_jobs\":$failed_jobs}"
    else
        success "Queue status OK (Size: $queue_size, Failed: $failed_jobs)"
        add_check_result "queue_status" "healthy" "Queue size within limits" "{\"queue_size\":$queue_size,\"threshold\":$QUEUE_THRESHOLD,\"failed_jobs\":$failed_jobs}"
    fi
    
    return 0
}

# Check index statistics
check_index_statistics() {
    info "Checking index statistics..."
    
    local auth_header=""
    if [[ -n "${MEILISEARCH_KEY:-}" ]]; then
        auth_header="-H Authorization: Bearer $MEILISEARCH_KEY"
    fi
    
    # Get list of indexes
    local indexes_response
    if ! indexes_response=$(curl -s $auth_header "$MEILISEARCH_HOST/indexes" 2>/dev/null); then
        error "Failed to get indexes list"
        add_check_result "index_statistics" "error" "Cannot retrieve indexes" "{}"
        return 5
    fi
    
    # Parse index names (simplified parsing)
    local index_count=0
    local total_documents=0
    local index_details="["
    
    # Expected indexes based on scout configuration
    local expected_indexes=("campaigns" "donations" "users" "organizations" "employees")
    local found_indexes=()
    
    for expected_index in "${expected_indexes[@]}"; do
        local full_index_name="${SCOUT_PREFIX}${expected_index}"
        local stats_url="$MEILISEARCH_HOST/indexes/$full_index_name/stats"
        
        local stats_response
        if stats_response=$(curl -s $auth_header "$stats_url" 2>/dev/null); then
            local doc_count
            doc_count=$(echo "$stats_response" | grep -o '"numberOfDocuments":[0-9]*' | cut -d':' -f2 || echo "0")
            
            if [[ $doc_count -gt 0 ]]; then
                found_indexes+=("$expected_index")
                total_documents=$((total_documents + doc_count))
                
                if [[ $index_count -gt 0 ]]; then
                    index_details+=","
                fi
                index_details+="{\"name\":\"$expected_index\",\"documents\":$doc_count}"
                ((index_count++))
            fi
        fi
    done
    
    index_details+="]"
    
    # Check results
    if [[ $index_count -eq 0 ]]; then
        error "No active indexes found"
        add_check_result "index_statistics" "error" "No indexes found" "{\"expected\":${#expected_indexes[@]},\"found\":0}"
        return 5
    elif [[ $index_count -lt ${#expected_indexes[@]} ]]; then
        warning "Some indexes missing or empty (Found: $index_count/${#expected_indexes[@]})"
        add_check_result "index_statistics" "warning" "Some indexes missing" "{\"expected\":${#expected_indexes[@]},\"found\":$index_count,\"indexes\":$index_details}"
    else
        success "All indexes healthy (Indexes: $index_count, Documents: $total_documents)"
        add_check_result "index_statistics" "healthy" "All indexes present" "{\"expected\":${#expected_indexes[@]},\"found\":$index_count,\"total_documents\":$total_documents,\"indexes\":$index_details}"
    fi
    
    return 0
}

# Check scout workers
check_scout_workers() {
    info "Checking scout workers..."
    
    # Check if Laravel Horizon is running (if using Horizon)
    local horizon_status="unknown"
    if php artisan horizon:status >/dev/null 2>&1; then
        if php artisan horizon:status | grep -q "running"; then
            horizon_status="running"
            success "Laravel Horizon is running"
        else
            horizon_status="stopped"
            warning "Laravel Horizon is not running"
        fi
    fi
    
    # Check for queue workers in process list
    local worker_count=0
    if command -v pgrep >/dev/null 2>&1; then
        worker_count=$(pgrep -f "queue:work\|horizon" | wc -l || echo "0")
    fi
    
    # Check supervisor processes if available
    local supervisor_status="unknown"
    if command -v supervisorctl >/dev/null 2>&1; then
        local supervisor_output
        if supervisor_output=$(supervisorctl status 2>/dev/null); then
            if echo "$supervisor_output" | grep -q "RUNNING"; then
                supervisor_status="running"
            else
                supervisor_status="stopped"
            fi
        fi
    fi
    
    # Evaluate worker status
    if [[ "$horizon_status" == "running" ]] || [[ $worker_count -gt 0 ]]; then
        success "Queue workers are active (Workers: $worker_count, Horizon: $horizon_status)"
        add_check_result "scout_workers" "healthy" "Workers are running" "{\"worker_count\":$worker_count,\"horizon_status\":\"$horizon_status\",\"supervisor_status\":\"$supervisor_status\"}"
    elif [[ "$horizon_status" == "stopped" ]] && [[ $worker_count -eq 0 ]]; then
        error "No queue workers detected"
        add_check_result "scout_workers" "error" "No workers running" "{\"worker_count\":$worker_count,\"horizon_status\":\"$horizon_status\",\"supervisor_status\":\"$supervisor_status\"}"
        return 4
    else
        warning "Queue worker status unclear"
        add_check_result "scout_workers" "warning" "Worker status unclear" "{\"worker_count\":$worker_count,\"horizon_status\":\"$horizon_status\",\"supervisor_status\":\"$supervisor_status\"}"
    fi
    
    return 0
}

# Output results
output_results() {
    if [[ "$JSON_OUTPUT" == "true" ]]; then
        # JSON output
        local json_results=$(IFS=,; echo "[${CHECK_RESULTS[*]}]")
        local json_issues=""
        if [[ ${#ISSUES_FOUND[@]} -gt 0 ]]; then
            json_issues=$(printf '%s\n' "${ISSUES_FOUND[@]}" | sed 's/"/\\"/g' | sed 's/.*/"&"/' | paste -sd ',' -)
        fi
        
        cat << EOF
{
  "status": "$OVERALL_STATUS",
  "timestamp": "$TIMESTAMP",
  "checks": $json_results,
  "issues": [$json_issues],
  "summary": {
    "total_checks": ${#CHECK_RESULTS[@]},
    "issues_found": ${#ISSUES_FOUND[@]}
  }
}
EOF
    else
        # Human-readable output
        echo
        info "=== Monitoring Summary ==="
        info "Overall Status: $OVERALL_STATUS"
        info "Checks Run: ${#CHECK_RESULTS[@]}"
        
        if [[ ${#ISSUES_FOUND[@]} -gt 0 ]]; then
            echo
            warning "Issues Found (${#ISSUES_FOUND[@]}):"
            for issue in "${ISSUES_FOUND[@]}"; do
                echo "  - $issue"
            done
        fi
        
        echo
        info "Monitoring completed at $TIMESTAMP"
    fi
}

# Determine exit code based on overall status
get_exit_code() {
    case $OVERALL_STATUS in
        "healthy")
            return 0
            ;;
        "warning")
            return 0  # Warnings don't fail health checks
            ;;
        "error")
            # Return specific error codes based on issues
            for issue in "${ISSUES_FOUND[@]}"; do
                if [[ "$issue" == *"Connection failed"* ]] || [[ "$issue" == *"Health check failed"* ]]; then
                    return 2
                elif [[ "$issue" == *"Queue backlog"* ]]; then
                    return 3
                elif [[ "$issue" == *"workers"* ]]; then
                    return 4
                elif [[ "$issue" == *"indexes"* ]]; then
                    return 5
                fi
            done
            return 1  # General error
            ;;
        *)
            return 1
            ;;
    esac
}

# Main execution function
main() {
    parse_args "$@"
    load_environment
    
    # Run requested checks
    local exit_code=0
    
    if [[ "$CHECK_TYPE" == "all" ]] || [[ "$CHECK_TYPE" == "health" ]]; then
        check_meilisearch_health || exit_code=$?
    fi
    
    if [[ "$CHECK_TYPE" == "all" ]] || [[ "$CHECK_TYPE" == "queue" ]]; then
        check_queue_status || exit_code=$?
    fi
    
    if [[ "$CHECK_TYPE" == "all" ]] || [[ "$CHECK_TYPE" == "indexes" ]]; then
        check_index_statistics || exit_code=$?
    fi
    
    if [[ "$CHECK_TYPE" == "all" ]] || [[ "$CHECK_TYPE" == "workers" ]]; then
        check_scout_workers || exit_code=$?
    fi
    
    # Output results
    output_results
    
    # Exit with appropriate code
    get_exit_code
}

# Execute main function with all arguments
main "$@"