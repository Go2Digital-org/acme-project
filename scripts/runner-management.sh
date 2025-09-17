#!/bin/bash

# GitHub Actions Runner Management Script
# Provides convenient commands for managing the self-hosted runner
# Usage: ./scripts/runner-management.sh <command> [args]

set -euo pipefail

# Configuration
RUNNER_USER="github-runner"
RUNNER_HOME="/home/${RUNNER_USER}"
RUNNER_DIR="${RUNNER_HOME}/actions-runner"
SERVICE_NAME="github-runner"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Logging functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

info() {
    echo -e "${CYAN}[INFO]${NC} $1"
}

# Function to check if running as root when needed
check_root() {
    if [[ $EUID -ne 0 ]]; then
        error "This command requires root privileges. Use sudo."
        exit 1
    fi
}

# Function to check if service exists
check_service_exists() {
    if ! systemctl list-unit-files | grep -q "^${SERVICE_NAME}.service"; then
        error "GitHub runner service not found. Run setup first."
        exit 1
    fi
}

# Function to display service status
show_status() {
    log "Checking GitHub Actions Runner status..."
    
    if ! systemctl list-unit-files | grep -q "^${SERVICE_NAME}.service"; then
        error "Service not installed"
        return 1
    fi
    
    echo -e "\n${PURPLE}=== SERVICE STATUS ===${NC}"
    systemctl status "$SERVICE_NAME" --no-pager -l || true
    
    echo -e "\n${PURPLE}=== SERVICE PROPERTIES ===${NC}"
    echo "Active: $(systemctl is-active "$SERVICE_NAME" 2>/dev/null || echo 'inactive')"
    echo "Enabled: $(systemctl is-enabled "$SERVICE_NAME" 2>/dev/null || echo 'disabled')"
    echo "Failed: $(systemctl is-failed "$SERVICE_NAME" 2>/dev/null || echo 'no')"
    
    echo -e "\n${PURPLE}=== RUNNER CONFIGURATION ===${NC}"
    if [[ -f "$RUNNER_DIR/.runner" ]]; then
        if command -v jq >/dev/null 2>&1; then
            jq -r '"Name: " + .agentName + "\nPool: " + .poolName + "\nServer URL: " + .serverUrl + "\nLabels: " + (.labels | join(", "))' "$RUNNER_DIR/.runner"
        else
            cat "$RUNNER_DIR/.runner"
        fi
    else
        warning "Runner configuration not found"
    fi
    
    echo -e "\n${PURPLE}=== SYSTEM RESOURCES ===${NC}"
    echo "Memory Usage: $(free -h | awk '/^Mem:/{print $3 "/" $2 " (" int($3/$2*100) "%)"}')"
    echo "CPU Load: $(uptime | awk -F'load average:' '{print $2}' | xargs)"
    echo "Disk Usage: $(df -h / | awk 'NR==2{print $3 "/" $2 " (" $5 " used)"}')"
    
    echo -e "\n${PURPLE}=== PROCESS INFORMATION ===${NC}"
    if pgrep -f "Runner.Listener" >/dev/null; then
        ps aux | grep -E "(Runner.Listener|run.sh)" | grep -v grep | while read -r line; do
            echo "  $line"
        done
    else
        warning "No runner processes found"
    fi
}

# Function to show recent logs
show_logs() {
    local lines="${1:-50}"
    
    check_service_exists
    
    log "Showing last $lines lines of runner logs..."
    echo -e "\n${PURPLE}=== RECENT LOGS ===${NC}"
    journalctl -u "$SERVICE_NAME" --no-pager -n "$lines" --output=short-precise
}

# Function to follow logs in real-time
follow_logs() {
    check_service_exists
    
    log "Following runner logs (Ctrl+C to stop)..."
    echo -e "${PURPLE}=== LIVE LOGS ===${NC}"
    journalctl -u "$SERVICE_NAME" -f --output=short-precise
}

# Function to start the runner
start_runner() {
    check_root
    check_service_exists
    
    log "Starting GitHub Actions Runner..."
    
    if systemctl is-active --quiet "$SERVICE_NAME"; then
        warning "Runner is already running"
        return 0
    fi
    
    systemctl start "$SERVICE_NAME"
    
    # Wait for service to start
    sleep 3
    
    if systemctl is-active --quiet "$SERVICE_NAME"; then
        success "Runner started successfully"
    else
        error "Failed to start runner"
        systemctl status "$SERVICE_NAME" --no-pager -l
        exit 1
    fi
}

