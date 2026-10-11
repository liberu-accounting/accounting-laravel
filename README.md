# Liberu Accounting ERP

> Production-ready accounting and ERP application for ledgers, banking, tax, expenses, approvals, and reporting.

[Software](https://liberusoftware.com) · [Hosting](https://liberuhosting.com) · [Services](https://liberuservices.com) · [Liberu Group](https://liberugroup.com)

[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/) [![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/) [![Filament](https://img.shields.io/badge/Filament-5-FDAE4B)](https://filamentphp.com/) [![Livewire](https://img.shields.io/badge/Livewire-4-FB70A9)](https://livewire.laravel.com/)

[![Install](https://github.com/liberusoftware/accounting-erp-laravel/actions/workflows/install.yml/badge.svg?branch=main)](https://github.com/liberusoftware/accounting-erp-laravel/actions/workflows/install.yml) [![Tests](https://github.com/liberusoftware/accounting-erp-laravel/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/liberusoftware/accounting-erp-laravel/actions/workflows/tests.yml) [![Docker](https://github.com/liberusoftware/accounting-erp-laravel/actions/workflows/docker.yml/badge.svg?branch=main)](https://github.com/liberusoftware/accounting-erp-laravel/actions/workflows/docker.yml) [![Codecov](https://codecov.io/gh/liberusoftware/accounting-erp-laravel/branch/main/graph/badge.svg)](https://codecov.io/gh/liberusoftware/accounting-erp-laravel) [![Latest release](https://img.shields.io/github/v/release/liberusoftware/accounting-erp-laravel?sort=semver)](https://github.com/liberusoftware/accounting-erp-laravel/releases/latest) [![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE.md)

Liberu Accounting ERP is the deployable accounting application built on the Liberu Laravel foundation. It combines ledger, invoicing, procurement, banking, tax, payroll, inventory, reconciliation, approvals, and reporting capabilities with Filament and Livewire surfaces.

## Key features

- Double-entry general ledger, chart of accounts, journal entries, posting, period close, and reversals
- Invoices, bills, estimates, payments, sales orders, receipts, credits, and approval workflows
- Banking connections, reconciliation, exchange rates, multi-currency transactions, and financial statements
- Tax, payroll, HMRC integrations, fixed assets, inventory valuation, subscriptions, and recurring documents
- Filament admin and accounting panels with Livewire pages, tenancy, roles, permissions, audit trails, and settings
- API v1 endpoints, OpenAPI documentation, a typed PHP SDK, notifications, queues, backups, and observability
- Independently versioned modules installed into tracked `/modules` directories
- Independently versioned themes installed into tracked `/themes` directories with inheritance and safe fallback
- Architecture tests for manifests, dependency direction, package ownership, and presentation boundaries

## Requirements

| Dependency | Supported version |
|---|---|
| PHP | 8.5 |
| Laravel | 13.x |
| Filament | 5.x |
| Livewire | 4.x |
| Composer | 2.x |
| Node.js | Latest stable release |
| Database | A Laravel-supported SQL database |

## Quick start

```bash
git clone https://github.com/liberusoftware/accounting-erp-laravel.git
cd accounting-erp-laravel
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
php artisan migrate
php artisan serve
```

Review `.env` before migrating. Use `php artisan migrate --seed` only when example data is wanted. The optional interactive `install.sh` supports local, Docker, and Kubernetes-oriented setup.

## Composable package architecture

Each runtime capability is an independent `liberu-module` Composer package with its own GitHub repository, release lifecycle, manifest, provider, documentation, and tests. Each visual package is an independent `liberu-theme`. Shared contract packages and the custom installer are normal Composer dependencies under `/vendor`.

```text
Application composition
├── modules/       # Composer-installed module releases, tracked in Git
├── themes/        # Composer-installed theme releases, tracked in Git
├── app/           # Host-only composition and integration
├── config/        # Enabled modules and application policy
└── tests/         # Cross-package and application tests
```

Composer is the source of installation and version truth:

```bash
# Update all dependencies, including modules and themes
composer update --with-all-dependencies

# Update one capability from its tagged GitHub repository
composer update liberusoftware/search --with-dependencies
```

The trusted [`liberusoftware/composer-installer`](https://github.com/liberusoftware/composer-installer) places packages according to type:

| Composer type | Install path | Repository convention |
|---|---|---|
| `liberu-module` | `/modules/{installer-name}` | `liberusoftware/module-{installer-name}` |
| `liberu-theme` | `/themes/{installer-name}` | `liberusoftware/theme-{installer-name}` |
| Contract/library | `/vendor` | Package-specific repository |

`modules/` and `themes/` are intentionally kept out of `.gitignore`. Their reproduced contents are committed so deployments and reviews can see the exact installed code, while `composer.lock` pins each release and source commit. Do not edit an installed module only in this host: contribute the generic change to its package repository, release it, and update the Composer dependency here.

Installation, runtime enablement, authorisation, and commercial entitlement are separate concerns. `config/modules.php` selects the enabled capability graph; the module manager validates dependencies and orders providers without scanning application classes manually.

Every module also publishes a validated feature catalog in `module.json`. Hosts can inspect the complete catalog or search it without loading module internals:

```bash
php artisan module:features
php artisan module:features health
php artisan module:status search
```

## Module and theme development

A module owns one cohesive capability and communicates through public contracts, actions, events, registries, or stable identifiers. Domain modules do not depend on Filament or themes; optional `*-filament`, `*-api`, and `*-livewire` packages provide presentation adapters.

Every module contains:

```text
composer.json
module.json
README.md
LICENSE.md
CHANGELOG.md
src/
database/ or resources/ when required
tests/
```

Themes contain `composer.json`, `theme.json`, source assets, compatibility metadata, accessibility/fallback expectations, tests, documentation, and asset licensing information. See the [module development guide](docs/MODULE_DEVELOPMENT.md) and [theme architecture](docs/THEME_ARCHITECTURE.md).

## Testing, coverage, and quality

```bash
composer validate --strict
vendor/bin/pest
vendor/bin/pint --test
npm run build
```

The test suite exercises application behaviour, API boundaries, Filament pages, Livewire flows, and every installed module provider. Package architecture tests verify metadata, declared dependencies, host isolation, UI boundaries, and Composer ownership.

Coverage is generated as Clover XML and enforced in CI for the host application:

```bash
php artisan test --coverage --coverage-clover=coverage.xml --min=100
```

The complete release gate is available as `scripts/verify-release` (or
`composer verify`). It fails when coverage, static analysis, dependency audits,
asset builds, Docker Compose, or Kubernetes validation cannot be completed.

The live coverage report is published to [Codecov](https://codecov.io/gh/liberusoftware/accounting-erp-laravel). Package coverage is measured by each package's own test workflow.

### Publishing the component repositories

The package fleet derives repository names from directory names, using
`module-` for entries in `modules/` and `theme-` for entries in `themes/`.
The host application remains `accounting-erp-laravel` and is not submitted as
a Composer package.

```bash
# Inspect the independently released repositories
scripts/fleet status

# Clone any missing component repositories into the fleet workspace
scripts/fleet clone

# Run a package command across the fleet
scripts/fleet run 'vendor/bin/pest'
```

Fleet operations require authenticated `git` access to the organisation. Package
repositories are the source of truth; the host application is deliberately not
mirrored into them.

After the repositories are public, register every Composer package on Packagist:

```bash
# Verify all package-to-repository mappings without submitting
php scripts/submit-packagist.php --dry-run

# Obtain the MAIN API token from packagist.org/profile, then refresh the packages
export PACKAGIST_USERNAME='your-packagist-username'
export PACKAGIST_API_TOKEN='your-packagist-api-token'
php scripts/submit-packagist.php
unset PACKAGIST_API_TOKEN
```

The submitter skips packages that are already registered and reports individual
API failures without printing the configured token.

## Documentation

- [Accounting architecture](docs/ACCOUNTING_ARCHITECTURE.md)
- [Module development](docs/MODULE_DEVELOPMENT.md)
- [Foundation compliance](docs/FOUNDATION_COMPLIANCE.md)
- [Foundation module matrix](docs/FOUNDATION_MODULE_MATRIX.md)
- [Theme architecture](docs/THEME_ARCHITECTURE.md)
- [Theme system](docs/THEME_SYSTEM.md)
- [Messaging architecture](docs/MESSAGING_ARCHITECTURE.md)
- [Search architecture](docs/SEARCH_ARCHITECTURE.md)
- [Localisation](docs/MULTI_LANGUAGE.md)
- [Notifications](docs/NOTIFICATIONS.md)

## Related Liberu projects

| Project | Repository | Scope |
|---|---|---|
| Accounting | [liberusoftware/accounting-erp-laravel](https://github.com/liberusoftware/accounting-erp-laravel) | Ledgers, banking, tax, expenses, close, and reporting |
| Automation | [liberusoftware/automation-laravel](https://github.com/liberusoftware/automation-laravel) | Governed workflows, provider-neutral AI, approvals, and connectors |
| Billing | [liberusoftware/billing-laravel](https://github.com/liberusoftware/billing-laravel) | Billing, subscriptions, payments, invoices, and revenue operations |
| Boilerplate | [liberusoftware/boilerplate-laravel](https://github.com/liberusoftware/boilerplate-laravel) | Modular Laravel foundation used by this application |
| Browser game | [liberusoftware/browser-game-laravel](https://github.com/liberusoftware/browser-game-laravel) | Browser-based game platform and domain capabilities |
| CMS | [liberusoftware/cms-laravel](https://github.com/liberusoftware/cms-laravel) | Content, publishing, pages, media, search, and delivery |
| Control panel | [liberusoftware/control-panel-laravel](https://github.com/liberusoftware/control-panel-laravel) | Hosting, infrastructure, DNS, mail, backups, and operations |
| CRM | [liberusoftware/crm-laravel](https://github.com/liberusoftware/crm-laravel) | Customers, leads, opportunities, sales, and service |
| Ecommerce | [liberusoftware/ecommerce-laravel](https://github.com/liberusoftware/ecommerce-laravel) | Catalogues, checkout, orders, fulfilment, and returns |
| Genealogy | [liberusoftware/genealogy-laravel](https://github.com/liberusoftware/genealogy-laravel) | Genealogy records, relationships, sources, and research |
| Maintenance | [liberusoftware/maintenance-laravel](https://github.com/liberusoftware/maintenance-laravel) | Maintenance planning, assets, work orders, and operations |
| Real estate | [liberusoftware/real-estate-laravel](https://github.com/liberusoftware/real-estate-laravel) | Property, listing, tenancy, and transaction workflows |
| Social network | [liberusoftware/social-network-laravel](https://github.com/liberusoftware/social-network-laravel) | Social profiles, groups, content, messaging, and discovery |

## Security

Do not report security vulnerabilities through public GitHub issues. Email `security@liberusoftware.com` with reproduction details and the affected version so the report can be handled privately.

## License

This project is open-source software available under the [MIT License](LICENSE.md). The linked licence text is authoritative; this summary is not legal advice.

## Feedback and contributing

Feedback and contributions are welcome. Report reproducible bugs, propose focused enhancements, improve documentation or translations, and submit tested changes. Search existing issues first. Pull requests should explain the problem and approach, remain focused, pass the required checks, and document user-visible or breaking changes. Security reports must follow the private route above.

## Contributors

Thank you to everyone who helps improve Liberu. [View the contributors graph](https://github.com/liberusoftware/accounting-erp-laravel/graphs/contributors).
