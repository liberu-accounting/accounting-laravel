#!/bin/bash
# Setup script for Liberu Accounting.
# Supports Standalone, Docker, and Kubernetes deployments.
# Version: 2.1
# Requirements: bash 4.0+, curl/wget, PHP 8.5+, composer, npm (for frontend)

set -euo pipefail

# ── Colors and Formatting ──────────────────────────────────────────────────────
RED='\e[91m'
GREEN='\e[92m'
YELLOW='\e[93m'
BLUE='\e[94m'
MAGENTA='\e[95m'
CYAN='\e[96m'
RESET='\e[0m'
BOLD='\e[1m'

print_header()  { echo ""; echo -e "${BOLD}=================================="; echo -e "  $1"; echo -e "==================================${RESET}"; echo ""; }
print_error()   { echo -e "${RED}ERROR: $1${RESET}" >&2; }
print_success() { echo -e "${GREEN}OK: $1${RESET}"; }
print_info()    { echo -e "${BLUE}INFO: $1${RESET}"; }
print_warning() { echo -e "${YELLOW}WARN: $1${RESET}"; }
print_debug()   { [ "${DEBUG:-0}" = "1" ] && echo -e "${MAGENTA}DEBUG: $1${RESET}" || true; }

command_exists() { command -v "$1" >/dev/null 2>&1; }

# ── Global Variables ───────────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENVIRONMENT="${ENVIRONMENT:-production}"
DEBUG="${DEBUG:-0}"
SKIP_TESTS="${SKIP_TESTS:-0}"
INSTALL_HORIZON="${INSTALL_HORIZON:-1}"
INSTALL_REVERB="${INSTALL_REVERB:-1}"
PHP_CMD=""

# ── PHP Binary Detection ───────────────────────────────────────────────────────
# Detect the first PHP 8.5+ binary, checking common versioned paths first so
# systems like AlmaLinux (where `php` may still point to 8.3) work out of the
# box without requiring users to change their PATH.
detect_php() {
    local required="8.5"
    local candidates=(
        php85 php8.5
        /usr/bin/php85 /usr/bin/php8.5
        /usr/local/bin/php85 /usr/local/bin/php8.5
        /opt/php85/bin/php /opt/php8.5/bin/php
        php
    )

    for candidate in "${candidates[@]}"; do
        if command -v "$candidate" >/dev/null 2>&1; then
            if "$candidate" -r "exit(version_compare(PHP_VERSION, '${required}', '>=') ? 0 : 1);" 2>/dev/null; then
                PHP_CMD="$candidate"
                return 0
            fi
        fi
    done
    return 1
}

# ── PHP Version Check ──────────────────────────────────────────────────────────
check_php_version() {
    local required="8.5"

    if detect_php; then
        local full_ver
        full_ver=$("$PHP_CMD" -r 'echo PHP_VERSION;')
        print_success "PHP ${full_ver} detected (${PHP_CMD})"
    else
        print_error "PHP ${required}+ is required but not found."
        print_info  "On AlmaLinux/RHEL: sudo dnf install php8.5 php8.5-cli"
        print_info  "On Ubuntu/Debian:  sudo apt install php8.5-cli"
        exit 1
    fi
}

# ── Dependency Validation ──────────────────────────────────────────────────────
validate_dependencies() {
    print_header "DEPENDENCY VALIDATION"
    local missing_deps=0

    if ! detect_php; then
        print_warning "PHP 8.5+ not found"
        ((missing_deps++))
    else
        print_success "PHP 8.5+ found: ${PHP_CMD}"
    fi

    if ! command_exists composer; then
        print_warning "Composer not found (will download)"
    else
        print_success "Composer found"
    fi

    if ! command_exists npm; then
        print_warning "npm not found (skipping frontend build)"
    else
        print_success "npm found: $(npm --version)"
    fi

    if ! command_exists git; then
        print_warning "git not found"
    else
        print_success "git found"
    fi

    if [ $missing_deps -gt 0 ]; then
        print_error "$missing_deps critical dependencies missing"
        exit 1
    fi
}

# ── Composer ───────────────────────────────────────────────────────────────────
COMPOSER_CMD="composer"

