#!/bin/bash

# ================================================================
# ACME Corp CSR Platform - Docker Security Scanning Script
# Comprehensive security analysis for Docker images
# ================================================================

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
IMAGE_NAME="${1:-acme-csr-platform:latest}"
SCAN_TYPE="${SCAN_TYPE:-all}"
OUTPUT_FORMAT="${OUTPUT_FORMAT:-table}"
SEVERITY_THRESHOLD="${SEVERITY_THRESHOLD:-medium}"

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if tools are installed
check_tools() {
    print_status "Checking security scanning tools..."
    
    local tools_available=true
    
    # Check for Docker Scout
    if docker scout version &> /dev/null; then
        print_success "Docker Scout is available"
    else
        print_warning "Docker Scout is not available"
        tools_available=false
    fi
    
    # Check for Trivy
    if command -v trivy &> /dev/null; then
        print_success "Trivy is available"
    else
        print_warning "Trivy is not available. Install with: curl -sfL https://raw.githubusercontent.com/aquasecurity/trivy/main/contrib/install.sh | sh -s -- -b /usr/local/bin"
        tools_available=false
    fi
    
    # Check for Grype
    if command -v grype &> /dev/null; then
        print_success "Grype is available"
    else
        print_warning "Grype is not available. Install with: curl -sSfL https://raw.githubusercontent.com/anchore/grype/main/install.sh | sh -s -- -b /usr/local/bin"
        tools_available=false
    fi
    
    # Check for Dive
    if command -v dive &> /dev/null; then
        print_success "Dive is available"
    else
        print_warning "Dive is not available. Install from: https://github.com/wagoodman/dive"
    fi
    
    if [ "$tools_available" = false ]; then
        print_error "Some security tools are missing. Please install them for comprehensive scanning."
    fi
}

# Function to scan with Docker Scout
scan_with_scout() {
    print_status "Running Docker Scout security scan..."
    
    if docker scout version &> /dev/null; then
        echo "=== Docker Scout Vulnerabilities ==="
        docker scout cves "$IMAGE_NAME" || print_warning "Docker Scout scan failed"
        
        echo ""
        echo "=== Docker Scout Recommendations ==="
        docker scout recommendations "$IMAGE_NAME" || print_warning "Docker Scout recommendations failed"
        echo ""
    else
        print_warning "Skipping Docker Scout scan (not available)"
    fi
}

# Function to scan with Trivy
scan_with_trivy() {
    print_status "Running Trivy security scan..."
    
    if command -v trivy &> /dev/null; then
        echo "=== Trivy Vulnerability Scan ==="
        trivy image \
            --severity HIGH,CRITICAL \
            --format "$OUTPUT_FORMAT" \
            --exit-code 0 \
            "$IMAGE_NAME" || print_warning "Trivy scan had issues"
        
        echo ""
        echo "=== Trivy Secret Scan ==="
        trivy image \
            --scanners secret \
            --format "$OUTPUT_FORMAT" \
            "$IMAGE_NAME" || print_warning "Trivy secret scan had issues"
        
        echo ""
        echo "=== Trivy Configuration Scan ==="
        trivy image \
            --scanners config \
            --format "$OUTPUT_FORMAT" \
            "$IMAGE_NAME" || print_warning "Trivy config scan had issues"
        echo ""
    else
        print_warning "Skipping Trivy scan (not available)"
    fi
}

# Function to scan with Grype
scan_with_grype() {
    print_status "Running Grype vulnerability scan..."
    
    if command -v grype &> /dev/null; then
        echo "=== Grype Vulnerability Scan ==="
        grype "$IMAGE_NAME" \
            --only-fixed \
            --fail-on high || print_warning "Grype scan found high/critical vulnerabilities"
        echo ""
    else
        print_warning "Skipping Grype scan (not available)"
    fi
}

# Function to analyze image layers
analyze_layers() {
    print_status "Analyzing Docker image layers..."
    
    if command -v dive &> /dev/null; then
        echo "=== Image Layer Analysis ==="
        print_status "Running Dive analysis (efficiency score)..."
        dive "$IMAGE_NAME" --ci || print_warning "Dive analysis had issues"
        echo ""
    else
        print_warning "Skipping layer analysis (Dive not available)"
    fi
    
    # Basic layer information
    echo "=== Image Layer Information ==="
    docker history "$IMAGE_NAME" --format "table {{.CreatedBy}}\t{{.Size}}\t{{.Comment}}" || print_warning "Failed to get image history"
    echo ""
}

