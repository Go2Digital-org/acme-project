# Go2digit.al ACME Corp CSR Platform - GitHub Actions Self-Hosted Runner Setup

This guide provides comprehensive scripts for setting up a secure, production-ready self-hosted GitHub Actions runner that automatically starts on system reboot.

## Quick Start

### 1. Initial Setup (Run Once)

```bash
# Download your GitHub token from: https://github.com/settings/tokens
# Required permissions: repo, workflow

sudo ./scripts/runner-setup.sh <GITHUB_TOKEN> <REPO_URL>

# Example:
sudo ./scripts/runner-setup.sh ghp_xxxxxxxxxxxx https://github.com/username/acme-corp-optimy
```

### 2. Verify Installation

```bash
# Check runner status
./scripts/runner-management.sh status

# View logs
./scripts/runner-management.sh logs

# Perform health check
./scripts/runner-management.sh health
```

## File Structure

```
scripts/
├── runner-setup.sh          # Main setup script (run once as root)
├── runner-install.sh        # Runner installation helper
└── runner-management.sh     # Daily management commands

config/
└── systemd/
    └── github-runner.service # SystemD service configuration

docs/
└── github-runner-setup.md   # This documentation
```

## Script Overview

### 1. `runner-setup.sh` - Main Setup Script

**Purpose**: Complete initial setup of the GitHub Actions runner
**Usage**: `sudo ./scripts/runner-setup.sh <GITHUB_TOKEN> <REPO_URL>`

**Features**:
- Creates secure `github-runner` user
- Downloads and installs latest runner
- Creates test database (`acme_test`)
- Configures SystemD service with auto-start
- Sets resource limits (60% CPU, 4GB RAM)
- Installs PHP 8.4, Composer, Node.js
- Creates monitoring and management scripts

**Security Features**:
- Non-root execution (runs as `github-runner` user)
- SystemD security isolation
- Resource limits to prevent system overload
- Separate test database for CI/CD

### 2. `runner-install.sh` - Installation Helper

**Purpose**: Download, configure and install the runner binary
**Usage**: `sudo -u github-runner ./scripts/runner-install.sh <GITHUB_TOKEN> <REPO_URL> [RUNNER_NAME]`

**Features**:
- Auto-detects latest runner version
- Downloads appropriate architecture (x64/arm64/arm)
- Configures runner with GitHub repository
- Tests connectivity before completion
- Creates management scripts

### 3. `runner-management.sh` - Daily Operations

**Purpose**: Manage the runner service after installation
**Usage**: `./scripts/runner-management.sh <command>`

**Available Commands**:
```bash
# Service control (requires sudo)
sudo ./scripts/runner-management.sh start
sudo ./scripts/runner-management.sh stop
sudo ./scripts/runner-management.sh restart
sudo ./scripts/runner-management.sh enable    # Auto-start on boot
sudo ./scripts/runner-management.sh disable   # Disable auto-start

# Monitoring (no sudo required)
./scripts/runner-management.sh status         # Detailed status
./scripts/runner-management.sh logs [lines]   # Show recent logs
./scripts/runner-management.sh follow         # Live log monitoring
./scripts/runner-management.sh health         # Health check

# Maintenance
sudo ./scripts/runner-management.sh remove <token>  # Complete removal
```

### 4. `github-runner.service` - SystemD Configuration

**Purpose**: SystemD service file for automatic startup and management

**Key Features**:
- **Auto-start**: Starts automatically on system reboot
- **Auto-restart**: Restarts if the process crashes
- **Resource Limits**: 60% CPU, 4GB RAM, 65536 file descriptors
- **Security Isolation**: Runs with restricted permissions
- **Logging**: Integrated with systemd journal

## Security & Isolation

### User Isolation
- Runs as dedicated `github-runner` user (not root)
- Home directory: `/home/github-runner`
- Limited shell access

### SystemD Security
- `NoNewPrivileges=yes` - Prevents privilege escalation
- `PrivateTmp=yes` - Isolated temporary directory
- `ProtectSystem=strict` - Read-only system directories
- `ProtectHome=yes` - No access to user home directories
- Network and capability restrictions

### Resource Limits
- **Memory**: 4GB maximum
- **CPU**: 60% of available cores
- **File Descriptors**: 65,536
- **Processes**: 4,096

## Database Configuration

The setup creates a separate test database for CI/CD:

```sql
-- Test database created automatically
CREATE DATABASE `acme_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `acme_test`.* TO 'root'@'localhost';
```

Update your `.env.testing` to use this database:
```env
DB_DATABASE=acme_test
```

## Monitoring & Logs

### SystemD Integration
```bash
# View service status
systemctl status github-runner