ensure_composer() {
    if command_exists composer; then
        COMPOSER_CMD="composer"
        local version=$(composer --version 2>/dev/null | head -1)
        print_success "$version"
        return 0
    fi

    if ! command_exists curl; then
        print_error "curl is required to download Composer. Install curl or Composer manually."
        return 1
    fi

    print_info "Downloading Composer installer..."
    if curl -sS https://getcomposer.org/installer | "${PHP_CMD:-php}" -- --quiet; then
        if [ -f "composer.phar" ]; then
            COMPOSER_CMD="${PHP_CMD:-php} composer.phar"
            print_success "composer.phar downloaded successfully"
            return 0
        fi
    fi

    print_error "Failed to download Composer."
    return 1
}

install_composer_dependencies() {
    print_header "COMPOSER INSTALL"

    if [ -d "vendor" ] && [ -f "vendor/autoload.php" ]; then
        print_info "vendor/ directory already exists"
        read -rp "Reinstall dependencies? (y/n): " reply
        [[ ! $reply =~ ^[Yy]$ ]] && { print_success "Skipped"; return 0; }
    fi

    ensure_composer || return 1

    print_info "Installing dependencies (this may take a few minutes)..."
    if eval "$COMPOSER_CMD install --no-interaction --prefer-dist --optimize-autoloader"; then
        print_success "Composer dependencies installed successfully"
    else
        print_error "Composer install failed"
        return 1
    fi
}

# ── Node/NPM ───────────────────────────────────────────────────────────────────
install_npm_dependencies() {
    print_header "NPM INSTALL"

    if ! command_exists npm; then
        print_warning "npm is not installed. Skipping frontend setup."
        print_info "Install Node.js from: https://nodejs.org/"
        return 0
    fi

    print_info "npm version: $(npm --version)"

    if [ -d "node_modules" ]; then
        print_info "node_modules/ directory already exists"
        read -rp "Reinstall dependencies? (y/n): " reply
        [[ ! $reply =~ ^[Yy]$ ]] && { print_success "Skipped"; return 0; }
    fi

    print_info "Installing Node dependencies (this may take a few minutes)..."
    if npm install; then
        print_success "NPM dependencies installed successfully"
    else
        print_error "npm install failed"
        return 1
    fi
}

build_frontend_assets() {
    print_header "NPM BUILD"

    if ! command_exists npm; then
        print_warning "npm not found — skipping frontend build"
        return 0
    fi

    print_info "Building frontend assets..."
    if npm run build; then
        print_success "Frontend assets built successfully"
    else
        print_error "npm run build failed"
        return 1
    fi
}

# ── Laravel Setup ──────────────────────────────────────────────────────────────
setup_env() {
    local env_file=".env"
    
    if [ ! -f "$env_file" ]; then
        if [ ! -f ".env.example" ]; then
            print_error ".env.example not found"
            return 1
        fi
        cp .env.example "$env_file"
        print_success "Created $env_file from .env.example"
    fi

    # Remove duplicate SESSION_DRIVER if present
    if grep -c "^SESSION_DRIVER=" .env 2>/dev/null | grep -q "^[2-9]$"; then
        awk '!seen["SESSION_DRIVER"]++ || !/^SESSION_DRIVER=/' .env > .env.tmp && mv .env.tmp .env
        print_info "Removed duplicate SESSION_DRIVER entries from .env"
    fi

    # Validate critical environment variables
    if ! grep -q "^APP_KEY=" "$env_file" || grep -q "^APP_KEY=$" "$env_file"; then
        print_warning "APP_KEY is not set in .env"
    else
        print_success "APP_KEY is configured"
    fi
}

laravel_setup() {
    print_header "LARAVEL SETUP"

    # Generate key if missing
    if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
        print_info "Generating application key..."
        "$PHP_CMD" artisan key:generate --force
        print_success "Application key generated"
    else
        print_success "APP_KEY already configured"
    fi

    # Database migrations
    print_info ""
    read -rp "Run database migrations with seeding? (y/n): " run_migrations
    if [[ $run_migrations =~ ^[Yy]$ ]]; then
        print_info "Running migrations..."
        "$PHP_CMD" artisan migrate:fresh --seed --force
        print_success "Database migrated and seeded"
    else
        print_info "Running standard migrations..."
        "$PHP_CMD" artisan migrate --force
        print_success "Migrations complete"
    fi

    # Horizon installation (optional)
    if [ "$INSTALL_HORIZON" = "1" ]; then
        print_info "Setting up Horizon..."
        if "$PHP_CMD" artisan horizon:install 2>/dev/null; then
            print_success "Horizon configured"
        fi
    fi

    # Reverb installation (optional)
    if [ "$INSTALL_REVERB" = "1" ]; then
        print_info "Setting up Reverb..."
        if "$PHP_CMD" artisan reverb:install 2>/dev/null; then
            print_success "Reverb configured"
        fi
    fi

    # Shield permissions
    print_info "Generating Filament Shield permissions..."
    if "$PHP_CMD" artisan shield:generate --all 2>/dev/null; then
        print_success "Shield permissions generated"
    fi

    # Storage link
    print_info "Creating storage link..."
    "$PHP_CMD" artisan storage:link 2>/dev/null || true

    # Cache optimization
    print_info "Optimizing caches..."
    "$PHP_CMD" artisan optimize:clear
    "$PHP_CMD" artisan event:cache
    "$PHP_CMD" artisan config:cache
    "$PHP_CMD" artisan route:cache
    print_success "Caches optimized"
}

