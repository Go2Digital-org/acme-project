#!/bin/bash

# CI Workflow Switching Script
# Allows easy switching between optimized and comprehensive CI workflows

set -e

WORKFLOWS_DIR=".github/workflows"
COMPREHENSIVE_CI="ci.yml"
OPTIMIZED_CI="ci-optimized.yml"

show_help() {
    cat << EOF
CI Workflow Switcher

Usage: $0 [mode]

Modes:
  fast        Switch to optimized CI (ci-optimized.yml) - 5-8 minutes
  full        Switch to comprehensive CI (ci.yml) - 10-15 minutes
  status      Show current active workflow
  help        Show this help message

Examples:
  $0 fast     # Use fast CI for development
  $0 full     # Use comprehensive CI for releases
  $0 status   # Check which workflow is active

The script temporarily renames workflows to control which one runs.
Only one workflow will be active at a time.
EOF
}

check_git_repo() {
    if [ ! -d ".git" ]; then
        echo "❌ Error: Not in a Git repository"
        exit 1
    fi
}

check_workflows_exist() {
    if [ ! -f "$WORKFLOWS_DIR/$COMPREHENSIVE_CI" ] || [ ! -f "$WORKFLOWS_DIR/$OPTIMIZED_CI" ]; then
        echo "❌ Error: Required workflow files not found"
        echo "   Expected: $WORKFLOWS_DIR/$COMPREHENSIVE_CI"
        echo "   Expected: $WORKFLOWS_DIR/$OPTIMIZED_CI"
        exit 1
    fi
}

get_active_workflow() {
    local active=""
    
    if [ -f "$WORKFLOWS_DIR/$COMPREHENSIVE_CI" ] && ! [[ "$COMPREHENSIVE_CI" =~ \.disabled$ ]]; then
        if [ -f "$WORKFLOWS_DIR/$OPTIMIZED_CI" ] && ! [[ "$OPTIMIZED_CI" =~ \.disabled$ ]]; then
            active="both (conflicting!)"
        else
            active="comprehensive ($COMPREHENSIVE_CI)"
        fi
    elif [ -f "$WORKFLOWS_DIR/$OPTIMIZED_CI" ] && ! [[ "$OPTIMIZED_CI" =~ \.disabled$ ]]; then
        active="optimized ($OPTIMIZED_CI)"
    else
        active="none (all disabled)"
    fi
    
    echo "$active"
}

disable_workflow() {
    local workflow="$1"
    if [ -f "$WORKFLOWS_DIR/$workflow" ]; then
        mv "$WORKFLOWS_DIR/$workflow" "$WORKFLOWS_DIR/${workflow}.disabled"
        echo "   Disabled: $workflow"
    fi
}

enable_workflow() {
    local workflow="$1"
    if [ -f "$WORKFLOWS_DIR/${workflow}.disabled" ]; then
        mv "$WORKFLOWS_DIR/${workflow}.disabled" "$WORKFLOWS_DIR/$workflow"
        echo "   Enabled: $workflow"
    elif [ -f "$WORKFLOWS_DIR/$workflow" ]; then
        echo "   Already enabled: $workflow"
    else
        echo "   ❌ Workflow not found: $workflow"
        return 1
    fi
}

switch_to_fast() {
    echo "🚀 Switching to FAST CI (optimized)..."
    echo
    
    disable_workflow "$COMPREHENSIVE_CI"
    enable_workflow "$OPTIMIZED_CI"
    
    echo
    echo "✅ Fast CI activated!"
    echo "   Runtime: ~5-8 minutes"
    echo "   Features: Essential checks, combined jobs, aggressive caching"
    echo "   Best for: Feature development, quick feedback"
}

switch_to_full() {
    echo "🔍 Switching to FULL CI (comprehensive)..."
    echo
    
    disable_workflow "$OPTIMIZED_CI"
    enable_workflow "$COMPREHENSIVE_CI"
    
    echo
    echo "✅ Comprehensive CI activated!"
    echo "   Runtime: ~10-15 minutes"  
    echo "   Features: Full test suite, detailed analysis, optional coverage"
    echo "   Best for: Main branch, releases, thorough validation"
}

show_status() {
    local active=$(get_active_workflow)
    
    echo "📊 CI Workflow Status"
    echo "===================="
    echo "Active workflow: $active"
    echo
    
    echo "Available workflows:"
    if [ -f "$WORKFLOWS_DIR/$COMPREHENSIVE_CI" ]; then
        echo "  ✅ $COMPREHENSIVE_CI (comprehensive)"
    else
        echo "  💤 $COMPREHENSIVE_CI.disabled"
    fi
    
    if [ -f "$WORKFLOWS_DIR/$OPTIMIZED_CI" ]; then
        echo "  ✅ $OPTIMIZED_CI (optimized)"
    else
        echo "  💤 $OPTIMIZED_CI.disabled"
    fi
    
    echo
    echo "💡 Use '$0 fast' or '$0 full' to switch workflows"
}

# Main execution
case "${1:-help}" in
    "fast"|"optimized")
        check_git_repo
        check_workflows_exist
        switch_to_fast
        ;;
    "full"|"comprehensive")
        check_git_repo
        check_workflows_exist
        switch_to_full
        ;;
    "status"|"current")
        check_git_repo
        check_workflows_exist
        show_status
        ;;
    "help"|"-h"|"--help")
        show_help
        ;;
    *)
        echo "❌ Unknown mode: $1"
        echo
        show_help
        exit 1
        ;;
esac