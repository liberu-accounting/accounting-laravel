# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Liberu Accounting** is an open-source, double-entry accounting platform built on Laravel 13 / PHP 8.5 / Filament 5 / Livewire 4. It provides a full accounting engine (journal entries, chart of accounts, general ledger, invoicing, payroll, fixed assets, bank reconciliation) with integrations to QuickBooks Online, Plaid, Revolut, Wise, and HMRC Making Tax Digital.

## Common Commands

### Setup
```bash
./setup.sh                          # Interactive installer (standalone, Docker, or K8s)
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm install && npm run build
```

### Development
```bash
php artisan serve                   # Standard Laravel dev server
php artisan octane:start            # Octane/RoadRunner server (production-equivalent)
npm run dev                         # Vite HMR dev server
```

### Testing
```bash
./vendor/bin/pest                   # Run all tests
./vendor/bin/pest tests/Unit        # Run unit tests only
./vendor/bin/pest tests/Feature     # Run feature tests only
./vendor/bin/pest --filter=DoubleEntry  # Run a specific test
php artisan test --coverage         # With coverage report
```

Tests run against SQLite `:memory:` — no real database needed (configured in `phpunit.xml`).

### Code Quality
```bash
./vendor/bin/pint                   # Laravel Pint auto-formatter
./vendor/bin/rector process         # Rector AST refactoring (PHP 8.5 rules)
php artisan insights --min-quality=90 --min-complexity=90
```

### Docker
```bash
docker compose up -d --build
docker compose exec app php artisan migrate:fresh --seed
docker compose --profile horizon up -d   # Start Horizon queue worker
docker compose --profile reverb up -d    # Start Reverb WebSocket server
```

### Queue / WebSockets (local)
```bash
php artisan horizon                 # Horizon queue dashboard + workers
php artisan reverb:start            # Reverb WebSocket server (port 8080)
```

### Cache & Optimisation
```bash
php artisan optimize:clear          # Clear all caches (config, route, view)
php artisan filament:upgrade        # Re-publish Filament assets after upgrade
php artisan shield:generate --all   # Regenerate Filament Shield permissions
```

## Architecture

### Two Filament Panels

**Admin panel** (`/admin`) — `app/Providers/Filament/AdminPanelProvider.php`  
Manages users, roles, permissions (via Filament Shield), site settings, and menus.

**App panel** (`/app`) — `app/Providers/Filament/AppPanelProvider.php`  
All accounting operations: 30+ resources across `app/Filament/App/Resources/`. This is where end-users work.

### Double-Entry Engine

The core invariant: every posted `JournalEntry` must have equal debits and credits.

- `JournalEntry` / `JournalEntryLine` — `app/Models/`
- Posted entries **cannot be edited**; they must be reversed first (reversing entry type)
- Account types (Asset, Liability, Equity, Revenue, Expense) determine the "normal balance" direction
- Parent accounts in the hierarchy cannot hold manual entries — only leaf accounts do

### Multi-Tenancy via Teams

The `IsTenantModel` trait (used on most models) automatically scopes Eloquent queries to the current team. Filament panels are also scoped by team. `TeamServiceProvider` bootstraps this. The `User` model carefully merges `HasRoles` (Spatie) and `HasTeams` (Jetstream) to avoid method conflicts.

### Service Layer

Business logic lives in `app/Services/` (22 services). Key ones:
- `GeneralLedgerService`, `FinancialStatementService` — reporting
- `PlaidService`, `BankFeedService`, `BankStatementImportService` — open banking
- `HmrcMtdVatService`, `HmrcRtiPayeService` — tax compliance
- `InventoryService`, `InventoryValuationService` (FIFO/LIFO/average)
- `ReconciliationService`, `BudgetService`, `ExchangeRateService`

### Module System

`app/Modules/` contains optional feature modules (auto-discovered via `ModuleServiceProvider`). Configured in `config/modules.php`. `BaseModule` provides lifecycle hooks. Currently ships with `BlogModule` as an example.

### API Layer

