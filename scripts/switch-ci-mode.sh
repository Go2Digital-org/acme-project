#!/bin/bash

# CI Mode Switching Script
# Manages GitHub Actions CI runner types and workflow selection

set -e

WORKFLOWS_DIR=".github/workflows"
MAIN_CI="ci.yml"
SELF_HOSTED_CI="ci-optimized.yml"

show_help() {
    cat << EOF
CI Mode Switcher

Usage: $0 [mode] [options]

Modes:
  github-hosted   Use GitHub-hosted runners (billable minutes)
  self-hosted     Use self-hosted runners (your infrastructure)
  status          Show current CI configuration
  help            Show this help message

Options:
  --force         Force the operation even if runners are offline
  --quiet         Suppress verbose output

Examples:
  $0 github-hosted     # Switch to GitHub-hosted runners
  $0 self-hosted       # Switch to self-hosted runners
  $0 status            # Check current configuration
  $0 self-hosted --force  # Force self-hosted even if offline

The script configures repository variables and workflow selection
to optimize for your chosen runner type.
EOF
}

check_git_repo() {
    if [ ! -d ".git" ]; then
        echo "❌ Error: Not in a Git repository"
        exit 1
    fi
}

check_workflows_exist() {
    if [ ! -f "$WORKFLOWS_DIR/$MAIN_CI" ]; then
        echo "❌ Error: Main workflow file not found: $WORKFLOWS_DIR/$MAIN_CI"
        exit 1
    fi
    
    if [ ! -f "$WORKFLOWS_DIR/$SELF_HOSTED_CI" ]; then
        echo "❌ Error: Self-hosted workflow file not found: $WORKFLOWS_DIR/$SELF_HOSTED_CI"
        exit 1
    fi
}

get_current_mode() {
    # Check for CI_RUNNER_TYPE in environment or git config
    if [ -f ".env" ] && grep -q "CI_RUNNER_TYPE" .env 2>/dev/null; then
        grep "CI_RUNNER_TYPE" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'"
    elif git config --get ci.runner-type 2>/dev/null; then
        git config --get ci.runner-type
    else
        echo "github-hosted"
    fi
}

check_self_hosted_availability() {
    local quiet=${1:-false}
    
    if [ "$quiet" = false ]; then
        echo "🔍 Checking self-hosted runner availability..."
    fi
    
    # This is a basic check - in practice you might ping your runners
    # or check their status via GitHub API
    if command -v docker >/dev/null 2>&1; then
        if [ "$quiet" = false ]; then
            echo "✅ Docker available (good for self-hosted setup)"
        fi
        return 0
    else
        if [ "$quiet" = false ]; then
            echo "⚠️ Docker not available (might indicate runner issues)"
        fi
        return 1
    fi
}

update_git_config() {
    local mode="$1"
    git config ci.runner-type "$mode"
    echo "   Updated git config: ci.runner-type = $mode"
}

update_env_file() {
    local mode="$1"
    
    # Update .env if it exists
    if [ -f ".env" ]; then
        if grep -q "CI_RUNNER_TYPE" .env; then
            # Update existing line
            if [[ "$OSTYPE" == "darwin"* ]]; then
                sed -i '' "s/^CI_RUNNER_TYPE=.*/CI_RUNNER_TYPE=$mode/" .env
            else
                sed -i "s/^CI_RUNNER_TYPE=.*/CI_RUNNER_TYPE=$mode/" .env
            fi
        else
            # Add new line
            echo "CI_RUNNER_TYPE=$mode" >> .env
        fi
        echo "   Updated .env file: CI_RUNNER_TYPE = $mode"
    fi
}

switch_to_github_hosted() {
    local quiet=${1:-false}
    
    if [ "$quiet" = false ]; then
        echo "☁️ Switching to GITHUB-HOSTED CI..."
        echo
    fi
    
    # Update configuration
    update_git_config "github-hosted"
    update_env_file "github-hosted"
    
    if [ "$quiet" = false ]; then
        echo
        echo "✅ GitHub-hosted CI activated!"
        echo "   Runner Type: GitHub-hosted (ubuntu-latest)"
        echo "   Cost: Billable minutes (2000 free/month for private repos)"
        echo "   Performance: Standard GitHub runners (2 CPU, 7GB RAM)"
        echo "   Best for: Public repos, standard workloads"
        echo
        echo "💡 The main CI workflow (ci.yml) will automatically use GitHub-hosted runners"
    fi
}

