#!/bin/bash

# ================================================================
# ACME Corp CSR Platform - Docker Management Script
# ================================================================
# Unified Docker management for FrankenPHP + Laravel Octane + Supervisor
# Usage: ./docker/scripts/docker-manager.sh [command] [environment]

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.yml"
PROD_COMPOSE_FILE="${PROJECT_ROOT}/docker-compose.prod.yml"
APP_CONTAINER="acme-app"

# Default environment
ENVIRONMENT="${2:-local}"

# Print functions
print_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
print_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
print_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Helper functions
get_compose_command() {
    local env="${1:-local}"
    case $env in
        production|prod)
            echo "docker-compose -f ${COMPOSE_FILE} -f ${PROD_COMPOSE_FILE}"
            ;;
        staging|stage)
            echo "docker-compose -f ${COMPOSE_FILE} -f ${PROD_COMPOSE_FILE}"
            ;;
        *)
            echo "docker-compose -f ${COMPOSE_FILE}"
            ;;
    esac
}

wait_for_health() {
    local service="$1"
    local timeout="${2:-60}"
    local interval=5
    local elapsed=0
    
    print_info "Waiting for $service to be healthy..."
    
    while [ $elapsed -lt $timeout ]; do
        if docker-compose ps $service | grep -q "healthy\|Up"; then
            print_success "$service is healthy"
            return 0
        fi
        sleep $interval
        elapsed=$((elapsed + interval))
        echo -n "."
    done
    
    print_error "$service failed to become healthy within ${timeout}s"
    return 1
}

# Command functions
cmd_start() {
    local env="${1:-local}"
    local compose_cmd
    compose_cmd=$(get_compose_command "$env")
    
    print_info "Starting ACME CSR Platform in $env environment..."
    
    case $env in
        production|prod)
            print_info "Using production configuration with optimizations"
            ;;
        staging|stage)
            print_info "Using staging configuration"
            export APP_ENV=staging
            export APP_DEBUG=false
            ;;
        *)
            print_info "Using development configuration"
            export APP_ENV=local
            export APP_DEBUG=true
            ;;
    esac
    
    # Start services
    $compose_cmd up -d
    
    # Wait for critical services
    wait_for_health mysql 60
    wait_for_health redis 30
    wait_for_health meilisearch 30
    
    print_success "ACME CSR Platform started successfully!"
    print_info "Access points:"
    print_info "  - Application: http://localhost"
    print_info "  - Horizon: http://localhost/admin/horizon"
    print_info "  - Mailpit: http://localhost:8025"
    print_info "  - RedisInsight: http://localhost:5540"
    print_info "  - Meilisearch: http://localhost:7700"
}

cmd_stop() {
    local env="${1:-local}"
    local compose_cmd
    compose_cmd=$(get_compose_command "$env")
    
    print_info "Stopping ACME CSR Platform..."
    $compose_cmd down
    print_success "Platform stopped successfully"
}

cmd_restart() {
    local env="${1:-local}"
    print_info "Restarting ACME CSR Platform..."
    cmd_stop "$env"
    sleep 2
    cmd_start "$env"
}

cmd_status() {
    local env="${1:-local}"
    local compose_cmd
    compose_cmd=$(get_compose_command "$env")
    
    print_info "ACME CSR Platform Status:"
    $compose_cmd ps
    
    print_info "\nContainer Resource Usage:"
    docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}\t{{.BlockIO}}"
}

cmd_logs() {
    local service="${1:-app}"
    local env="${2:-local}"
    local compose_cmd
    compose_cmd=$(get_compose_command "$env")
    
    print_info "Showing logs for $service..."
    $compose_cmd logs -f "$service"
}

cmd_shell() {
    local env="${1:-local}"
    print_info "Opening shell in application container..."
    docker exec -it $APP_CONTAINER /bin/sh
}

cmd_artisan() {
    local cmd="$1"
    shift
    print_info "Running artisan command: $cmd $*"
    docker exec -it $APP_CONTAINER php artisan "$cmd" "$@"
}

cmd_composer() {
    local cmd="$1"
    shift
    print_info "Running composer command: $cmd $*"
    docker exec -it $APP_CONTAINER composer "$cmd" "$@"
}

cmd_npm() {
    local cmd="$1"
    shift
    print_info "Running npm command: $cmd $*"
    docker exec -it $APP_CONTAINER npm "$cmd" "$@"
}

cmd_horizon() {
    print_info "Opening Horizon dashboard..."
    docker exec -it $APP_CONTAINER supervisorctl status horizon
    print_info "Access Horizon at: http://localhost/admin/horizon"
}

cmd_queues() {
    print_info "Queue worker status:"
    docker exec -it $APP_CONTAINER supervisorctl status | grep queue
    
    print_info "\nQueue statistics:"
    docker exec -it $APP_CONTAINER php artisan horizon:status
}