# ── Test Suite ─────────────────────────────────────────────────────────────────
run_tests() {
    print_header "RUNNING TEST SUITE"

    [ "$SKIP_TESTS" = "1" ] && { print_info "Tests skipped (SKIP_TESTS=1)"; return 0; }

    local test_bin="./vendor/bin/pest"
    [ ! -f "$test_bin" ] && test_bin="./vendor/bin/phpunit"

    if [ ! -f "$test_bin" ]; then
        print_warning "Test runner not found. Run: composer install"
        return 1
    fi

    print_info "Running test suite (this may take several minutes)..."
    if $test_bin; then
        print_success "All tests passed! ✨"
        return 0
    else
        print_warning "Some tests failed — review output above"
        return 1
    fi
}

# ── Linting & Code Quality ────────────────────────────────────────────────────
run_linting() {
    print_header "CODE QUALITY CHECKS"

    if ! command_exists ./vendor/bin/pint; then
        print_info "Pint not found — skipping"
        return 0
    fi

    print_info "Running Laravel Pint..."
    if ./vendor/bin/pint --test; then
        print_success "Code style check passed"
    else
        print_warning "Code style issues found. Run: ./vendor/bin/pint"
    fi
}

# ── Standalone ─────────────────────────────────────────────────────────────────
install_standalone() {
    print_header "STANDALONE INSTALLATION"
    print_info "PHP $("$PHP_CMD" -r 'echo PHP_VERSION;' 2>/dev/null || echo 'unknown') | User: $(whoami) | Environment: $ENVIRONMENT"

    check_php_version
    validate_dependencies
    setup_env

    # Database configuration check
    if ! grep -q "DB_HOST=\|DB_DATABASE=" .env 2>/dev/null; then
        print_warning "Database configuration not found in .env"
        print_info "Please configure database settings in .env"
        read -rp "Database credentials configured? (y/n): " ready
        [[ ! $ready =~ ^[Yy]$ ]] && { 
            print_warning "Setup paused. Edit .env and re-run setup.sh"
            exit 0
        }
    else
        print_success "Database configuration detected"
    fi

    # Installation steps
    install_composer_dependencies || { print_error "Installation failed at composer"; exit 1; }
    
    if command_exists npm; then
        install_npm_dependencies || print_warning "npm install failed — continuing"
        build_frontend_assets || print_warning "npm build failed — continuing"
    fi

    laravel_setup

    # Optional: code quality checks
    echo ""
    read -rp "Run code quality checks? (y/n): " check_quality
    [[ $check_quality =~ ^[Yy]$ ]] && run_linting

    # Optional: tests
    echo ""
    read -rp "Run test suite? (y/n): " run_t
    [[ $run_t =~ ^[Yy]$ ]] && run_tests

    # Summary
    echo ""
    print_success "Installation complete! 🎉"
    echo ""
    print_info "Next steps:"
    echo ""
    echo "  Development server:    ${CYAN}${PHP_CMD} artisan serve${RESET}"
    echo "  Production server:      ${CYAN}${PHP_CMD} artisan octane:start${RESET}"
    echo "  Queue worker:           ${CYAN}${PHP_CMD} artisan horizon${RESET}"
    echo "  WebSockets:             ${CYAN}${PHP_CMD} artisan reverb:start${RESET}"
    echo "  Run tests:              ${CYAN}./vendor/bin/pest${RESET}"
    echo ""

    read -rp "Start development server now? (y/n): " start
    if [[ $start =~ ^[Yy]$ ]]; then
        print_info "Starting Octane server..."
        "$PHP_CMD" artisan octane:start
    fi
}

