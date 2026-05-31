#!/bin/bash
# Setup script for Liberu Accounting.
# Supports Standalone, Docker, and Kubernetes deployments.

set -euo pipefail

# ── Colors ─────────────────────────────────────────────────────────────────────
RED='\e[91m'
GREEN='\e[92m'
YELLOW='\e[93m'
BLUE='\e[94m'
RESET='\e[0m'

print_header()  { echo ""; echo "=================================="; echo "  $1"; echo "=================================="; echo ""; }
print_error()   { echo -e "${RED}❌  ERROR: $1${RESET}" >&2; }
print_success() { echo -e "${GREEN}✅  $1${RESET}"; }
print_info()    { echo -e "${BLUE}ℹ   $1${RESET}"; }
print_warning() { echo -e "${YELLOW}⚠   $1${RESET}"; }

command_exists() { command -v "$1" >/dev/null 2>&1; }

# ── PHP Version Check ──────────────────────────────────────────────────────────
check_php_version() {
    if ! command_exists php; then
        print_error "PHP is not installed. Please install PHP 8.5+."
        exit 1
    fi

    PHP_VERSION=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
    REQUIRED="8.5"

    if ! php -r "exit(version_compare(PHP_VERSION, '${REQUIRED}', '>=') ? 0 : 1);"; then
        print_error "PHP ${REQUIRED}+ is required. Found PHP ${PHP_VERSION}."
        exit 1
    fi

    print_success "PHP ${PHP_VERSION} detected"
}

# ── Composer ───────────────────────────────────────────────────────────────────
COMPOSER_CMD="composer"

ensure_composer() {
    if command_exists composer; then
        COMPOSER_CMD="composer"
        print_success "Composer found: $(composer --version 2>/dev/null | head -1)"
        return 0
    fi

    if ! command_exists curl; then
        print_error "curl is required to download Composer. Install curl or Composer manually."
        return 1
    fi

    print_info "Downloading Composer..."
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    php -r "unlink('composer-setup.php');"

    if [ -f "composer.phar" ]; then
        COMPOSER_CMD="php composer.phar"
        print_success "composer.phar downloaded"
    else
        print_error "Failed to download Composer."
        return 1
    fi
}

install_composer_dependencies() {
    print_header "COMPOSER INSTALL"

    if [ -d "vendor" ] && [ -f "vendor/autoload.php" ]; then
        read -rp "vendor/ already exists — reinstall? (y/n): " reply
        [[ ! $reply =~ ^[Yy]$ ]] && { print_success "Skipped"; return 0; }
    fi

    ensure_composer

    if eval "$COMPOSER_CMD install --no-interaction --prefer-dist --optimize-autoloader"; then
        print_success "Composer dependencies installed"
    else
        print_error "Composer install failed"
        return 1
    fi
}

# ── Node/NPM ───────────────────────────────────────────────────────────────────
install_npm_dependencies() {
    print_header "NPM INSTALL"

    if ! command_exists npm; then
        print_error "npm is not installed. Visit https://nodejs.org/"
        return 1
    fi

    if [ -d "node_modules" ]; then
        read -rp "node_modules/ already exists — reinstall? (y/n): " reply
        [[ ! $reply =~ ^[Yy]$ ]] && { print_success "Skipped"; return 0; }
    fi

    if npm install; then
        print_success "NPM dependencies installed"
    else
        print_error "npm install failed"
        return 1
    fi
}

build_frontend_assets() {
    print_header "NPM BUILD"

    if ! command_exists npm; then
        print_warning "npm not found — skipping asset build"
        return 0
    fi

    if npm run build; then
        print_success "Frontend assets built"
    else
        print_error "npm run build failed"
        return 1
    fi
}

