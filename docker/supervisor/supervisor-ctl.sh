#!/bin/bash

# ACME Corp CSR Platform - Supervisor Management Script
# Provides convenient commands for managing the unified container

set -e

CONTAINER_NAME="acme-csr-unified"
COMPOSE_FILE="docker/supervisor/docker-compose.unified.yml"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_header() {
    echo -e "${BLUE}=== ACME Corp CSR Platform - Supervisor Management ===${NC}"
}

print_usage() {
    echo -e "${YELLOW}Usage: $0 <command> [options]${NC}"
    echo ""
    echo "Commands:"
    echo "  start [env]          Start the unified container (production|staging|local)"
    echo "  stop                 Stop the unified container"
    echo "  restart [env]        Restart the unified container"
    echo "  status               Show all process status"
    echo "  logs [process]       Show logs (all processes or specific process)"
    echo "  exec <command>       Execute command in container"
    echo "  shell                Open bash shell in container"
    echo "  workers              Show queue worker status"
    echo "  horizon              Show Horizon dashboard URL"
    echo "  scale <process> <count>  Scale specific process"
    echo "  health               Check container health"
    echo "  build [env]          Build the unified container"
    echo ""
    echo "Process Names:"
    echo "  frankenphp           Web server"
    echo "  horizon              Queue monitoring"
    echo "  scheduler            Laravel scheduler"
    echo "  queue-payments       Payment queue workers"
    echo "  queue-notifications  Notification queue workers"
    echo "  queue-exports        Export queue workers"
    echo "  queue-reports        Report queue workers"
    echo "  queue-default        Default queue workers"
    echo "  queue-bulk           Bulk processing workers"
    echo "  queue-maintenance    Maintenance workers"
    echo "  queue-cache-warming  Cache warming workers"
}

check_container() {
    if ! docker ps -q -f name="$CONTAINER_NAME" | grep -q .; then
        echo -e "${RED}Container $CONTAINER_NAME is not running${NC}"
        return 1
    fi
    return 0
}

cmd_start() {
    local env=${1:-production}
    print_header
    echo -e "${GREEN}Starting ACME CSR Platform in $env mode...${NC}"
    
    APP_ENV=$env docker-compose -f "$COMPOSE_FILE" up -d
    
    echo -e "${GREEN}Waiting for container to be healthy...${NC}"
    timeout 120 bash -c 'until docker-compose -f "'$COMPOSE_FILE'" ps | grep -q "healthy"; do sleep 2; done'
    
    echo -e "${GREEN}Container started successfully!${NC}"
    cmd_status
}

cmd_stop() {
    print_header
    echo -e "${YELLOW}Stopping ACME CSR Platform...${NC}"
    docker-compose -f "$COMPOSE_FILE" down
    echo -e "${GREEN}Container stopped${NC}"
}

cmd_restart() {
    local env=${1:-production}
    cmd_stop
    sleep 2
    cmd_start "$env"
}

cmd_status() {
    print_header
    echo -e "${BLUE}Container Status:${NC}"
    docker-compose -f "$COMPOSE_FILE" ps
    echo ""
    
    if check_container; then
        echo -e "${BLUE}Process Status:${NC}"
        docker exec "$CONTAINER_NAME" supervisorctl status
    fi
}

cmd_logs() {
    local process=${1:-}
    print_header
    
    if [ -z "$process" ]; then
        echo -e "${BLUE}Showing all container logs:${NC}"
        docker-compose -f "$COMPOSE_FILE" logs -f --tail=100
    else
        if check_container; then
            echo -e "${BLUE}Showing logs for process: $process${NC}"
            docker exec "$CONTAINER_NAME" supervisorctl tail -f "$process"
        fi
    fi
}

cmd_exec() {
    if [ $# -eq 0 ]; then
        echo -e "${RED}Please provide a command to execute${NC}"
        return 1
    fi
    
    if check_container; then
        docker exec -it "$CONTAINER_NAME" "$@"
    fi
}

cmd_shell() {
    if check_container; then
        echo -e "${BLUE}Opening shell in container...${NC}"
        docker exec -it "$CONTAINER_NAME" bash
    fi
}

cmd_workers() {
    print_header
    if check_container; then
        echo -e "${BLUE}Queue Worker Status:${NC}"
        docker exec "$CONTAINER_NAME" supervisorctl status | grep queue-
        echo ""
        echo -e "${BLUE}Queue Statistics:${NC}"
        docker exec "$CONTAINER_NAME" php artisan queue:monitor redis:default,payments,notifications,exports,reports,bulk,maintenance
    fi
}

cmd_horizon() {
    print_header
    if check_container; then
        local port=$(docker-compose -f "$COMPOSE_FILE" port app 80 2>/dev/null | cut -d: -f2)
        echo -e "${GREEN}Horizon Dashboard: http://localhost:${port}/admin/horizon${NC}"
        echo -e "${BLUE}Admin Panel: http://localhost:${port}/admin${NC}"
    fi
}

cmd_scale() {
    local process=$1
    local count=$2
    
    if [ -z "$process" ] || [ -z "$count" ]; then
        echo -e "${RED}Usage: $0 scale <process> <count>${NC}"
        return 1
    fi
    
    if check_container; then
        echo -e "${BLUE}Scaling $process to $count processes...${NC}"
        # This would require dynamic supervisor configuration
        echo -e "${YELLOW}Dynamic scaling requires container restart with new environment variables${NC}"
        echo -e "${YELLOW}Edit docker/supervisor/env/[environment].env and restart container${NC}"
    fi
}

cmd_health() {
    print_header
    if check_container; then
        echo -e "${BLUE}Container Health Check:${NC}"
        docker exec "$CONTAINER_NAME" /usr/local/bin/healthcheck
        echo ""
        echo -e "${BLUE}Laravel Health Check:${NC}"
        docker exec "$CONTAINER_NAME" php artisan health:check
    fi
}

cmd_build() {
    local env=${1:-production}
    print_header
    echo -e "${BLUE}Building unified container for $env environment...${NC}"
    APP_ENV=$env docker-compose -f "$COMPOSE_FILE" build --no-cache
    echo -e "${GREEN}Build completed${NC}"
}

# Main command handler
case "${1:-}" in
    start)
        cmd_start "${2:-}"
        ;;
    stop)
        cmd_stop
        ;;
    restart)
        cmd_restart "${2:-}"
        ;;
    status)
        cmd_status
        ;;
    logs)
        cmd_logs "${2:-}"
        ;;
    exec)
        shift
        cmd_exec "$@"
        ;;
    shell)
        cmd_shell
        ;;
    workers)
        cmd_workers
        ;;
    horizon)
        cmd_horizon
        ;;
    scale)
        cmd_scale "$2" "$3"
        ;;
    health)
        cmd_health
        ;;
    build)
        cmd_build "${2:-}"
        ;;
    *)
        print_usage
        exit 1
        ;;
esac