# Function to check image metadata
check_metadata() {
    print_status "Checking image metadata and configuration..."
    
    echo "=== Image Inspection ==="
    docker inspect "$IMAGE_NAME" --format='
Image: {{.RepoTags}}
Architecture: {{.Architecture}}
OS: {{.Os}}
Size: {{.Size}} bytes
Created: {{.Created}}
User: {{.Config.User}}
WorkingDir: {{.Config.WorkingDir}}
ExposedPorts: {{.Config.ExposedPorts}}
Env: {{.Config.Env}}
Cmd: {{.Config.Cmd}}
Entrypoint: {{.Config.Entrypoint}}
' || print_warning "Failed to inspect image"
    
    echo ""
    echo "=== Security Configuration Check ==="
    
    # Check if running as root
    local user=$(docker inspect "$IMAGE_NAME" --format='{{.Config.User}}')
    if [ -z "$user" ] || [ "$user" = "root" ] || [ "$user" = "0" ]; then
        print_warning "Image is configured to run as root - security risk!"
    else
        print_success "Image is configured to run as non-root user: $user"
    fi
    
    # Check for exposed ports
    local ports=$(docker inspect "$IMAGE_NAME" --format='{{.Config.ExposedPorts}}')
    if [ "$ports" != "map[]" ] && [ "$ports" != "<no value>" ]; then
        print_status "Exposed ports: $ports"
    fi
    
    echo ""
}

# Function to perform basic hardening checks
security_hardening_check() {
    print_status "Performing security hardening checks..."
    
    echo "=== Security Hardening Analysis ==="
    
    # Create a temporary container to check filesystem
    local container_id
    container_id=$(docker create "$IMAGE_NAME")
    
    # Check for common security issues
    echo "Checking for potential security issues..."
    
    # Check /etc/passwd for unnecessary users
    print_status "Checking user accounts..."
    docker cp "$container_id:/etc/passwd" - 2>/dev/null | tar -xO | wc -l | {
        read line_count
        if [ "$line_count" -gt 10 ]; then
            print_warning "Many user accounts found ($line_count lines in /etc/passwd)"
        else
            print_success "Minimal user accounts found"
        fi
    } || print_warning "Could not check /etc/passwd"
    
    # Check for package managers (should be removed in production)
    print_status "Checking for package managers..."
    if docker run --rm "$IMAGE_NAME" which apt &>/dev/null; then
        print_warning "apt package manager found - should be removed in production"
    fi
    
    if docker run --rm "$IMAGE_NAME" which yum &>/dev/null; then
        print_warning "yum package manager found - should be removed in production"
    fi
    
    if docker run --rm "$IMAGE_NAME" which apk &>/dev/null; then
        print_warning "apk package manager found - consider removing in production"
    fi
    
    # Check for common development tools
    print_status "Checking for development tools..."
    local dev_tools=("git" "curl" "wget" "vim" "nano" "ssh")
    for tool in "${dev_tools[@]}"; do
        if docker run --rm "$IMAGE_NAME" which "$tool" &>/dev/null; then
            print_warning "$tool found - consider removing in production"
        fi
    done
    
    # Cleanup
    docker rm "$container_id" &>/dev/null
    
    echo ""
}

# Function to generate security report
generate_report() {
    print_status "Generating security summary report..."
    
    echo "=== SECURITY SCAN SUMMARY ==="
    echo "Image: $IMAGE_NAME"
    echo "Scan Date: $(date)"
    echo "Scan Type: $SCAN_TYPE"
    echo ""
    
    echo "Recommendations:"
    echo "1. Regularly update base images and dependencies"
    echo "2. Use multi-stage builds to reduce attack surface"
    echo "3. Run containers as non-root user"
    echo "4. Remove unnecessary packages and tools from production images"
    echo "5. Scan images regularly in CI/CD pipeline"
    echo "6. Use minimal base images (Alpine, Distroless)"
    echo "7. Implement proper secrets management"
    echo "8. Enable Docker Content Trust"
    echo ""
    
    print_success "Security scan completed for $IMAGE_NAME"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [IMAGE_NAME] [OPTIONS]"
    echo ""
    echo "Arguments:"
    echo "  IMAGE_NAME                 Docker image to scan (default: acme-csr-platform:latest)"
    echo ""
    echo "Environment Variables:"
    echo "  SCAN_TYPE                  Type of scan: all, quick, deep (default: all)"
    echo "  OUTPUT_FORMAT             Output format: table, json, sarif (default: table)"
    echo "  SEVERITY_THRESHOLD        Minimum severity: low, medium, high, critical (default: medium)"
    echo ""
    echo "Examples:"
    echo "  $0                                           # Scan default image"
    echo "  $0 myapp:latest                             # Scan specific image"
    echo "  SCAN_TYPE=quick $0 myapp:latest             # Quick scan only"
    echo "  OUTPUT_FORMAT=json $0 myapp:latest          # JSON output"
}

# Main execution
main() {
    if [ "$1" = "-h" ] || [ "$1" = "--help" ]; then
        show_usage
        exit 0
    fi
    
    print_status "Starting comprehensive security scan for: $IMAGE_NAME"
    print_status "Scan type: $SCAN_TYPE"
    echo ""
    
    check_tools
    echo ""
    
    case $SCAN_TYPE in
        "quick")
            scan_with_scout
            check_metadata
            ;;
        "deep")
            scan_with_scout
            scan_with_trivy
            scan_with_grype
            analyze_layers
            check_metadata
            security_hardening_check
            ;;
        "all"|*)
            scan_with_scout
            scan_with_trivy
            scan_with_grype
            analyze_layers
            check_metadata
            security_hardening_check
            ;;
    esac
    
    generate_report
}

# Run main function with all arguments
main "$@"