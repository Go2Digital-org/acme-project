#!/bin/bash
# ================================================================
# ACME Corp CSR Platform - Tenant Management Script
# ================================================================
# Manages multi-tenant operations including creation, deletion,
# migration, and maintenance of tenant databases and resources

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/../.." && pwd)"
APP_CONTAINER="${APP_CONTAINER:-acme-app}"
MYSQL_CONTAINER="${MYSQL_CONTAINER:-acme-mysql}"
REDIS_CONTAINER="${REDIS_CONTAINER:-acme-redis}"
MEILISEARCH_CONTAINER="${MEILISEARCH_CONTAINER:-acme-meilisearch}"

# Database configuration
DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-root}"
DB_PREFIX="${TENANT_DATABASE_PREFIX:-tenant_}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

# Print functions
print_info() { echo -e "${BLUE}[INFO]${NC} $1"; }
print_success() { echo -e "${GREEN}[SUCCESS]${NC} $1"; }
print_warning() { echo -e "${YELLOW}[WARNING]${NC} $1"; }
print_error() { echo -e "${RED}[ERROR]${NC} $1"; }
print_section() { echo -e "\n${CYAN}=== $1 ===${NC}\n"; }

# Check if containers are running
check_containers() {
    local required_containers=("$APP_CONTAINER" "$MYSQL_CONTAINER")

    for container in "${required_containers[@]}"; do
        if ! docker ps --format '{{.Names}}' | grep -q "^${container}$"; then
            print_error "Container ${container} is not running"
            print_info "Start the platform with: ./docker/scripts/docker-manager.sh start"
            return 1
        fi
    done

    return 0
}

# Execute MySQL command
mysql_exec() {
    docker exec -i "$MYSQL_CONTAINER" mysql -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "$1" 2>/dev/null
}

# Execute artisan command
artisan_exec() {
    docker exec -i "$APP_CONTAINER" php artisan "$@"
}

# Create a new tenant
create_tenant() {
    local tenant_id="$1"
    local tenant_name="${2:-$tenant_id}"
    local tenant_domain="${3:-$tenant_id.localhost}"
    local admin_email="${4:-admin@$tenant_domain}"

    print_section "Creating Tenant: $tenant_id"

    # Validate tenant ID
    if [[ ! "$tenant_id" =~ ^[a-z0-9][a-z0-9-]{2,30}[a-z0-9]$ ]]; then
        print_error "Invalid tenant ID. Must be lowercase alphanumeric with hyphens (3-32 chars)"
        return 1
    fi

    # Check if tenant already exists
    local db_name="${DB_PREFIX}${tenant_id}"
    if mysql_exec "SHOW DATABASES LIKE '${db_name}';" | grep -q "$db_name"; then
        print_error "Tenant database ${db_name} already exists"
        return 1
    fi

    print_info "Creating tenant database: ${db_name}"

    # Create tenant database
    mysql_exec "CREATE DATABASE IF NOT EXISTS \`${db_name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

    # Grant privileges
    mysql_exec "GRANT ALL PRIVILEGES ON \`${db_name}\`.* TO '${DB_USERNAME}'@'%';"
    mysql_exec "FLUSH PRIVILEGES;"

    print_success "Database created: ${db_name}"

    # Run migrations for tenant
    print_info "Running migrations for tenant..."
    artisan_exec "tenants:migrate" "--tenant=${tenant_id}" || {
        # Fallback to manual migration if tenant command doesn't exist
        docker exec -i "$APP_CONTAINER" bash -c "
            export DB_DATABASE='${db_name}'
            php artisan migrate --force --database=tenant
        "
    }

    # Seed tenant database
    print_info "Seeding tenant database..."
    artisan_exec "tenants:seed" "--tenant=${tenant_id}" 2>/dev/null || {
        docker exec -i "$APP_CONTAINER" bash -c "
            export DB_DATABASE='${db_name}'
            php artisan db:seed --force --database=tenant
        "
    }

    # Create Meilisearch indexes for tenant
    print_info "Creating search indexes for tenant..."
    docker exec -i "$APP_CONTAINER" bash -c "
        export TENANT_ID='${tenant_id}'
        if [ -f /app/scripts/meilisearch-setup.sh ]; then
            bash /app/scripts/meilisearch-setup.sh
        fi
    "

    # Create tenant storage directories
    print_info "Creating storage directories..."
    docker exec -i "$APP_CONTAINER" bash -c "
        mkdir -p /app/storage/app/tenants/${tenant_id}/{uploads,exports,cache,logs}
        chown -R www-data:www-data /app/storage/app/tenants/${tenant_id}
        chmod -R 775 /app/storage/app/tenants/${tenant_id}
    "

    # Register tenant in central database
    print_info "Registering tenant in central database..."
    artisan_exec tinker --execute="
        \$tenant = new \App\Models\Tenant();
        \$tenant->id = '${tenant_id}';
        \$tenant->name = '${tenant_name}';
        \$tenant->domain = '${tenant_domain}';
        \$tenant->database = '${db_name}';
        \$tenant->status = 'active';
        \$tenant->data = [
            'admin_email' => '${admin_email}',
            'created_at' => now(),
            'features' => ['campaigns', 'donations', 'reports']
        ];
        \$tenant->save();
        echo 'Tenant registered';
    " 2>/dev/null || print_warning "Could not register tenant in central database (model may not exist)"

    # Clear caches
    print_info "Clearing caches..."
    artisan_exec cache:clear
    artisan_exec config:clear

    print_success "Tenant ${tenant_id} created successfully!"
    print_info "Domain: ${tenant_domain}"
    print_info "Database: ${db_name}"
    print_info "Admin Email: ${admin_email}"

    # Output DNS configuration hint
    print_section "DNS Configuration"
    print_info "For production, add the following DNS record:"
    print_info "  ${tenant_domain}. IN A YOUR_SERVER_IP"
    print_info ""
    print_info "For local development, add to /etc/hosts:"
    print_info "  127.0.0.1 ${tenant_domain}"
}

