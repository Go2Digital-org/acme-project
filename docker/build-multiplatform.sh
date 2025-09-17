#!/bin/bash

# ================================================================
# ACME Corp CSR Platform - Multi-platform Docker Build Script
# Supports AMD64 and ARM64 architectures
# ================================================================

set -euo pipefail

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
REGISTRY="${REGISTRY:-ghcr.io/acme-corp}"
IMAGE_NAME="${IMAGE_NAME:-acme-csr-platform}"
VERSION="${VERSION:-latest}"
PLATFORMS="${PLATFORMS:-linux/amd64,linux/arm64}"
PUSH="${PUSH:-false}"
CACHE_FROM="${CACHE_FROM:-true}"
BUILD_ARGS="${BUILD_ARGS:-}"

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

# Function to check prerequisites
check_prerequisites() {
    print_status "Checking prerequisites..."
    
    # Check if Docker is installed and running
    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed or not in PATH"
        exit 1
    fi
    
    # Check if Docker is running
    if ! docker info &> /dev/null; then
        print_error "Docker daemon is not running"
        exit 1
    fi
    
    # Check if buildx is available
    if ! docker buildx version &> /dev/null; then
        print_error "Docker buildx is not available"
        exit 1
    fi
    
    print_success "Prerequisites check passed"
}

# Function to setup buildx builder
setup_builder() {
    print_status "Setting up buildx builder..."
    
    # Create and use a new builder instance
    BUILDER_NAME="acme-multiplatform-builder"
    
    # Remove existing builder if it exists
    docker buildx rm $BUILDER_NAME &> /dev/null || true
    
    # Create new builder with multi-platform support
    docker buildx create \
        --name $BUILDER_NAME \
        --driver docker-container \
        --platform $PLATFORMS \
        --use
    
    # Bootstrap the builder
    docker buildx inspect --bootstrap
    
    print_success "Buildx builder setup completed"
}

# Function to build multi-platform images
build_images() {
    print_status "Building multi-platform Docker images..."
    
    # Common build arguments
    local common_args=(
        --platform "$PLATFORMS"
        --builder "acme-multiplatform-builder"
        --progress plain
    )
    
    # Add cache arguments if enabled
    if [ "$CACHE_FROM" = "true" ]; then
        common_args+=(
            --cache-from "type=registry,ref=${REGISTRY}/${IMAGE_NAME}:cache"
            --cache-to "type=registry,ref=${REGISTRY}/${IMAGE_NAME}:cache,mode=max"
        )
    fi
    
    # Add push argument if enabled
    if [ "$PUSH" = "true" ]; then
        common_args+=(--push)
    else
        common_args+=(--load)
    fi
    
    # Parse additional build arguments
    if [ -n "$BUILD_ARGS" ]; then
        IFS=',' read -ra ARGS <<< "$BUILD_ARGS"
        for arg in "${ARGS[@]}"; do
            common_args+=(--build-arg "$arg")
        done
    fi
    
    # Build development image
    print_status "Building development image..."
    docker buildx build \
        "${common_args[@]}" \
        --target frankenphp_dev \
        --tag "${REGISTRY}/${IMAGE_NAME}:${VERSION}-dev" \
        --tag "${REGISTRY}/${IMAGE_NAME}:latest-dev" \
        .
    
    # Build testing image
    print_status "Building testing image..."
    docker buildx build \
        "${common_args[@]}" \
        --target frankenphp_test \
        --tag "${REGISTRY}/${IMAGE_NAME}:${VERSION}-test" \
        --tag "${REGISTRY}/${IMAGE_NAME}:latest-test" \
        .
    
    # Build production image
    print_status "Building production image..."
    docker buildx build \
        "${common_args[@]}" \
        --target frankenphp_prod \
        --tag "${REGISTRY}/${IMAGE_NAME}:${VERSION}" \
        --tag "${REGISTRY}/${IMAGE_NAME}:latest" \
        .
    
    print_success "Multi-platform images built successfully"
}

# Function to cleanup
cleanup() {
    print_status "Cleaning up..."
    
    # Remove the builder
    docker buildx rm acme-multiplatform-builder &> /dev/null || true
    
    print_success "Cleanup completed"
}

# Function to show usage
show_usage() {
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Options:"
    echo "  -r, --registry REGISTRY    Container registry (default: ghcr.io/acme-corp)"
    echo "  -i, --image IMAGE          Image name (default: acme-csr-platform)"
    echo "  -v, --version VERSION      Image version (default: latest)"
    echo "  -p, --platforms PLATFORMS  Target platforms (default: linux/amd64,linux/arm64)"
    echo "  --push                     Push images to registry"
    echo "  --no-cache                 Disable cache"
    echo "  --build-args ARGS          Additional build arguments (comma-separated)"
    echo "  -h, --help                 Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0                                    # Build locally"
    echo "  $0 --push                            # Build and push to registry"
    echo "  $0 --version v1.0.0 --push          # Build specific version and push"
    echo "  $0 --platforms linux/amd64          # Build only for AMD64"
    echo "  $0 --build-args 'NODE_VERSION=18'   # Pass custom build arguments"
}

# Main execution
main() {
    # Parse command line arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -r|--registry)
                REGISTRY="$2"
                shift 2
                ;;
            -i|--image)
                IMAGE_NAME="$2"
                shift 2
                ;;
            -v|--version)
                VERSION="$2"
                shift 2
                ;;
            -p|--platforms)
                PLATFORMS="$2"
                shift 2
                ;;
            --push)
                PUSH="true"
                shift
                ;;
            --no-cache)
                CACHE_FROM="false"
                shift
                ;;
            --build-args)
                BUILD_ARGS="$2"
                shift 2
                ;;
            -h|--help)
                show_usage
                exit 0
                ;;
            *)
                print_error "Unknown option: $1"
                show_usage
                exit 1
                ;;
        esac
    done
    
    # Print configuration
    print_status "Build Configuration:"
    echo "  Registry: $REGISTRY"
    echo "  Image: $IMAGE_NAME"
    echo "  Version: $VERSION"
    echo "  Platforms: $PLATFORMS"
    echo "  Push: $PUSH"
    echo "  Cache: $CACHE_FROM"
    echo "  Build Args: $BUILD_ARGS"
    echo ""
    
    # Execute build process
    check_prerequisites
    setup_builder
    
    # Set trap for cleanup on exit
    trap cleanup EXIT
    
    build_images
    
    print_success "Multi-platform build completed successfully!"
    
    if [ "$PUSH" = "true" ]; then
        print_success "Images pushed to $REGISTRY/$IMAGE_NAME"
    else
        print_warning "Images built locally. Use --push to push to registry."
    fi
}

# Run main function with all arguments
main "$@"