# ── Docker ─────────────────────────────────────────────────────────────────────
install_docker() {
    print_header "DOCKER INSTALLATION"

    if ! command_exists docker; then
        print_error "Docker is not installed. Visit: https://docs.docker.com/get-docker/"
        exit 1
    fi

    # Prefer Compose V2 plugin
    if docker compose version >/dev/null 2>&1; then
        COMPOSE_CMD="docker compose"
    elif command_exists docker-compose; then
        COMPOSE_CMD="docker-compose"
    else
        print_error "Docker Compose not found. Visit: https://docs.docker.com/compose/install/"
        exit 1
    fi

    print_success "Docker: $(docker --version)"
    print_success "Docker Compose: $($COMPOSE_CMD version 2>/dev/null | head -1)"

    setup_env
    print_warning "Review .env — ensure database and cache credentials are set for Docker"
    echo ""
    read -rp "Continue with Docker installation? (y/n): " ok
    [[ ! $ok =~ ^[Yy]$ ]] && { print_info "Aborted"; exit 0; }

    print_info "Building and starting containers..."
    if eval "$COMPOSE_CMD up -d --build"; then
        print_success "Containers started successfully"
    else
        print_error "Failed to start containers"
        return 1
    fi

    print_info "Waiting for database to be ready..."
    sleep 5

    print_info "Running Laravel setup inside container..."
    eval "$COMPOSE_CMD exec -T app php artisan migrate:fresh --seed --force" || true
    eval "$COMPOSE_CMD exec -T app php artisan shield:generate --all" || true
    eval "$COMPOSE_CMD exec -T app php artisan optimize:clear" || true

    echo ""
    print_success "Docker installation complete! 🐳"
    echo ""
    print_info "Application: http://localhost:${APP_PORT:-8000}"
    print_info "Mailpit:     http://localhost:${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}"
    echo ""
    print_info "Additional services:"
    echo "  Horizon:  $COMPOSE_CMD --profile horizon up -d"
    echo "  Reverb:   $COMPOSE_CMD --profile reverb up -d"
    echo ""
    print_info "View logs: $COMPOSE_CMD logs -f app"
}

# ── Kubernetes ──────────────────────────────────────────────────────────────────
install_kubernetes() {
    print_header "KUBERNETES INSTALLATION"

    if ! command_exists kubectl; then
        print_error "kubectl not found. Visit: https://kubernetes.io/docs/tasks/tools/"
        exit 1
    fi

    print_success "kubectl $(kubectl version --client --short 2>/dev/null || echo 'found')"

    local k8s_dir="k8s"
    if [ ! -d "$k8s_dir" ]; then
        print_error "k8s/ directory not found"
        exit 1
    fi

    echo ""
    echo "Select Kubernetes overlay:"
    echo "  1) Development  (single replica, debug enabled)"
    echo "  2) Production   (multi-replica, HPA, monitoring)"
    echo "  3) Base only    (minimal base configuration)"
    echo "  4) Cancel"
    echo ""
    read -rp "Choice (1-4): " env_choice

    case $env_choice in
        1) OVERLAY="k8s/overlays/development" ;;
        2) OVERLAY="k8s/overlays/production"  ;;
        3) OVERLAY="k8s/base"                 ;;
        4) print_info "Aborted"; exit 0 ;;
        *) print_error "Invalid choice"; exit 1 ;;
    esac

    print_info "Applying manifests from: $OVERLAY"
    echo ""

    if command_exists kustomize; then
        if kustomize build "$OVERLAY" | kubectl apply -f -; then
            print_success "Kubernetes resources applied successfully"
        else
            print_error "Failed to apply resources"
            exit 1
        fi
    else
        if kubectl apply -k "$OVERLAY"; then
            print_success "Kubernetes resources applied successfully"
        else
            print_error "Failed to apply resources"
            exit 1
        fi
    fi

    echo ""
    print_success "Kubernetes deployment initiated! 🚀"
    echo ""
    print_info "Next steps:"
    echo ""
    echo "  Monitor deployment:  ${CYAN}kubectl get pods -n accounting --watch${RESET}"
    echo "  View logs:           ${CYAN}kubectl logs -n accounting -l app=accounting-laravel${RESET}"
    echo "  Port forward:        ${CYAN}kubectl port-forward -n accounting svc/accounting-app 8000:8000${RESET}"
    echo ""
    print_warning "Remember to:"
    print_warning "  1. Update k8s/base/secret.yaml with real credentials"
    print_warning "  2. Update k8s/base/ingress.yaml with your domain"
    print_warning "  3. Install cert-manager for TLS support"
}

