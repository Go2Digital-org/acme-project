#!/bin/bash

# GitHub Actions Self-Hosted Runner Setup Script
# Creates a secure, isolated runner with auto-start on reboot
# Usage: sudo ./scripts/runner-setup.sh <GITHUB_TOKEN> <REPO_URL>

set -euo pipefail

# Configuration
RUNNER_USER="github-runner"
RUNNER_HOME="/home/${RUNNER_USER}"
RUNNER_DIR="${RUNNER_HOME}/actions-runner"
SERVICE_NAME="github-runner"
TEST_DB_NAME="acme_test"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
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

# Check if running as root
if [[ $EUID -ne 0 ]]; then
    error "This script must be run as root (use sudo)"
fi

# Validate arguments
if [[ $# -ne 2 ]]; then
    error "Usage: $0 <GITHUB_TOKEN> <REPO_URL>
Example: $0 ghp_xxxxxxxxxxxx https://github.com/username/repo"
fi

GITHUB_TOKEN="$1"
REPO_URL="$2"

# Validate GitHub token format
if [[ ! $GITHUB_TOKEN =~ ^gh[ps]_[A-Za-z0-9_]{36,251}$ ]]; then
    error "Invalid GitHub token format. Expected format: ghp_xxxx or ghs_xxxx"
fi

# Validate repository URL
if [[ ! $REPO_URL =~ ^https://github\.com/[^/]+/[^/]+/?$ ]]; then
    error "Invalid repository URL format. Expected: https://github.com/username/repo"
fi

log "Starting GitHub Actions Runner setup for repository: $REPO_URL"

# Update system packages
log "Updating system packages..."
apt-get update -qq
apt-get upgrade -y -qq

# Install required packages
log "Installing required system packages..."
apt-get install -y -qq \
    curl \
    wget \
    tar \
    jq \
    git \
    build-essential \
    libssl-dev \
    libffi-dev \
    python3-dev \
    python3-pip \
    nodejs \
    npm \
    php8.4 \
    php8.4-cli \
    php8.4-common \
    php8.4-mysql \
    php8.4-xml \
    php8.4-curl \
    php8.4-gd \
    php8.4-imagick \
    php8.4-cli \
    php8.4-dev \
    php8.4-imap \
    php8.4-mbstring \
    php8.4-opcache \
    php8.4-soap \
    php8.4-zip \
    php8.4-redis \
    unzip \
    mysql-client \
    redis-tools

# Create github-runner user with secure permissions
log "Creating secure github-runner user..."
if ! id "$RUNNER_USER" &>/dev/null; then
    useradd -m -s /bin/bash -d "$RUNNER_HOME" "$RUNNER_USER"
    success "Created user: $RUNNER_USER"
else
    warning "User $RUNNER_USER already exists"
fi

# Set up proper directory permissions
mkdir -p "$RUNNER_DIR"
chown -R "$RUNNER_USER:$RUNNER_USER" "$RUNNER_HOME"
chmod 755 "$RUNNER_HOME"

# Create test database for CI/CD
log "Setting up test database..."
if command -v mysql &> /dev/null; then
    # Check if database exists
    if mysql -u root -e "USE $TEST_DB_NAME;" 2>/dev/null; then
        warning "Database $TEST_DB_NAME already exists"
    else
        mysql -u root -e "CREATE DATABASE IF NOT EXISTS \`$TEST_DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        mysql -u root -e "GRANT ALL PRIVILEGES ON \`$TEST_DB_NAME\`.* TO 'root'@'localhost';"
        mysql -u root -e "FLUSH PRIVILEGES;"
        success "Created test database: $TEST_DB_NAME"
    fi
else
    warning "MySQL not found. Database setup will be skipped."
fi

# Install Composer globally
log "Installing Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
    success "Composer installed"
else
    success "Composer already installed"
fi

# Create systemd service directory
log "Creating systemd service configuration..."
mkdir -p /etc/systemd/system

# Create systemd service file
cat > /etc/systemd/system/${SERVICE_NAME}.service << 'EOF'
[Unit]
Description=GitHub Actions Self-Hosted Runner
Documentation=https://docs.github.com/en/actions/hosting-your-own-runners
After=network.target multi-user.target
Wants=network.target

[Service]
Type=simple
User=github-runner
Group=github-runner
WorkingDirectory=/home/github-runner/actions-runner
Environment=HOME=/home/github-runner
Environment=DOTNET_ROOT=/home/github-runner/actions-runner/externals/dotnet
Environment=DOTNET_SYSTEM_GLOBALIZATION_INVARIANT=1
ExecStart=/home/github-runner/actions-runner/run.sh
ExecReload=/bin/kill -HUP $MAINPID
Restart=always
RestartSec=10

# Security settings
NoNewPrivileges=yes
PrivateTmp=yes
ProtectSystem=strict
ProtectHome=yes
ReadWritePaths=/home/github-runner
ProtectKernelTunables=yes
ProtectKernelModules=yes
ProtectControlGroups=yes

# Resource limits
LimitNOFILE=65536
LimitNPROC=4096
MemoryLimit=4G
CPUQuota=60%

# Logging
StandardOutput=journal
StandardError=journal
SyslogIdentifier=github-runner

[Install]
WantedBy=multi-user.target
EOF

success "Created systemd service file"

# Create runner installation script
log "Creating runner installation script..."
cat > /tmp/install-runner.sh << 'INSTALL_EOF'
#!/bin/bash

set -euo pipefail

RUNNER_HOME="/home/github-runner"
RUNNER_DIR="${RUNNER_HOME}/actions-runner"
GITHUB_TOKEN="$1"
REPO_URL="$2"

# Function to get latest runner version
get_latest_runner_version() {
    curl -s https://api.github.com/repos/actions/runner/releases/latest | \
    jq -r '.tag_name' | sed 's/^v//'
}

# Function to determine architecture
get_arch() {
    case $(uname -m) in
        x86_64) echo "x64" ;;
        aarch64) echo "arm64" ;;
        armv7l) echo "arm" ;;
        *) echo "x64" ;; # Default fallback
    esac
}

cd "$RUNNER_HOME"

# Get latest runner version and architecture
RUNNER_VERSION=$(get_latest_runner_version)
ARCH=$(get_arch)
RUNNER_PACKAGE="actions-runner-linux-${ARCH}-${RUNNER_VERSION}.tar.gz"
DOWNLOAD_URL="https://github.com/actions/runner/releases/download/v${RUNNER_VERSION}/${RUNNER_PACKAGE}"

echo "Downloading GitHub Actions Runner v${RUNNER_VERSION} for ${ARCH}..."
curl -o "$RUNNER_PACKAGE" -L "$DOWNLOAD_URL"

echo "Extracting runner..."
mkdir -p "$RUNNER_DIR"
tar xzf "$RUNNER_PACKAGE" -C "$RUNNER_DIR"
rm "$RUNNER_PACKAGE"

cd "$RUNNER_DIR"

# Configure runner
echo "Configuring runner for repository: $REPO_URL"
./config.sh --url "$REPO_URL" --token "$GITHUB_TOKEN" --name "self-hosted-$(hostname)" --work _work --replace --unattended

# Install dependencies
echo "Installing runner dependencies..."
sudo ./svc.sh install github-runner

echo "Runner installation completed successfully!"
INSTALL_EOF

chmod +x /tmp/install-runner.sh

# Run installation as github-runner user
log "Installing GitHub Actions Runner..."
sudo -u "$RUNNER_USER" bash /tmp/install-runner.sh "$GITHUB_TOKEN" "$REPO_URL"
rm /tmp/install-runner.sh

# Enable and start the service
log "Enabling GitHub Actions Runner service..."
systemctl daemon-reload
systemctl enable "$SERVICE_NAME"
systemctl start "$SERVICE_NAME"

# Verify service status
sleep 5
if systemctl is-active --quiet "$SERVICE_NAME"; then
    success "GitHub Actions Runner service is running!"
else
    error "Failed to start GitHub Actions Runner service"
fi

# Create monitoring script
log "Creating monitoring script..."
cat > "$RUNNER_HOME/monitor-runner.sh" << 'MONITOR_EOF'
#!/bin/bash

# GitHub Runner Monitoring Script
# Usage: ./monitor-runner.sh

SERVICE_NAME="github-runner"

echo "=== GitHub Actions Runner Status ==="
echo "Service Status: $(systemctl is-active $SERVICE_NAME)"
echo "Service Enabled: $(systemctl is-enabled $SERVICE_NAME)"
echo "Last 10 log entries:"
journalctl -u $SERVICE_NAME --no-pager -n 10

echo -e "\n=== System Resources ==="
echo "Memory Usage:"
free -h

echo "CPU Usage:"
top -bn1 | grep "Cpu(s)" | awk '{print "CPU Load: " $2 " " $3}'

echo "Disk Usage:"
df -h | grep -E "/$|/home"

echo -e "\n=== Runner Process ==="
ps aux | grep -E "(Runner.Listener|run.sh)" | grep -v grep || echo "No runner processes found"
MONITOR_EOF

chmod +x "$RUNNER_HOME/monitor-runner.sh"
chown "$RUNNER_USER:$RUNNER_USER" "$RUNNER_HOME/monitor-runner.sh"

# Create maintenance script
cat > "$RUNNER_HOME/restart-runner.sh" << 'RESTART_EOF'
#!/bin/bash

# GitHub Runner Restart Script
# Usage: sudo ./restart-runner.sh

SERVICE_NAME="github-runner"

echo "Stopping GitHub Actions Runner..."
systemctl stop $SERVICE_NAME

echo "Waiting 5 seconds..."
sleep 5

echo "Starting GitHub Actions Runner..."
systemctl start $SERVICE_NAME

echo "Service status:"
systemctl status $SERVICE_NAME --no-pager -l
RESTART_EOF

chmod +x "$RUNNER_HOME/restart-runner.sh"
chown "$RUNNER_USER:$RUNNER_USER" "$RUNNER_HOME/restart-runner.sh"

# Final verification and status
log "Performing final verification..."

# Check service status
SERVICE_STATUS=$(systemctl is-active "$SERVICE_NAME")
SERVICE_ENABLED=$(systemctl is-enabled "$SERVICE_NAME")

echo
echo "================================================================="
echo "           GitHub Actions Runner Setup Complete!"
echo "================================================================="
echo
echo "Configuration Summary:"
echo "  • Runner User: $RUNNER_USER"
echo "  • Runner Directory: $RUNNER_DIR"
echo "  • Service Name: $SERVICE_NAME"
echo "  • Service Status: $SERVICE_STATUS"
echo "  • Auto-start Enabled: $SERVICE_ENABLED"
echo "  • Test Database: $TEST_DB_NAME"
echo "  • Repository: $REPO_URL"
echo
echo "Resource Limits:"
echo "  • Memory: 4GB"
echo "  • CPU: 60%"
echo "  • File Descriptors: 65536"
echo "  • Processes: 4096"
echo
echo "Management Commands:"
echo "  • Check status: systemctl status $SERVICE_NAME"
echo "  • View logs: journalctl -u $SERVICE_NAME -f"
echo "  • Restart: sudo systemctl restart $SERVICE_NAME"
echo "  • Monitor: sudo -u $RUNNER_USER $RUNNER_HOME/monitor-runner.sh"
echo "  • Restart script: sudo $RUNNER_HOME/restart-runner.sh"
echo
echo "Security Features:"
echo "  • Runs as non-root user ($RUNNER_USER)"
echo "  • Isolated with systemd security settings"
echo "  • Resource limits enforced"
echo "  • Separate test database"
echo
echo "The runner will automatically start on system reboot."
echo "================================================================="

success "Setup completed successfully! The runner is ready to accept jobs."