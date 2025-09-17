#!/bin/bash

# GitHub Actions Runner Installation Helper Script
# Downloads, configures, and sets up the runner as a system service
# Usage: ./runner-install.sh <GITHUB_TOKEN> <REPO_URL> [RUNNER_NAME]

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
NC='\033[0m' # No Color

# Logging functions
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# Check if running as github-runner user
if [[ "$USER" != "$RUNNER_USER" ]]; then
    error "This script must be run as the $RUNNER_USER user.
Use: sudo -u $RUNNER_USER ./runner-install.sh <args>"
fi

# Validate arguments
if [[ $# -lt 2 ]] || [[ $# -gt 3 ]]; then
    error "Usage: $0 <GITHUB_TOKEN> <REPO_URL> [RUNNER_NAME]
Examples:
  $0 ghp_xxxxxxxxxxxx https://github.com/username/repo
  $0 ghp_xxxxxxxxxxxx https://github.com/username/repo custom-runner-name"
fi

GITHUB_TOKEN="$1"
REPO_URL="$2"
RUNNER_NAME="${3:-self-hosted-$(hostname)-$(date +%s)}"

# Validate GitHub token format
if [[ ! $GITHUB_TOKEN =~ ^gh[ps]_[A-Za-z0-9_]{36,251}$ ]]; then
    error "Invalid GitHub token format. Expected format: ghp_xxxx or ghs_xxxx"
fi

# Validate repository URL
if [[ ! $REPO_URL =~ ^https://github\.com/[^/]+/[^/]+/?$ ]]; then
    error "Invalid repository URL format. Expected: https://github.com/username/repo"
fi

log "Installing GitHub Actions Runner: $RUNNER_NAME"
log "Repository: $REPO_URL"

# Function to get latest runner version from GitHub API
get_latest_runner_version() {
    log "Fetching latest runner version..."
    local version
    version=$(curl -s https://api.github.com/repos/actions/runner/releases/latest | jq -r '.tag_name' | sed 's/^v//')
    
    if [[ -z "$version" ]] || [[ "$version" == "null" ]]; then
        error "Failed to fetch latest runner version from GitHub API"
    fi
    
    echo "$version"
}

# Function to determine system architecture
get_system_architecture() {
    local arch
    case $(uname -m) in
        x86_64)
            arch="x64"
            ;;
        aarch64)
            arch="arm64"
            ;;
        armv7l)
            arch="arm"
            ;;
        *)
            warning "Unknown architecture $(uname -m), defaulting to x64"
            arch="x64"
            ;;
    esac
    echo "$arch"
}

# Function to verify download integrity
verify_download() {
    local file="$1"
    local expected_size="$2"
    
    if [[ ! -f "$file" ]]; then
        error "Downloaded file not found: $file"
    fi
    
    local actual_size
    actual_size=$(stat -c%s "$file")
    
    if [[ "$actual_size" -lt "$expected_size" ]]; then
        error "Downloaded file appears incomplete. Expected at least $expected_size bytes, got $actual_size bytes"
    fi
    
    success "Download verification passed"
}

# Function to cleanup existing installation
cleanup_existing_installation() {
    log "Cleaning up existing installation..."
    
    # Stop service if running
    if systemctl is-active --quiet "$SERVICE_NAME" 2>/dev/null; then
        log "Stopping existing runner service..."
        sudo systemctl stop "$SERVICE_NAME" || warning "Failed to stop service"
    fi
    
    # Remove existing runner if present
    if [[ -d "$RUNNER_DIR" ]]; then
        log "Removing existing runner directory..."
        
        # Try to unconfigure existing runner first
        if [[ -f "$RUNNER_DIR/config.sh" ]]; then
            cd "$RUNNER_DIR"
            ./config.sh remove --token "$GITHUB_TOKEN" --unattended 2>/dev/null || warning "Failed to unconfigure existing runner"
        fi
        
        rm -rf "$RUNNER_DIR"
        success "Removed existing installation"
    fi
}

# Function to download and extract runner
download_and_extract_runner() {
    local version="$1"
    local arch="$2"
    
    local runner_package="actions-runner-linux-${arch}-${version}.tar.gz"
    local download_url="https://github.com/actions/runner/releases/download/v${version}/${runner_package}"
    local download_path="$RUNNER_HOME/$runner_package"
    
    log "Downloading runner package: $runner_package"
    log "Download URL: $download_url"
    
    # Download with progress bar and retry logic
    local max_retries=3
    local retry_count=0
    
    while [[ $retry_count -lt $max_retries ]]; do
        if curl -L --progress-bar --retry 3 --retry-delay 5 -o "$download_path" "$download_url"; then
            break
        else
            ((retry_count++))
            if [[ $retry_count -eq $max_retries ]]; then
                error "Failed to download runner after $max_retries attempts"
            fi
            warning "Download attempt $retry_count failed, retrying..."
            sleep 5
        fi
    done
    
    # Verify download (minimum expected size: 50MB)
    verify_download "$download_path" 52428800
    
    log "Extracting runner package..."
    mkdir -p "$RUNNER_DIR"
    
    if tar xzf "$download_path" -C "$RUNNER_DIR"; then
        success "Runner extracted successfully"
    else
        error "Failed to extract runner package"
    fi
    
    # Cleanup download
    rm -f "$download_path"
    
    # Verify extraction
    if [[ ! -f "$RUNNER_DIR/config.sh" ]] || [[ ! -f "$RUNNER_DIR/run.sh" ]]; then
        error "Runner extraction incomplete. Missing required files."
    fi
    
    success "Runner files verified"
}

# Function to configure runner
configure_runner() {
    log "Configuring runner..."
    
    cd "$RUNNER_DIR"
    
    # Make scripts executable
    chmod +x config.sh run.sh
    
    # Configure runner with enhanced options
    local config_args=(
        --url "$REPO_URL"
        --token "$GITHUB_TOKEN"
        --name "$RUNNER_NAME"
        --work "_work"
        --labels "self-hosted,linux,$(get_system_architecture())"
        --replace
        --unattended
    )
    
    log "Running configuration with name: $RUNNER_NAME"
    if ./config.sh "${config_args[@]}"; then
        success "Runner configured successfully"
    else
        error "Failed to configure runner"
    fi
    
    # Verify configuration
    if [[ ! -f ".runner" ]] || [[ ! -f ".credentials" ]]; then
        error "Runner configuration incomplete. Missing credential files."
    fi
    
    # Display runner info
    log "Runner configuration details:"
    if [[ -f ".runner" ]]; then
        jq -r '"  Name: " + .agentName + "\n  Pool: " + .poolName + "\n  Labels: " + (.labels | join(", "))' .runner
    fi
}

# Function to install dependencies
install_runner_dependencies() {
    log "Installing runner dependencies..."
    
    cd "$RUNNER_DIR"
    
    # Install .NET dependencies
    if [[ -f "bin/installdependencies.sh" ]]; then
        sudo ./bin/installdependencies.sh
        success "Runner dependencies installed"
    else
        warning "Dependencies installation script not found, skipping..."
    fi
}

# Function to test runner connectivity
test_runner_connection() {
    log "Testing runner connectivity..."
    
    cd "$RUNNER_DIR"
    
    # Create a simple test script to verify connection
    cat > test_connection.sh << 'TEST_EOF'
#!/bin/bash
timeout 30s ./run.sh --check
exit_code=$?
if [[ $exit_code -eq 0 ]]; then
    echo "✓ Runner connection test passed"
    exit 0
else
    echo "✗ Runner connection test failed (exit code: $exit_code)"
    exit 1
fi
TEST_EOF
    
    chmod +x test_connection.sh
    
    if ./test_connection.sh; then
        success "Runner connectivity verified"
    else
        error "Runner connectivity test failed. Check your token and repository URL."
    fi
    
    rm -f test_connection.sh
}

# Function to setup service integration
setup_service_integration() {
    log "Setting up service integration..."
    
    cd "$RUNNER_DIR"
    
    # Install service
    if sudo ./svc.sh install "$RUNNER_USER"; then
        success "Service integration installed"
    else
        error "Failed to install service integration"
    fi
    
    # Enable auto-start
    if sudo systemctl enable "$SERVICE_NAME"; then
        success "Auto-start enabled"
    else
        error "Failed to enable auto-start"
    fi
    
    # Start service
    if sudo systemctl start "$SERVICE_NAME"; then
        success "Service started"
    else
        error "Failed to start service"
    fi
    
    # Wait for service to stabilize
    sleep 5
    
    # Verify service status
    if sudo systemctl is-active --quiet "$SERVICE_NAME"; then
        success "Service is running"
    else
        error "Service failed to start properly"
    fi
}

# Function to create useful scripts
create_management_scripts() {
    log "Creating management scripts..."
    
    # Status check script
    cat > "$RUNNER_HOME/check-status.sh" << 'STATUS_EOF'
#!/bin/bash

SERVICE_NAME="github-runner"
RUNNER_DIR="/home/github-runner/actions-runner"

echo "=== GitHub Actions Runner Status ==="
echo "Date: $(date)"
echo "Hostname: $(hostname)"
echo

echo "Service Status:"
systemctl status $SERVICE_NAME --no-pager -l | head -20

echo
echo "Recent Logs (last 10 entries):"
journalctl -u $SERVICE_NAME --no-pager -n 10

echo
echo "Runner Configuration:"
if [[ -f "$RUNNER_DIR/.runner" ]]; then
    jq -r '"Name: " + .agentName + "\nPool: " + .poolName + "\nLabels: " + (.labels | join(", "))' "$RUNNER_DIR/.runner"
else
    echo "Configuration file not found"
fi

echo
echo "System Resources:"
echo "Memory: $(free -h | awk '/^Mem:/{print $3 "/" $2}')"
echo "CPU Load: $(uptime | awk -F'load average:' '{print $2}')"
echo "Disk: $(df -h / | awk 'NR==2{print $3 "/" $2 " (" $5 " used)"}')"
STATUS_EOF

    chmod +x "$RUNNER_HOME/check-status.sh"
    
    # Update script
    cat > "$RUNNER_HOME/update-runner.sh" << 'UPDATE_EOF'
#!/bin/bash

# Runner update script
# Usage: ./update-runner.sh <GITHUB_TOKEN> <REPO_URL>

if [[ $# -ne 2 ]]; then
    echo "Usage: $0 <GITHUB_TOKEN> <REPO_URL>"
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
INSTALL_SCRIPT="$SCRIPT_DIR/../scripts/runner-install.sh"

if [[ -f "$INSTALL_SCRIPT" ]]; then
    echo "Updating runner using install script..."
    "$INSTALL_SCRIPT" "$1" "$2" "self-hosted-$(hostname)-updated"
else
    echo "Error: Install script not found at $INSTALL_SCRIPT"
    exit 1
fi
UPDATE_EOF

    chmod +x "$RUNNER_HOME/update-runner.sh"
    
    success "Management scripts created"
}

# Main installation process
main() {
    log "Starting GitHub Actions Runner installation process..."
    
    # Ensure we're in the correct directory
    cd "$RUNNER_HOME"
    
    # Get system information
    local version
    local arch
    version=$(get_latest_runner_version)
    arch=$(get_system_architecture)
    
    log "Runner version: $version"
    log "System architecture: $arch"
    
    # Installation steps
    cleanup_existing_installation
    download_and_extract_runner "$version" "$arch"
    configure_runner
    install_runner_dependencies
    test_runner_connection
    setup_service_integration
    create_management_scripts
    
    # Final status report
    echo
    echo "================================================================="
    echo "        GitHub Actions Runner Installation Complete!"
    echo "================================================================="
    echo
    echo "Installation Summary:"
    echo "  • Runner Name: $RUNNER_NAME"
    echo "  • Repository: $REPO_URL"
    echo "  • Version: $version"
    echo "  • Architecture: $arch"
    echo "  • Service: $SERVICE_NAME"
    echo "  • Working Directory: $RUNNER_DIR/_work"
    echo
    echo "Service Management:"
    echo "  • Status: sudo systemctl status $SERVICE_NAME"
    echo "  • Logs: sudo journalctl -u $SERVICE_NAME -f"
    echo "  • Restart: sudo systemctl restart $SERVICE_NAME"
    echo "  • Stop: sudo systemctl stop $SERVICE_NAME"
    echo
    echo "Management Scripts:"
    echo "  • Check Status: $RUNNER_HOME/check-status.sh"
    echo "  • Update Runner: $RUNNER_HOME/update-runner.sh <token> <repo>"
    echo
    echo "The runner will automatically start on system reboot."
    echo "================================================================="
    
    success "Installation completed successfully! Runner is ready to accept jobs."
}

# Trap errors and cleanup
trap 'error "Installation failed at line $LINENO"' ERR

# Run main installation
main "$@"