# Delete a tenant
delete_tenant() {
    local tenant_id="$1"
    local force="${2:-false}"

    print_section "Deleting Tenant: $tenant_id"

    local db_name="${DB_PREFIX}${tenant_id}"

    # Check if database exists
    if ! mysql_exec "SHOW DATABASES LIKE '${db_name}';" | grep -q "$db_name"; then
        print_error "Tenant database ${db_name} does not exist"
        return 1
    fi

    # Confirm deletion
    if [ "$force" != "true" ]; then
        print_warning "This will permanently delete all data for tenant: ${tenant_id}"
        print_warning "Database to be deleted: ${db_name}"
        read -p "Are you sure? (type 'yes' to confirm): " confirmation

        if [ "$confirmation" != "yes" ]; then
            print_info "Deletion cancelled"
            return 0
        fi
    fi

    # Backup before deletion
    if [ "$force" != "true" ]; then
        print_info "Creating backup before deletion..."
        backup_tenant "$tenant_id"
    fi

    # Drop database
    print_info "Dropping database: ${db_name}"
    mysql_exec "DROP DATABASE IF EXISTS \`${db_name}\`;"

    # Remove Meilisearch indexes
    print_info "Removing search indexes..."
    docker exec -i "$MEILISEARCH_CONTAINER" bash -c "
        curl -X DELETE 'http://localhost:7700/indexes/tenant_${tenant_id}_campaigns' \
             -H 'Authorization: Bearer \${MEILISEARCH_KEY}'
        curl -X DELETE 'http://localhost:7700/indexes/tenant_${tenant_id}_donations' \
             -H 'Authorization: Bearer \${MEILISEARCH_KEY}'
        curl -X DELETE 'http://localhost:7700/indexes/tenant_${tenant_id}_reports' \
             -H 'Authorization: Bearer \${MEILISEARCH_KEY}'
        curl -X DELETE 'http://localhost:7700/indexes/tenant_${tenant_id}_activities' \
             -H 'Authorization: Bearer \${MEILISEARCH_KEY}'
    " 2>/dev/null || true

    # Remove storage directories
    print_info "Removing storage directories..."
    docker exec -i "$APP_CONTAINER" rm -rf "/app/storage/app/tenants/${tenant_id}"

    # Remove from central database
    print_info "Removing tenant from central database..."
    artisan_exec tinker --execute="
        \App\Models\Tenant::where('id', '${tenant_id}')->delete();
        echo 'Tenant removed from central database';
    " 2>/dev/null || print_warning "Could not remove tenant from central database"

    # Clear Redis cache for tenant
    print_info "Clearing tenant cache..."
    docker exec -i "$REDIS_CONTAINER" redis-cli --scan --pattern "tenant:${tenant_id}:*" | \
        xargs -L 100 docker exec -i "$REDIS_CONTAINER" redis-cli DEL 2>/dev/null || true

    print_success "Tenant ${tenant_id} deleted successfully!"
}