# Function to stop the runner
stop_runner() {
    check_root
    check_service_exists
    
    log "Stopping GitHub Actions Runner..."
    
    if ! systemctl is-active --quiet "$SERVICE_NAME"; then
        warning "Runner is not running"
        return 0
    fi
    
    systemctl stop "$SERVICE_NAME"
    
    # Wait for service to stop
    sleep 3
    
    if ! systemctl is-active --quiet "$SERVICE_NAME"; then
        success "Runner stopped successfully"
    else
        error "Failed to stop runner"
        exit 1
    fi
}

# Function to restart the runner
restart_runner() {
    check_root
    check_service_exists
    
    log "Restarting GitHub Actions Runner..."
    
    systemctl restart "$SERVICE_NAME"
    
    # Wait for service to restart
    sleep 5
    
    if systemctl is-active --quiet "$SERVICE_NAME"; then
        success "Runner restarted successfully"
    else
        error "Failed to restart runner"
        systemctl status "$SERVICE_NAME" --no-pager -l
        exit 1
    fi
}

# Function to enable auto-start
enable_autostart() {
    check_root
    check_service_exists
    
    log "Enabling auto-start on boot..."
    
    systemctl enable "$SERVICE_NAME"
    
    if systemctl is-enabled --quiet "$SERVICE_NAME"; then
        success "Auto-start enabled"
    else
        error "Failed to enable auto-start"
        exit 1
    fi
}

# Function to disable auto-start
disable_autostart() {
    check_root
    check_service_exists
    
    log "Disabling auto-start on boot..."
    
    systemctl disable "$SERVICE_NAME"
    
    if ! systemctl is-enabled --quiet "$SERVICE_NAME"; then
        success "Auto-start disabled"
    else
        error "Failed to disable auto-start"
        exit 1
    fi
}

# Function to check system health
health_check() {
    log "Performing comprehensive health check..."
    
    local issues=0
    
    echo -e "\n${PURPLE}=== SYSTEM HEALTH CHECK ===${NC}"
    
    # Check service status
    if systemctl is-active --quiet "$SERVICE_NAME"; then
        echo -e "${GREEN}✓${NC} Service is running"
    else
        echo -e "${RED}✗${NC} Service is not running"
        ((issues++))
    fi
    
    # Check auto-start
    if systemctl is-enabled --quiet "$SERVICE_NAME"; then
        echo -e "${GREEN}✓${NC} Auto-start is enabled"
    else
        echo -e "${YELLOW}!${NC} Auto-start is disabled"
    fi
    
    # Check runner directory
    if [[ -d "$RUNNER_DIR" ]]; then
        echo -e "${GREEN}✓${NC} Runner directory exists"
    else
        echo -e "${RED}✗${NC} Runner directory missing"
        ((issues++))
    fi
    
    # Check configuration files
    if [[ -f "$RUNNER_DIR/.runner" ]]; then
        echo -e "${GREEN}✓${NC} Runner configuration exists"
    else
        echo -e "${RED}✗${NC} Runner configuration missing"
        ((issues++))
    fi
    
    if [[ -f "$RUNNER_DIR/.credentials" ]]; then
        echo -e "${GREEN}✓${NC} Runner credentials exist"
    else
        echo -e "${RED}✗${NC} Runner credentials missing"
        ((issues++))
    fi
    
    # Check user and permissions
    if id "$RUNNER_USER" >/dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} Runner user exists"
    else
        echo -e "${RED}✗${NC} Runner user missing"
        ((issues++))
    fi
    
    if [[ -O "$RUNNER_HOME" ]] || [[ "$(stat -c %U "$RUNNER_HOME")" == "$RUNNER_USER" ]]; then
        echo -e "${GREEN}✓${NC} Runner home permissions correct"
    else
        echo -e "${RED}✗${NC} Runner home permissions incorrect"
        ((issues++))
    fi
    
    # Check disk space
    local disk_usage
    disk_usage=$(df / | awk 'NR==2{print +$5}')
    if [[ $disk_usage -lt 90 ]]; then
        echo -e "${GREEN}✓${NC} Disk space sufficient (${disk_usage}% used)"
    else
        echo -e "${YELLOW}!${NC} Disk space low (${disk_usage}% used)"
    fi
    
    # Check memory usage
    local mem_usage
    mem_usage=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
    if [[ $mem_usage -lt 90 ]]; then
        echo -e "${GREEN}✓${NC} Memory usage normal (${mem_usage}% used)"
    else
        echo -e "${YELLOW}!${NC} Memory usage high (${mem_usage}% used)"
    fi
    
    # Check network connectivity
    if curl -s --max-time 10 https://api.github.com >/dev/null; then
        echo -e "${GREEN}✓${NC} GitHub API connectivity"
    else
        echo -e "${RED}✗${NC} GitHub API connectivity failed"
        ((issues++))
    fi
    
    echo
    if [[ $issues -eq 0 ]]; then
        success "Health check passed - no issues found"
        return 0
    else
        error "Health check failed - $issues issues found"
        return 1
    fi
}