switch_to_self_hosted() {
    local force=${1:-false}
    local quiet=${2:-false}
    
    if [ "$quiet" = false ]; then
        echo "🏠 Switching to SELF-HOSTED CI..."
        echo
    fi
    
    # Check availability unless forced
    if [ "$force" = false ] && ! check_self_hosted_availability $quiet; then
        echo "⚠️ Self-hosted runners may not be available"
        echo "   Use --force to override this check"
        echo "   Or ensure your self-hosted runners are running"
        return 1
    fi
    
    # Update configuration
    update_git_config "self-hosted"
    update_env_file "self-hosted"
    
    if [ "$quiet" = false ]; then
        echo
        echo "✅ Self-hosted CI activated!"
        echo "   Runner Type: Self-hosted Linux runners"
        echo "   Cost: Your infrastructure costs"
        echo "   Performance: Depends on your hardware setup"
        echo "   Best for: Private repos, heavy workloads, cost optimization"
        echo
        echo "💡 Both workflows will prefer self-hosted runners when available"
        echo "💡 Use 'ci-optimized.yml' workflow for pure self-hosted execution"
    fi
}

show_status() {
    local current_mode=$(get_current_mode)
    local is_available=""
    
    echo "📊 CI Configuration Status"
    echo "=========================="
    echo "Current Mode: $current_mode"
    echo
    
    echo "Runner Availability:"
    echo "  GitHub-hosted: ✅ Always available"
    
    if check_self_hosted_availability true; then
        is_available="✅ Available"
    else
        is_available="❌ Not detected"
    fi
    echo "  Self-hosted: $is_available"
    echo
    
    echo "Available Workflows:"
    if [ -f "$WORKFLOWS_DIR/$MAIN_CI" ]; then
        echo "  ✅ $MAIN_CI (universal - supports both runner types)"
    else
        echo "  ❌ $MAIN_CI (missing)"
    fi
    
    if [ -f "$WORKFLOWS_DIR/$SELF_HOSTED_CI" ]; then
        echo "  ✅ $SELF_HOSTED_CI (optimized for self-hosted)"
    else
        echo "  ❌ $SELF_HOSTED_CI (missing)"
    fi
    echo
    
    echo "Configuration Files:"
    if [ -f ".env" ] && grep -q "CI_RUNNER_TYPE" .env 2>/dev/null; then
        local env_mode=$(grep "CI_RUNNER_TYPE" .env | cut -d'=' -f2 | tr -d '"' | tr -d "'")
        echo "  .env: CI_RUNNER_TYPE=$env_mode"
    else
        echo "  .env: No CI_RUNNER_TYPE set"
    fi
    
    if git config --get ci.runner-type >/dev/null 2>&1; then
        local git_mode=$(git config --get ci.runner-type)
        echo "  git config: ci.runner-type=$git_mode"
    else
        echo "  git config: No ci.runner-type set"
    fi
    echo
    
    echo "💡 Use '$0 github-hosted' or '$0 self-hosted' to switch modes"
    echo "💡 Set repository variable CI_RUNNER_TYPE in GitHub for team-wide defaults"
}

show_billing_info() {
    echo "💰 GitHub Actions Billing Information"
    echo "====================================="
    echo
    echo "GitHub-hosted runners:"
    echo "  • Free tier: 2000 minutes/month (private repos)"
    echo "  • Public repos: Unlimited free minutes"
    echo "  • Cost: \$0.008/minute (Linux) after free tier"
    echo
    echo "Self-hosted runners:"
    echo "  • No GitHub charges for compute time"
    echo "  • You pay for infrastructure costs"
    echo "  • Setup and maintenance overhead"
    echo
    echo "Typical CI run times:"
    echo "  • GitHub-hosted: 10-15 minutes (full CI)"
    echo "  • Self-hosted: 8-12 minutes (potentially faster)"
    echo
    echo "Monthly cost estimation (private repo, 100 runs):"
    echo "  • GitHub-hosted: ~\$8-12/month (after free tier)"
    echo "  • Self-hosted: \$0 GitHub charges + infrastructure costs"
}

# Parse arguments
FORCE=false
QUIET=false
MODE=""

while [[ $# -gt 0 ]]; do
    case $1 in
        --force)
            FORCE=true
            shift
            ;;
        --quiet)
            QUIET=true
            shift
            ;;
        github-hosted|self-hosted|status|help|billing)
            MODE="$1"
            shift
            ;;
        *)
            echo "❌ Unknown option: $1"
            echo
            show_help
            exit 1
            ;;
    esac
done

# Default to help if no mode specified
if [ -z "$MODE" ]; then
    MODE="help"
fi

# Main execution
case "$MODE" in
    "github-hosted")
        check_git_repo
        check_workflows_exist
        switch_to_github_hosted $QUIET
        ;;
    "self-hosted")
        check_git_repo
        check_workflows_exist
        switch_to_self_hosted $FORCE $QUIET
        ;;
    "status")
        check_git_repo
        check_workflows_exist
        show_status
        ;;
    "billing")
        show_billing_info
        ;;
    "help"|"-h"|"--help")
        show_help
        ;;
    *)
        echo "❌ Unknown mode: $MODE"
        echo
        show_help
        exit 1
        ;;
esac