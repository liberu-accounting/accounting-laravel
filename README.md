# Liberu Accounting

![Open Source Love](https://img.shields.io/badge/Open%20Source-%E2%9D%A4-red.svg)

![](https://img.shields.io/badge/PHP-8.3-informational?style=flat&logo=php&color=4f5b93)
![](https://img.shields.io/badge/Laravel-11-informational?style=flat&logo=laravel&color=ef3b2d)
![](https://img.shields.io/badge/Filament-3.2-informational?style=flat&logo=data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0OCIgaGVpZ2h0PSI0OCIgeG1sbnM6dj0iaHR0cHM6Ly92ZWN0YS5pby9uYW5vIj48cGF0aCBkPSJNMCAwaDQ4djQ4SDBWMHoiIGZpbGw9IiNmNGIyNWUiLz48cGF0aCBkPSJNMjggN2wtMSA2LTMuNDM3LjgxM0wyMCAxNWwtMSAzaDZ2NWgtN2wtMyAxOEg4Yy41MTUtNS44NTMgMS40NTQtMTEuMzMgMy0xN0g4di01bDUtMSAuMjUtMy4yNUMxNCAxMSAxNCAxMSAxNS40MzggOC41NjMgMTkuNDI5IDYuMTI4IDIzLjQ0MiA2LjY4NyAyOCA3eiIgZmlsbD0iIzI4MjQxZSIvPjxwYXRoIGQ9Ik0zMCAxOGg0YzIuMjMzIDUuMzM0IDIuMjMzIDUuMzM0IDEuMTI1IDguNUwzNCAyOWMtLjE2OCAzLjIwOS0uMTY4IDMuMjA5IDAgNmwtMiAxIDEgM2gtNXYyaC0yYy44NzUtNy42MjUuODc1LTcuNjI1IDItMTFoMnYtMmgtMnYtMmwyLTF2LTQtM3oiIGZpbGw9IiMyYTIwMTIiLz48cGF0aCBkPSJNMzUuNTYzIDYuODEzQzM4IDcgMzggNyAzOSA4Yy4xODggMi40MzguMTg4IDIuNDM4IDAgNWwtMiAyYy0yLjYyNS0uMzc1LTIuNjI1LS4zNzUtNS0xLS42MjUtMi4zNzUtLjYyNS0yLjM3NS0xLTUgMi0yIDItMiA0LjU2My0yLjE4N3oiIGZpbGw9IiM0MDM5MzEiLz48cGF0aCBkPSJNMzAgMThoNGMyLjA1NSA1LjMxOSAyLjA1NSA1LjMxOSAxLjgxMyA4LjMxM0wzNSAyOGwtMyAxdi0ybC00IDF2LTJsMi0xdi00LTN6IiBmaWxsPSIjMzEyODFlIi8+PHBhdGggZD0iTTI5IDI3aDN2MmgydjJoLTJ2MmwtNC0xdi0yaDJsLTEtM3oiIGZpbGw9IiMxNTEzMTAiLz48cGF0aCBkPSJNMzAgMThoNHYzaC0ydjJsLTMgMSAxLTZ6IiBmaWxsPSIjNjA0YjMyIi8+PC9zdmc+&&color=fdae4b&link=https://filamentphp.com)
![](https://img.shields.io/badge/Livewire-3.5-informational?style=flat&logo=Livewire&color=fb70a9)

[![Install](https://github.com/liberu-accounting/accounting-laravel/actions/workflows/install.yml/badge.svg)](https://github.com/liberu-accounting/accounting-laravel/actions/workflows/install.yml)
[![Tests](https://github.com/liberu-accounting/accounting-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/liberu-accounting/accounting-laravel/actions/workflows/tests.yml)
[![Docker](https://github.com/liberu-accounting/accounting-laravel/actions/workflows/main.yml/badge.svg)](https://github.com/liberu-accounting/accounting-laravel/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/liberu-accounting/accounting-laravel/graph/badge.svg?token=K7TWB1QF1L)](https://codecov.io/gh/liberu-accounting/accounting-laravel)

---

A modular, open-source accounting platform built with Laravel 11 and PHP 8.3. Designed for extensibility, real-time reports, and a clean developer experience using Livewire and Filament.

Highlights
- Modular architecture: ledger, invoices, payroll, inventory, fixed assets, and more.
- Modern stack: Laravel 11, PHP 8.3, Livewire 3, Filament 3.
- Suitable for businesses and developers who need a customizable accounting core.

## Quick start
### Prerequisites
- PHP 8.3, Composer, Node.js (for assets)
- A database (MySQL/Postgres)

### Install (recommended for local development)
1. Copy or create your environment file:

```powershell
copy .env.example .env
```

2. Install dependencies, generate key, and run migrations/seeds:

```powershell
composer install; php artisan key:generate; php artisan migrate --seed
```

If you prefer the repository helper script, run:

```powershell
./setup.sh
```

Note: the setup script may overwrite `.env` and will run seeders.

## Docker
Build and run the provided Docker image:

```powershell
docker build -t accounting-laravel .; docker run -p 8000:8000 accounting-laravel
```

## Laravel Sail
Start the Sail environment (if using Sail):

```powershell
./vendor/bin/sail up
```

## Related projects
| Project | Description |
|---|---|
| liberu-accounting/accounting-laravel | This repository — core accounting platform |
| liberu-automation/automation-laravel | Automation utilities and jobs |
| liberu-billing/billing-laravel | Billing and payments module |
| liberusoftware/boilerplate | Application boilerplate and templates |
| liberu-cms/cms-laravel | CMS for content and pages |
| liberu-ecommerce/ecommerce-laravel | E-commerce starter pack |
| liberu-crm/crm-laravel | Customer relationship management |

For the full list and links, see the GitHub organization: https://github.com/liberu-accounting

## Contributing
We welcome contributions. Typical workflow:
- Fork the repo, create a feature branch, run tests, and open a pull request.
- Keep changes small and focused. Add tests for new behavior where possible.

## License
This project is licensed under the MIT License — see the LICENSE file for details.

## Maintainers & contributors
See the contributors graph on GitHub for an up-to-date list of contributors.

---