# ── Laravel Setup ──────────────────────────────────────────────────────────────
setup_env() {
    if [ ! -f ".env" ]; then
        cp .env.example .env
        print_success "Copied .env.example → .env"
    fi

    # Remove duplicate SESSION_DRIVER if present
    if grep -c "^SESSION_DRIVER=" .env 2>/dev/null | grep -q "^2$"; then
        # Keep only the first occurrence
        awk '!seen["SESSION_DRIVER"]++ || !/^SESSION_DRIVER=/' .env > .env.tmp && mv .env.tmp .env
        print_info "Removed duplicate SESSION_DRIVER from .env"
    fi
}

laravel_setup() {
    print_header "LARAVEL SETUP"

    # Generate key if missing
    if ! grep -q "^APP_KEY=base64:" .env 2>/dev/null; then
        php artisan key:generate --force
        print_success "Application key generated"
    else
        print_info "APP_KEY already set — skipping key:generate"
    fi

    # Migrations
    read -rp "Run migrate:fresh --seed? (y/n): " reply
    if [[ $reply =~ ^[Yy]$ ]]; then
        php artisan migrate:fresh --seed --force
        print_success "Database migrated and seeded"
    else
        php artisan migrate --force
        print_success "Migrations run"
    fi

    # Horizon queue table
    if php artisan horizon:install 2>/dev/null; then
        print_success "Horizon installed"
    fi

    # Reverb config publish
    if php artisan reverb:install 2>/dev/null; then
        print_success "Reverb config published"
    fi

    # Shield permissions
    if php artisan shield:generate --all 2>/dev/null; then
        print_success "Filament Shield permissions generated"
    fi

    # Storage link
    php artisan storage:link 2>/dev/null || true

    # Cache
    php artisan optimize:clear
    php artisan event:cache
    php artisan config:cache
    php artisan route:cache

    print_success "Caches refreshed"
}

# ── Test Suite ─────────────────────────────────────────────────────────────────
run_tests() {
    print_header "RUNNING TESTS"

    local test_bin="./vendor/bin/pest"
    [ ! -f "$test_bin" ] && test_bin="./vendor/bin/phpunit"

    if [ -f "$test_bin" ]; then
        if $test_bin; then
            print_success "Tests passed"
        else
            print_warning "Tests failed — review errors before deploying"
        fi
    else
        print_warning "Test runner not found. Run: composer install"
    fi
}

# ── Standalone ─────────────────────────────────────────────────────────────────
install_standalone() {
    print_header "STANDALONE INSTALLATION"
    print_info "PHP $(php -r 'echo PHP_VERSION;') | User: $(whoami)"

    check_php_version
    setup_env

    read -rp "Database credentials configured in .env? (y/n): " ready
    [[ ! $ready =~ ^[Yy]$ ]] && { print_warning "Edit .env then re-run setup.sh"; exit 0; }

    install_composer_dependencies
    install_npm_dependencies || print_warning "npm install failed — continuing"
    build_frontend_assets    || print_warning "npm build failed — continuing"
    laravel_setup

    read -rp "Run test suite? (y/n): " run_t
    [[ $run_t =~ ^[Yy]$ ]] && run_tests

    echo ""
    print_success "Installation complete!"
    echo ""

    read -rp "Start Octane server? (y/n): " start
    if [[ $start =~ ^[Yy]$ ]]; then
        php artisan octane:start
    else
        print_info "Start later with: php artisan octane:start"
        print_info "Or dev server:   php artisan serve"
        print_info "Queue worker:    php artisan horizon"
        print_info "WebSockets:      php artisan reverb:start"
    fi
}