cmd_health() {
    print_info "Checking application health..."
    
    # Check container health
    if docker exec $APP_CONTAINER curl -sf http://localhost/health > /dev/null; then
        print_success "Application health check passed"
    else
        print_error "Application health check failed"
        return 1
    fi
    
    # Check supervisor processes
    print_info "\nSupervisor process status:"
    docker exec $APP_CONTAINER supervisorctl status
    
    # Check database connectivity
    print_info "\nDatabase connectivity:"
    if docker exec $APP_CONTAINER php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database: Connected';" 2>/dev/null; then
        print_success "Database connection successful"
    else
        print_error "Database connection failed"
    fi
    
    # Check Redis connectivity
    print_info "\nRedis connectivity:"
    if docker exec $APP_CONTAINER php artisan tinker --execute="Redis::ping(); echo 'Redis: Connected';" 2>/dev/null; then
        print_success "Redis connection successful"
    else
        print_error "Redis connection failed"
    fi
}

cmd_build() {
    local env="${1:-local}"
    local compose_cmd
    compose_cmd=$(get_compose_command "$env")
    
    print_info "Building application container for $env environment..."
    
    case $env in
        production|prod)
            $compose_cmd build --no-cache app
            ;;
        *)
            $compose_cmd build app
            ;;
    esac
    
    print_success "Build completed successfully"
}

cmd_reset() {
    local env="${1:-local}"
    local compose_cmd
    compose_cmd=$(get_compose_command "$env")
    
    print_warning "This will destroy all containers and volumes. Are you sure? (y/N)"
    read -r confirmation
    
    if [[ $confirmation =~ ^[Yy]$ ]]; then
        print_info "Resetting ACME CSR Platform..."
        $compose_cmd down -v --remove-orphans
        docker system prune -f
        print_success "Platform reset completed"
    else
        print_info "Reset cancelled"
    fi
}

cmd_backup() {
    local env="${1:-local}"
    local timestamp
    timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_dir="${PROJECT_ROOT}/backups/${timestamp}"
    
    print_info "Creating backup for $env environment..."
    mkdir -p "$backup_dir"
    
    # Backup database
    print_info "Backing up MySQL database..."
    docker exec acme-mysql mysqldump -u root -proot --all-databases > "$backup_dir/mysql_backup.sql"
    
    # Backup Redis
    print_info "Backing up Redis data..."
    docker exec acme-redis redis-cli BGSAVE
    docker cp acme-redis:/data/dump.rdb "$backup_dir/redis_backup.rdb"
    
    # Backup Meilisearch
    print_info "Backing up Meilisearch data..."
    docker exec acme-meilisearch tar czf /tmp/meilisearch_backup.tar.gz /meili_data
    docker cp acme-meilisearch:/tmp/meilisearch_backup.tar.gz "$backup_dir/"
    
    # Backup application storage
    print_info "Backing up application storage..."
    tar czf "$backup_dir/storage_backup.tar.gz" -C "$PROJECT_ROOT" storage
    
    print_success "Backup completed: $backup_dir"
}

cmd_help() {
    cat << EOF
ACME Corp CSR Platform - Docker Management Script

Usage: $0 [command] [environment]

Commands:
  start [env]           Start the platform (default: local)
  stop [env]            Stop the platform
  restart [env]         Restart the platform
  status [env]          Show platform status
  logs [service] [env]  Show logs for a service (default: app)
  shell [env]           Open shell in application container
  artisan [cmd] [args]  Run Laravel Artisan command
  composer [cmd] [args] Run Composer command
  npm [cmd] [args]      Run NPM command
  horizon               Show Horizon status and open dashboard
  queues                Show queue worker status
  health                Run comprehensive health checks
  build [env]           Build application container
  reset [env]           Reset platform (destroys all data)
  backup [env]          Create backup of all data
  help                  Show this help message

Environments:
  local                 Development environment (default)
  staging               Staging environment
  production            Production environment

Examples:
  $0 start                    # Start in development mode
  $0 start production         # Start in production mode
  $0 logs app                 # Show application logs
  $0 artisan migrate          # Run database migrations
  $0 composer install         # Install dependencies
  $0 health                   # Check platform health
  $0 backup production        # Backup production data

EOF
}

# Main execution
main() {
    cd "$PROJECT_ROOT"
    
    local command="${1:-help}"
    
    case $command in
        start)
            cmd_start "$ENVIRONMENT"
            ;;
        stop)
            cmd_stop "$ENVIRONMENT"
            ;;
        restart)
            cmd_restart "$ENVIRONMENT"
            ;;
        status)
            cmd_status "$ENVIRONMENT"
            ;;
        logs)
            cmd_logs "${2:-app}" "$ENVIRONMENT"
            ;;
        shell)
            cmd_shell "$ENVIRONMENT"
            ;;
        artisan)
            shift
            cmd_artisan "$@"
            ;;
        composer)
            shift
            cmd_composer "$@"
            ;;
        npm)
            shift
            cmd_npm "$@"
            ;;
        horizon)
            cmd_horizon
            ;;
        queues)
            cmd_queues
            ;;
        health)
            cmd_health
            ;;
        build)
            cmd_build "$ENVIRONMENT"
            ;;
        reset)
            cmd_reset "$ENVIRONMENT"
            ;;
        backup)
            cmd_backup "$ENVIRONMENT"
            ;;
        help|--help|-h)
            cmd_help
            ;;
        *)
            print_error "Unknown command: $command"
            cmd_help
            exit 1
            ;;
    esac
}

# Run main function with all arguments
main "$@"