# List all tenants
list_tenants() {
    print_section "Tenant List"

    print_info "Scanning for tenant databases..."

    # Get all databases with tenant prefix
    databases=$(mysql_exec "SHOW DATABASES LIKE '${DB_PREFIX}%';" | grep "^${DB_PREFIX}")

    if [ -z "$databases" ]; then
        print_warning "No tenants found"
        return 0
    fi

    # Display tenant information
    echo -e "${CYAN}ID\t\tDatabase\t\tSize${NC}"
    echo "----------------------------------------"

    while IFS= read -r db; do
        tenant_id="${db#$DB_PREFIX}"

        # Get database size
        size=$(mysql_exec "
            SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
            FROM information_schema.TABLES
            WHERE table_schema = '${db}';
        " | tail -n 1)

        echo -e "${tenant_id}\t${db}\t${size:-0} MB"
    done <<< "$databases"

    # Count total tenants
    total=$(echo "$databases" | wc -l)
    echo "----------------------------------------"
    print_info "Total tenants: ${total}"
}

# Backup a tenant
backup_tenant() {
    local tenant_id="$1"
    local backup_dir="${2:-${PROJECT_ROOT}/backups/tenants}"

    print_section "Backing Up Tenant: $tenant_id"

    local db_name="${DB_PREFIX}${tenant_id}"
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_path="${backup_dir}/${tenant_id}/${timestamp}"

    # Create backup directory
    mkdir -p "$backup_path"

    # Backup database
    print_info "Backing up database..."
    docker exec "$MYSQL_CONTAINER" mysqldump \
        -u"$DB_USERNAME" -p"$DB_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        "$db_name" > "${backup_path}/database.sql"

    # Backup storage
    print_info "Backing up storage..."
    docker exec "$APP_CONTAINER" tar czf - \
        "/app/storage/app/tenants/${tenant_id}" 2>/dev/null \
        > "${backup_path}/storage.tar.gz"

    # Export Meilisearch data
    print_info "Exporting search indexes..."
    docker exec "$MEILISEARCH_CONTAINER" curl -s \
        "http://localhost:7700/dumps" \
        -H "Authorization: Bearer ${MEILISEARCH_KEY}" \
        -X POST \
        -d "{\"uid\": \"tenant_${tenant_id}_backup_${timestamp}\"}" \
        > "${backup_path}/meilisearch_task.json" 2>/dev/null || true

    # Create backup manifest
    cat > "${backup_path}/manifest.json" <<EOF
{
    "tenant_id": "${tenant_id}",
    "timestamp": "${timestamp}",
    "database": "${db_name}",
    "backup_date": "$(date -Iseconds)",
    "files": {
        "database": "database.sql",
        "storage": "storage.tar.gz",
        "search": "meilisearch_task.json"
    }
}
EOF

    print_success "Backup completed: ${backup_path}"
}

# Restore a tenant
restore_tenant() {
    local backup_path="$1"
    local new_tenant_id="${2:-}"

    print_section "Restoring Tenant from Backup"

    # Check if backup exists
    if [ ! -f "${backup_path}/manifest.json" ]; then
        print_error "Invalid backup path or missing manifest.json"
        return 1
    fi

    # Read manifest
    local tenant_id=$(jq -r '.tenant_id' "${backup_path}/manifest.json")
    local db_name="${DB_PREFIX}${new_tenant_id:-$tenant_id}"

    if [ -n "$new_tenant_id" ]; then
        tenant_id="$new_tenant_id"
        print_info "Restoring as new tenant: ${tenant_id}"
    fi

    # Create database
    print_info "Creating database: ${db_name}"
    mysql_exec "CREATE DATABASE IF NOT EXISTS \`${db_name}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

    # Restore database
    print_info "Restoring database..."
    docker exec -i "$MYSQL_CONTAINER" mysql \
        -u"$DB_USERNAME" -p"$DB_PASSWORD" \
        "$db_name" < "${backup_path}/database.sql"

    # Restore storage
    print_info "Restoring storage..."
    docker exec -i "$APP_CONTAINER" tar xzf - -C / < "${backup_path}/storage.tar.gz"

    if [ -n "$new_tenant_id" ]; then
        # Rename storage directory if restoring as different tenant
        docker exec "$APP_CONTAINER" bash -c "
            mv /app/storage/app/tenants/${tenant_id} /app/storage/app/tenants/${new_tenant_id}
            chown -R www-data:www-data /app/storage/app/tenants/${new_tenant_id}
        "
    fi

    print_success "Tenant restored successfully!"
}

# Migrate tenant database
migrate_tenant() {
    local tenant_id="$1"
    local rollback="${2:-false}"

    print_section "Migrating Tenant: $tenant_id"

    local db_name="${DB_PREFIX}${tenant_id}"

    if [ "$rollback" == "true" ]; then
        print_info "Rolling back migrations..."
        docker exec -i "$APP_CONTAINER" bash -c "
            export DB_DATABASE='${db_name}'
            php artisan migrate:rollback --force --database=tenant
        "
    else
        print_info "Running migrations..."
        docker exec -i "$APP_CONTAINER" bash -c "
            export DB_DATABASE='${db_name}'
            php artisan migrate --force --database=tenant
        "
    fi

    print_success "Migration completed for tenant: ${tenant_id}"
}

# Run command for all tenants
run_for_all_tenants() {
    local command="$1"

    print_section "Running Command for All Tenants"
    print_info "Command: ${command}"

    # Get all tenant databases
    databases=$(mysql_exec "SHOW DATABASES LIKE '${DB_PREFIX}%';" | grep "^${DB_PREFIX}")

    if [ -z "$databases" ]; then
        print_warning "No tenants found"
        return 0
    fi

    # Execute command for each tenant
    while IFS= read -r db; do
        tenant_id="${db#$DB_PREFIX}"
        print_info "Processing tenant: ${tenant_id}"

        docker exec -i "$APP_CONTAINER" bash -c "
            export TENANT_ID='${tenant_id}'
            export DB_DATABASE='${db}'
            ${command}
        "
    done <<< "$databases"

    print_success "Command executed for all tenants"
}

# Show tenant information
show_tenant_info() {
    local tenant_id="$1"

    print_section "Tenant Information: $tenant_id"

    local db_name="${DB_PREFIX}${tenant_id}"

    # Check if database exists
    if ! mysql_exec "SHOW DATABASES LIKE '${db_name}';" | grep -q "$db_name"; then
        print_error "Tenant database ${db_name} does not exist"
        return 1
    fi

    # Database info
    print_info "Database: ${db_name}"

    # Table count
    table_count=$(mysql_exec "
        SELECT COUNT(*) FROM information_schema.TABLES
        WHERE table_schema = '${db_name}';
    " | tail -n 1)
    print_info "Tables: ${table_count}"

    # Database size
    size=$(mysql_exec "
        SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.TABLES
        WHERE table_schema = '${db_name}';
    " | tail -n 1)
    print_info "Size: ${size:-0} MB"

    # Storage usage
    if docker exec "$APP_CONTAINER" test -d "/app/storage/app/tenants/${tenant_id}"; then
        storage_size=$(docker exec "$APP_CONTAINER" du -sh "/app/storage/app/tenants/${tenant_id}" | cut -f1)
        print_info "Storage: ${storage_size}"
    else
        print_info "Storage: No tenant storage directory"
    fi

    # Search indexes
    print_info "Search Indexes:"
    docker exec "$MEILISEARCH_CONTAINER" curl -s \
        "http://localhost:7700/indexes" \
        -H "Authorization: Bearer ${MEILISEARCH_KEY}" | \
        jq -r ".results[] | select(.uid | startswith(\"tenant_${tenant_id}_\")) | .uid" 2>/dev/null | \
        while read -r index; do
            echo "  - ${index}"
        done || print_warning "  Could not retrieve index information"
}

# Show help
show_help() {
    cat << EOF
ACME Corp CSR Platform - Tenant Management

Usage: $0 [command] [options]

Commands:
    create <tenant_id> [name] [domain] [admin_email]
        Create a new tenant with database and resources

    delete <tenant_id> [--force]
        Delete a tenant and all associated data

    list
        List all existing tenants

    info <tenant_id>
        Show detailed information about a tenant

    backup <tenant_id> [backup_dir]
        Create a backup of tenant data

    restore <backup_path> [new_tenant_id]
        Restore a tenant from backup

    migrate <tenant_id> [--rollback]
        Run migrations for a specific tenant

    run-all <command>
        Execute a command for all tenants

    help
        Show this help message

Examples:
    $0 create acme-corp "ACME Corporation" "acme.example.com"
    $0 delete acme-corp
    $0 list
    $0 backup acme-corp /backups
    $0 migrate acme-corp
    $0 run-all "php artisan cache:clear"

Environment Variables:
    APP_CONTAINER       Application container name (default: acme-app)
    MYSQL_CONTAINER     MySQL container name (default: acme-mysql)
    DB_USERNAME         Database username (default: root)
    DB_PASSWORD         Database password (default: root)
    TENANT_DATABASE_PREFIX  Tenant database prefix (default: tenant_)

EOF
}

# Main execution
main() {
    # Check if containers are running
    if ! check_containers; then
        exit 1
    fi

    local command="${1:-help}"
    shift || true

    case "$command" in
        create)
            create_tenant "$@"
            ;;
        delete)
            delete_tenant "$@"
            ;;
        list)
            list_tenants
            ;;
        info)
            show_tenant_info "$@"
            ;;
        backup)
            backup_tenant "$@"
            ;;
        restore)
            restore_tenant "$@"
            ;;
        migrate)
            migrate_tenant "$@"
            ;;
        run-all)
            run_for_all_tenants "$@"
            ;;
        help|--help|-h)
            show_help
            ;;
        *)
            print_error "Unknown command: $command"
            show_help
            exit 1
            ;;
    esac
}

# Run main function
main "$@"