# ── Cleanup ────────────────────────────────────────────────────────────────────
cleanup() {
    print_info "Cleaning up temporary files..."
    rm -f composer-setup.php 2>/dev/null || true
}

trap cleanup EXIT

# ── Help ────────────────────────────────────────────────────────────────────────
show_help() {
    cat << EOF
${BOLD}Liberu Accounting Installer${RESET}
Version: 2.0
A comprehensive setup tool for standalone, Docker, and Kubernetes deployments.

${BOLD}USAGE:${RESET}
  ./setup.sh [OPTIONS]

${BOLD}OPTIONS:${RESET}
  -h, --help              Show this help message
  -d, --debug             Enable debug mode
  -e, --env ENV           Set environment (development|production|staging)
  -s, --skip-tests        Skip test suite
  --no-horizon            Skip Horizon installation
  --no-reverb             Skip Reverb installation

${BOLD}EXAMPLES:${RESET}
  ./setup.sh                              # Interactive setup
  ./setup.sh -e production                # Production environment
  ./setup.sh -d --skip-tests              # Debug mode, skip tests
  SKIP_TESTS=1 ./setup.sh                 # Skip tests via environment variable

${BOLD}ENVIRONMENT VARIABLES:${RESET}
  DEBUG                   Set to 1 to enable debug output
  ENVIRONMENT             Deployment environment (default: production)
  SKIP_TESTS              Set to 1 to skip test suite
  INSTALL_HORIZON         Set to 0 to skip Horizon (default: 1)
  INSTALL_REVERB          Set to 0 to skip Reverb (default: 1)

${BOLD}SUPPORTED DEPLOYMENTS:${RESET}
  1. Standalone  - For development or bare-metal servers
  2. Docker      - Using Docker Compose for local development
  3. Kubernetes  - For production cluster deployments

${BOLD}REQUIREMENTS:${RESET}
  - PHP 8.5+
  - Composer
  - npm (optional, for frontend builds)
  - curl or wget
  - For Docker: docker and docker-compose
  - For Kubernetes: kubectl

EOF
}

# ── Parse Arguments ────────────────────────────────────────────────────────────
parse_args() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)
                show_help
                exit 0
                ;;
            -d|--debug)
                DEBUG=1
                print_debug "Debug mode enabled"
                shift
                ;;
            -e|--env)
                ENVIRONMENT="$2"
                print_debug "Environment set to: $ENVIRONMENT"
                shift 2
                ;;
            -s|--skip-tests)
                SKIP_TESTS=1
                print_debug "Test suite will be skipped"
                shift
                ;;
            --no-horizon)
                INSTALL_HORIZON=0
                print_debug "Horizon installation disabled"
                shift
                ;;
            --no-reverb)
                INSTALL_REVERB=0
                print_debug "Reverb installation disabled"
                shift
                ;;
            *)
                print_warning "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
}

# ── Main Menu ──────────────────────────────────────────────────────────────────
main() {
    parse_args "$@"

    clear
    print_header "LIBERU ACCOUNTING — INSTALLER"
    print_info "Environment: $ENVIRONMENT | Debug: $DEBUG"
    echo ""
    echo "Select installation type:"
    echo ""
    echo "  ${CYAN}1) Standalone${RESET}   - Local development or bare-metal server"
    echo "  ${CYAN}2) Docker${RESET}       - Docker Compose for local development"
    echo "  ${CYAN}3) Kubernetes${RESET}   - Production Kubernetes cluster"
    echo "  ${CYAN}4) Help${RESET}         - Show help message"
    echo "  ${CYAN}5) Exit${RESET}"
    echo ""

    while true; do
        read -rp "Choice (1-5): " choice
        case $choice in
            1) install_standalone; break ;;
            2) install_docker;     break ;;
            3) install_kubernetes; break ;;
            4) show_help; break ;;
            5) print_info "Setup cancelled"; exit 0 ;;
            *) print_warning "Please enter 1, 2, 3, 4, or 5." ;;
        esac
    done
}

main "$@"