# View logs
journalctl -u github-runner -f

# View logs with timestamps
journalctl -u github-runner --since "1 hour ago"
```

### Management Scripts
```bash
# Comprehensive status check
./scripts/runner-management.sh status

# Recent logs (last 50 lines)
./scripts/runner-management.sh logs 50

# Live log following
./scripts/runner-management.sh follow

# Health check with diagnostics
./scripts/runner-management.sh health
```

## Troubleshooting

### Common Issues

**1. Runner not starting after reboot**
```bash
# Check service status
systemctl status github-runner

# Check if auto-start is enabled
systemctl is-enabled github-runner

# Enable auto-start if needed
sudo systemctl enable github-runner
```

**2. Resource limits exceeded**
```bash
# Check resource usage
./scripts/runner-management.sh health

# View current limits
systemctl show github-runner | grep -E "(Memory|CPU)"

# Edit service file to adjust limits
sudo nano /etc/systemd/system/github-runner.service
sudo systemctl daemon-reload
sudo systemctl restart github-runner
```

**3. GitHub connectivity issues**
```bash
# Test GitHub API connectivity
curl -s https://api.github.com

# Check runner configuration
cat /home/github-runner/actions-runner/.runner

# Re-configure with new token
sudo -u github-runner /home/github-runner/actions-runner/config.sh remove --token <OLD_TOKEN>
sudo -u github-runner /home/github-runner/actions-runner/config.sh --url <REPO_URL> --token <NEW_TOKEN>
```

**4. Permission issues**
```bash
# Fix ownership
sudo chown -R github-runner:github-runner /home/github-runner

# Check user exists
id github-runner

# Verify directory permissions
ls -la /home/github-runner/actions-runner/
```

### Log Analysis

**Check for specific errors**:
```bash
# Search for error messages
journalctl -u github-runner | grep -i error

# Check startup issues
journalctl -u github-runner --since "today" | head -50

# Monitor resource usage
journalctl -u github-runner | grep -E "(Memory|CPU|killed)"
```

## Maintenance

### Updating the Runner

```bash
# Use the update script created during installation
sudo -u github-runner /home/github-runner/update-runner.sh <GITHUB_TOKEN> <REPO_URL>

# Or reinstall completely
sudo ./scripts/runner-install.sh <GITHUB_TOKEN> <REPO_URL>
```

### Backup Configuration

```bash
# Backup runner configuration
sudo cp /home/github-runner/actions-runner/.runner /backup/
sudo cp /home/github-runner/actions-runner/.credentials /backup/
sudo cp /etc/systemd/system/github-runner.service /backup/
```

### Complete Removal

```bash
# Remove runner and all components
sudo ./scripts/runner-management.sh remove <GITHUB_TOKEN>
```

## Production Considerations

### Server Requirements
- **OS**: Ubuntu 20.04+ or Debian 11+
- **RAM**: 8GB+ (4GB for runner, 4GB for system)
- **CPU**: 2+ cores
- **Disk**: 20GB+ free space
- **Network**: Reliable internet connection

### Monitoring Integration
- SystemD journal integration
- Compatible with log aggregation systems (ELK, Splunk)
- Prometheus metrics via node_exporter
- Custom monitoring scripts included

### Scaling Considerations
- Each runner handles one job at a time
- Multiple runners can be installed with different names
- Consider load balancing for high-traffic repositories
- Monitor resource usage and scale vertically/horizontally as needed

## Security Recommendations

1. **Regular Updates**: Keep the runner updated to latest version
2. **Token Rotation**: Rotate GitHub tokens regularly
3. **Network Security**: Use firewall rules to restrict access
4. **Monitoring**: Monitor logs for suspicious activity
5. **Backup**: Regular backup of configuration files
6. **Access Control**: Limit who can access the runner server

---

## Quick Reference Commands

```bash
# Installation
sudo ./scripts/runner-setup.sh <token> <repo_url>

# Daily management
./scripts/runner-management.sh status
./scripts/runner-management.sh logs
sudo ./scripts/runner-management.sh restart

# Emergency
sudo ./scripts/runner-management.sh remove <token>
```

For additional support, check the GitHub Actions documentation: https://docs.github.com/en/actions/hosting-your-own-runners

---

**Developed and Maintained by Go2digit.al**

Specialized in enterprise-grade applications with focus on scalability, security, and maintainability.

Copyright 2025 Go2digit.al - All Rights Reserved