# ── Docker ─────────────────────────────────────────────────────────────────────
install_docker() {
    print_header "DOCKER INSTALLATION"

    if ! command_exists docker; then
        print_error "Docker is not installed. Visit https://docs.docker.com/get-docker/"
        exit 1
    fi

    # Prefer Compose V2 plugin
    if docker compose version >/dev/null 2>&1; then
        COMPOSE_CMD="docker compose"
    elif command_exists docker-compose; then
        COMPOSE_CMD="docker-compose"
    else
        print_error "Docker Compose not found. Visit https://docs.docker.com/compose/install/"
        exit 1
    fi

    print_success "Docker Compose: $(eval "$COMPOSE_CMD" version 2>/dev/null | head -1)"

    setup_env
    print_warning "Review .env — ensure DB/Redis credentials are set for Docker"
    read -rp "Continue? (y/n): " ok
    [[ ! $ok =~ ^[Yy]$ ]] && exit 0

    print_info "Building and starting containers..."
    eval "$COMPOSE_CMD up -d --build"

    print_success "Containers started"

    print_info "Running migrations inside container..."
    eval "$COMPOSE_CMD exec app php artisan migrate --force" || true
    eval "$COMPOSE_CMD exec app php artisan db:seed --force" || true
    eval "$COMPOSE_CMD exec app php artisan shield:generate --all" || true
    eval "$COMPOSE_CMD exec app php artisan optimize:clear" || true

    echo ""
    print_success "Docker installation complete!"
    print_info "Application: http://localhost:\${APP_PORT:-8000}"
    print_info "Mailpit:     http://localhost:\${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}"
    echo ""
    print_info "Start Horizon:  $COMPOSE_CMD --profile horizon up -d"
    print_info "Start Reverb:   $COMPOSE_CMD --profile reverb up -d"
}

# ── Kubernetes ──────────────────────────────────────────────────────────────────
install_kubernetes() {
    print_header "KUBERNETES INSTALLATION"

    if ! command_exists kubectl; then
        print_error "kubectl not found. Visit https://kubernetes.io/docs/tasks/tools/"
        exit 1
    fi

    print_success "kubectl $(kubectl version --client --short 2>/dev/null || echo 'found')"

    local k8s_dir="k8s"
    [ ! -d "$k8s_dir" ] && { print_error "k8s/ directory not found. Run setup.sh first."; exit 1; }

    echo ""
    echo "Select environment:"
    echo "  1) Development  (k8s/overlays/development)"
    echo "  2) Production   (k8s/overlays/production)"
    echo "  3) Base only    (k8s/base)"
    read -rp "Choice (1-3): " env_choice

    case $env_choice in
        1) OVERLAY="k8s/overlays/development" ;;
        2) OVERLAY="k8s/overlays/production"  ;;
        3) OVERLAY="k8s/base"                 ;;
        *) print_error "Invalid choice"; exit 1 ;;
    esac

    print_info "Applying manifests from: $OVERLAY"

    if command_exists kustomize; then
        kustomize build "$OVERLAY" | kubectl apply -f -
    else
        kubectl apply -k "$OVERLAY"
    fi

    print_success "Kubernetes resources applied"
    echo ""
    print_info "Check status: kubectl get pods -n accounting"
    print_info "View logs:    kubectl logs -n accounting -l app=accounting-laravel"
    echo ""
    print_warning "Remember to:"
    print_warning "  1. Update k8s/base/secret.yaml with real credentials"
    print_warning "  2. Update k8s/base/ingress.yaml with your domain"
    print_warning "  3. Configure cert-manager for TLS"
}

# ── Main Menu ──────────────────────────────────────────────────────────────────
main() {
    clear
    print_header "LIBERU ACCOUNTING — INSTALLER"
    echo "  Select installation type:"
    echo ""
    echo "  1) Standalone   (local dev or bare-metal)"
    echo "  2) Docker       (docker compose)"
    echo "  3) Kubernetes   (k8s cluster)"
    echo "  4) Exit"
    echo ""

    while true; do
        read -rp "Choice (1-4): " choice
        case $choice in
            1) install_standalone; break ;;
            2) install_docker;     break ;;
            3) install_kubernetes; break ;;
            4) print_info "Cancelled"; exit 0 ;;
            *) print_warning "Enter 1, 2, 3, or 4." ;;
        esac
    done
}

main