# Function to remove runner
remove_runner() {
    local token="$1"
    
    check_root
    
    warning "This will completely remove the GitHub Actions Runner!"
    read -p "Are you sure? (yes/no): " -r
    if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        log "Operation cancelled"
        return 0
    fi
    
    log "Removing GitHub Actions Runner..."
    
    # Stop and disable service
    if systemctl list-unit-files | grep -q "^${SERVICE_NAME}.service"; then
        systemctl stop "$SERVICE_NAME" 2>/dev/null || true
        systemctl disable "$SERVICE_NAME" 2>/dev/null || true
    fi
    
    # Unconfigure runner
    if [[ -f "$RUNNER_DIR/config.sh" ]]; then
        log "Unconfiguring runner from GitHub..."
        sudo -u "$RUNNER_USER" bash -c "cd '$RUNNER_DIR' && ./config.sh remove --token '$token' --unattended" || warning "Failed to unconfigure runner"
    fi
    
    # Remove service file
    if [[ -f "/etc/systemd/system/${SERVICE_NAME}.service" ]]; then
        rm -f "/etc/systemd/system/${SERVICE_NAME}.service"
        systemctl daemon-reload
    fi
    
    # Remove runner directory
    if [[ -d "$RUNNER_DIR" ]]; then
        rm -rf "$RUNNER_DIR"
    fi
    
    # Optionally remove user
    read -p "Remove runner user '$RUNNER_USER'? (yes/no): " -r
    if [[ $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
        userdel -r "$RUNNER_USER" 2>/dev/null || warning "Failed to remove user"
    fi
    
    success "Runner removed successfully"
}

# Function to show help
show_help() {
    echo -e "${PURPLE}GitHub Actions Runner Management Script${NC}"
    echo
    echo "Usage: $0 <command> [args]"
    echo
    echo -e "${CYAN}Commands:${NC}"
    echo "  status              Show detailed runner status"
    echo "  start               Start the runner service (requires sudo)"
    echo "  stop                Stop the runner service (requires sudo)"
    echo "  restart             Restart the runner service (requires sudo)"
    echo "  enable              Enable auto-start on boot (requires sudo)"
    echo "  disable             Disable auto-start on boot (requires sudo)"
    echo "  logs [lines]        Show recent logs (default: 50 lines)"
    echo "  follow              Follow logs in real-time"
    echo "  health              Perform comprehensive health check"
    echo "  remove <token>      Remove runner completely (requires sudo)"
    echo "  help                Show this help message"
    echo
    echo -e "${CYAN}Examples:${NC}"
    echo "  $0 status"
    echo "  $0 logs 100"
    echo "  sudo $0 restart"
    echo "  sudo $0 remove ghp_xxxxxxxxxxxx"
    echo
    echo -e "${CYAN}Service Management:${NC}"
    echo "  Service Name: $SERVICE_NAME"
    echo "  User: $RUNNER_USER"
    echo "  Directory: $RUNNER_DIR"
    echo
}

# Main command processing
main() {
    if [[ $# -eq 0 ]]; then
        show_help
        exit 1
    fi
    
    local command="$1"
    shift
    
    case "$command" in
        "status")
            show_status
            ;;
        "start")
            start_runner
            ;;
        "stop")
            stop_runner
            ;;
        "restart")
            restart_runner
            ;;
        "enable")
            enable_autostart
            ;;
        "disable")
            disable_autostart
            ;;
        "logs")
            show_logs "${1:-50}"
            ;;
        "follow")
            follow_logs
            ;;
        "health")
            health_check
            ;;
        "remove")
            if [[ $# -ne 1 ]]; then
                error "Remove command requires GitHub token"
                echo "Usage: $0 remove <github_token>"
                exit 1
            fi
            remove_runner "$1"
            ;;
        "help"|"-h"|"--help")
            show_help
            ;;
        *)
            error "Unknown command: $command"
            echo
            show_help
            exit 1
            ;;
    esac
}

# Run main function with all arguments
main "$@"