Routes in `routes/api.php`. All endpoints authenticated via **Laravel Sanctum** tokens except webhooks (`/webhooks/plaid`, `/webhooks/revolut`, `/webhooks/wise` — verified with HMAC signatures). Rate limiting: 60 req/min default, 10–30 for sensitive endpoints.

### Real-Time (Horizon + Reverb)

**Horizon** (`laravel/horizon`) manages Redis-backed queues with a dashboard at `/horizon`. Access is gated to `super_admin` role (defined in `AppServiceProvider`).

**Reverb** (`laravel/reverb`) is the WebSocket server. Config in `config/reverb.php`; env vars use `REVERB_*` prefix. The Docker Compose `reverb` service runs it as a separate container (profile: `reverb`). Port 8080.

### Authentication

- Web auth: Laravel Fortify + Jetstream (team management)
- Social auth: Socialstream — `bursteri/socialstream ^7.0` (same `JoelButcher\Socialstream` namespace, drop-in for the abandoned `joelbutcher/socialstream`). All 9 providers configured in `config/socialstream.php` — twitter-oauth-1 deliberately excluded (OAuth 1.0 needs live credentials even for redirect).
- API auth: Sanctum token-based
- RBAC: Filament Shield (roles/permissions scoped per panel)

### Security

`SecurityHeaders` middleware is registered globally in `bootstrap/app.php` and injects `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`, and `Strict-Transport-Security` on every response. In production it also adds a CSP header with a per-request nonce.

## Key Configuration Files

| File | Purpose |
|------|---------|
| `config/modules.php` | Module auto-discovery and caching |
| `config/horizon.php` | Horizon queue worker environments and supervisor settings |
| `config/reverb.php` | Reverb WebSocket server and app credentials |
| `config/socialstream.php` | OAuth provider list (9 providers, no twitter-oauth-1) |
| `config/hmrc.php` | HMRC/MTD API endpoints and credentials |
| `config/filament-shield.php` | RBAC super-admin guard and panel config |
| `config/sanctum.php` | API token expiry and stateful domains |
| `.docker/supervisord.conf` | Process manager: Octane + Horizon + Reverb + Scheduler + Workers |
| `.docker/octane/` | RoadRunner binary and config |

## Docker / Deployment

The `Dockerfile` (PHP 8.5 Alpine, multi-stage) uses **Laravel Octane + RoadRunner** instead of php-fpm. Supervisor manages per-mode processes. The `CONTAINER_MODE` env var controls startup:
- `http` → Octane server (port 8000)
- `horizon` → Horizon queue workers
- `reverb` → Reverb WebSocket server (port 8080)
- `worker` → generic queue worker
- `scheduler` → cron runner

`docker-compose.yml` defines `horizon`, `reverb`, and `worker` as separate services under optional profiles. Laravel Sail can also be used for local dev.

## Kubernetes

Kustomize-based manifests in `k8s/` following `liberu-control-panel` label conventions (`app: accounting-laravel`, `component: application|database|cache|reverb|horizon`). Structure:
- `k8s/base/` — namespace, configmap, secret, deployment, reverb, horizon, mysql StatefulSet, redis, ingress, PVC, network-policy
- `k8s/overlays/development/` — single replica, debug mode
- `k8s/overlays/production/` — HPA (3–10 replicas, 70% CPU / 80% memory thresholds)

Apply with `kubectl apply -k k8s/overlays/production` (requires cert-manager for TLS).

## Non-Obvious Conventions

- `declare(strict_types=1)` is used throughout — keep it on all new PHP files.
- PHP 8.1+ `#[\Override]` attribute is used on interface implementations — add it when overriding.
- `rector.php` targets PHP 8.5 rules; run Rector after any major refactor.
- Filament Shield permissions are regenerated with `php artisan shield:generate --all` after adding new resources.
- The `App` Filament panel still has additional resources beyond the listed ones — check `app/Filament/App/Resources/` directly.
- Webhook routes bypass Sanctum auth deliberately; do not add auth middleware to them.
