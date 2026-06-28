# Task List — Liberu Accounting Feature Gaps

Derived from [`PRD.md`](PRD.md). Checkbox tasks grouped by requirement, priority-ordered. Out-of-scope at the bottom.

---

## Development Workflow — Laravel TDD Cycle

All feature and bugfix work follows test-driven development. Every task in this
file is "done" only when its gate test exists and passes.

**Cycle:** RED → Verify RED → GREEN → Verify GREEN → REFACTOR → Repeat.

1. **RED — write failing test.** One minimal test showing what the Laravel feature should do.
2. **Verify RED — watch it fail.** `php artisan test --filter=test_name`. Confirm it fails for the right reason.
3. **GREEN — minimal Laravel code.** Simplest code that passes the test. No more.
4. **Verify GREEN — re-run the filter.** Confirm the test now passes.
5. **REFACTOR — clean up, only after green:**
   - Extract **services** for complex logic (`app/Services/*Service.php`).
   - Create **policies** for authorization.
   - Add **query scopes** for reusable query logic.
   - Use **events** for side effects.
6. **Repeat** for the next behavior.

Test env: sqlite in-memory (`phpunit.xml`) — no DB setup. Most tests are feature tests;
use factories + custom states. Run the minimal filter while iterating; run the full suite
before finalizing.

### Branching & commit rules

- **One branch per functionality.** Each requirement (R1–R8) gets its own feature
  branch off `master` — e.g. `feat/r2-account-import-export`, `feat/r3-invoice-line-items`.
  Do not mix requirements in one branch.
- **Pint before every commit.** Run `./vendor/bin/pint` (or `docker compose run --rm composer pint`)
  on changed files; commit only when Pint reports clean.
- **Full suite green before PR.** `php artisan test` passes before opening the PR.

---

## P0 — README claim is false

### R1 · QuickBooks Online two-way sync `#13`
**DECISION: build sync** (branch `feat/r1-quickbooks-sync`). Done — invoice round-trips both directions, 4 tests green.
- [x] OAuth 2.0 connect flow (authorize, callback, store tokens)
- [x] Token refresh + expiry handling
- [x] `QuickBooksService` — push (app → QBO) `pushInvoice`
- [x] `QuickBooksService` — pull (QBO → app) `pullInvoices`
- [x] Entity mapping: invoices (incl. CustomerRef → local Customer)
- [ ] Entity mapping: accounts, bills, payments — deferred (see ponytail note in service; follow-up)
- [x] Sync trigger: webhook handler `QboWebhookController` (HMAC-verified) + manual sync route
- [x] `/api/qbo/*` routes (Sanctum-auth) + public HMAC webhook
- [x] Tests: round-trip invoice both directions (`QuickBooksSyncTest`, 4 tests)
- **Done:** connected QBO round-trips an invoice with passing tests. README claim now backed.

---

## P1 — Core accounting gaps

### R2 · Account import/export `#5` — done (branch `feat/r2-account-import-export`)
- [x] Add export action to `ChartOfAccounts` list page (CSV download)
- [x] Add import action (header action, CSV upload)
- [x] Validate on import: account type, parent ref, normal-balance
- [x] Lib: native `fputcsv`/`fgetcsv` — no new dependency (ponytail)
- [x] Test: export → re-import to empty tenant, hierarchy + types preserved (`AccountCsvServiceTest`, 5 tests)

### R3 · Invoice line items `#7` — done (branch `feat/r3-invoice-line-items`)
- [x] `InvoiceItem` model (mirror `BillItem`: qty, unit_price, amount, auto-calc + recalc hooks)
- [x] Migration `invoice_items` (+ guarded `invoice_number` column the model expects)
- [x] `Invoice` hasMany `InvoiceItem` + `calculateTotals()`
- [x] Filament repeater on `InvoiceResource`
- [x] Total rolls up: invoice total = Σ(qty × unit_price)
- [x] Posts balanced journal entry (`InvoicePostingService`: debit AR, credit revenue per line)
- [x] Test: line items, total roll-up, balanced JE (`InvoiceLineItemsTest`, 3 tests)
- Fixed pre-existing `str_pad(int)` bug in `Invoice` boot (shared hook, all callers)

### R4 · Payslip generation `#8` — done (branch `feat/r4-payslip-generation`)
- [x] `PayslipService` (no separate model needed) — gross/deductions/net per employee
- [x] `dompdf` render — installed `barryvdh/laravel-dompdf` (also unbreaks `Invoice::generatePDF`)
- [x] Download action on `PayrollResource`
- [x] Payroll `grossSalary()` + `totalDeductions()` helpers; `payslips.template` blade view
- [x] Test: gross/deduction helpers, payslip HTML content, PDF bytes (`PayslipServiceTest`, 3 tests)

---

## P2 — Finish partial modules

### R5 · Inventory UI + tests `#9` — done (branch `feat/r5-inventory-ui`)
- [x] `InventoryCostLayer` model (table already existed; model was absent)
- [x] Filament `InventoryItemResource` (App panel) + List/Create/Edit pages
- [x] `InventoryItemFactory`
- [x] COGS journal posting (`InventoryPostingService::postCogs` — debit COGS expense, credit inventory asset)
- [x] Tests: FIFO / LIFO / average valuation + balanced COGS JE (`InventoryValuationTest`, 4 tests)
- Fixed `InventoryItem` PK override (`inventory_item_id` → table uses `id`; broke relations); added missing `cost_of_goods_sold` column the service writes
- Stock-movement UI deferred (transaction/adjustment screens) — valuation engine + COGS verified, item CRUD shipped

### R6 · Reconciliation workflow UI `#11` — done (branch `feat/r6-reconciliation-ui`)
- [x] Reconcile action on `BankStatementResource` (existed; was **broken** — called `->count()` on an int; now routes through the service)
- [x] Match / unmatch UI (discrepancies modal — fixed to the int contract, unmatched list driven from discrepancies)
- [x] Statement status: open / reconciled (`reconciled` flag, set by `reconcileStatement`)
- [x] Difference display (balance discrepancy in modal + notification)
- [x] `ReconciliationService::reconcileStatement()` — single source of truth for the reconciled rule (no unmatched + zero discrepancy)
- [x] Test: balanced → reconciled, discrepancy → stays open (`ReconciliationWorkflowTest`, 2 tests)

### R7 · Core REST endpoints `#15` — done (branches `feat/r7-rest-endpoints` + `feat/r7b-sales-purchase-api`)
- [x] chart of accounts (`/api/chart-of-accounts`, full CRUD, Sanctum, user-scoped)
- [x] journal entries (`/api/journal-entries`, index/store/show/destroy; store rejects unbalanced lines)
- [x] general ledger (`/api/general-ledger/trial-balance` + `/balances`, read-only)
- [x] invoices (`/api/invoices`, full CRUD, team-scoped)
- [x] bills (`/api/bills`, full CRUD, team-scoped)
- [x] estimates (`/api/estimates`, full CRUD, team-scoped)
- [x] Apply existing rate-limit pattern (`throttle:60,1`)
- [x] Tests per endpoint (CoA 5, journal 5, GL 2, invoice 3, bill 3, estimate 3)
- [x] Fixed `GeneralLedgerService` null-currency crash + wrong `account_id` column
- [x] Fixed `str_pad(int)` boot bug in `Bill`/`Estimate` number generators; added `team_id` to fillable; new `Vendor`/`Bill`/`Estimate` factories

---

## P3 — Coverage

### R8 · Test gaps `#12` `#17` — done (branch `feat/r8-coverage`)
- [x] Inventory valuation/COGS tests (delivered in R5 — `InventoryValuationTest`)
- [x] Asset depreciation schedule tests (`AssetDepreciationTest`, 3 — straight-line + reducing-balance + schedule)
- [x] HMRC PAYE tests (`HmrcRtiPayeTest`, 2) + Corporation Tax tests (`HmrcCorporationTaxTest`, 2)
- [x] Coverage for R1–R7 deliverables (each shipped with its own tests)
- [x] No core feature at 0 coverage
- Surfaced + fixed real bugs while testing:
  - `assets` table migration mismatched the model (Asset was uncreatable) — schema corrected (PK `asset_id`, `asset_name`/`asset_cost`/`useful_life_years`) + dependent FKs
  - `HmrcSubmission`/`HmrcCorporationTaxSubmission`/`HmrcPayeSubmission` `company()` used a bare `belongsTo` (Company PK is `company_id`) → relation always null → fixed
  - `HmrcRtiPayeService` passed int `tax_month` to `SimpleXMLElement::addChild` (TypeError) → cast to string

---

## Out of scope

Explicitly **not** in this effort:

- **Already implemented** — no work: double-entry engine, post/reverse + 4 entry types, account hierarchy + opening balances, Chart of Accounts, general ledger, fixed-asset depreciation (both methods), Plaid (49 tests), module framework.
- **Modularizing existing features** — converting Inventory / Fixed Assets / Reconciliation into pluggable `app/Modules/*` units. Module framework exists; only `BlogModule` ships. Refactor deferred.
- **New integrations beyond QBO** — Xero, Sage, Stripe, etc. Not claimed in README, not in scope.
- **Multi-currency expansion** — beyond existing `ExchangeRateService`. Not a README claim.
- **Payroll tax engine** — statutory PAYE/NI auto-calculation beyond pay-run + payslip display. R4 covers display, not tax computation.
- **HMRC beyond MTD VAT** — full RTI PAYE / Corporation Tax filing. R8 adds tests only; new filing flows out of scope.
- **API versioning / public API docs / SDK** — R7 adds endpoints; versioning + OpenAPI publishing deferred.
- **UI/UX redesign** — Filament defaults retained; no theming work.

---

# Phase 2 — Platform & Maturity (R9–R14)

Derived from [`PRD.md`](PRD.md) Phase 2. Prioritized by value × cost × risk. Same workflow as Phase 1: one branch per requirement off `master`, TDD, Pint before commit, full suite green before PR.

**Build order (top = first):** P1 quick wins → P2 correctness/core → P3 platform → P4 invasive refactor.

## Dependency graph

```
R7 (REST endpoints, done) ──► R13 (API versioning)
QBO sync (done) ──► R11 (Xero/Sage)  [extract shared sync base first]
#798 (exchange_rates fix, done) ──► R10 (multi-currency completion)
HMRC RTI submission (done) ──► R12 (payroll tax engine feeds employee_data)
R9 (modularize) — no hard deps, but do LAST (invasive; churns every module's files)
```

Nothing in R9–R14 hard-blocks another; the only sequencing is "do R9 last" and "extract a sync base before R11".

---

## P1 — Quick wins (small, high value, no deps)

### R14 · UI/UX branding + theme `S`
- [ ] `->brandName('Liberu Accounting')` + logo on both panel providers (`AdminPanelProvider`, `AppPanelProvider`)
- [ ] Custom primary color palette (replace `Color::Gray`)
- [ ] Branded assets + dark-mode contrast pass
- [ ] **Acceptance:** both panels show brand name/logo + custom palette; light/dark legible; no contrast regressions
- **Deps:** none. **Boundary:** Filament layout kept; no bespoke component rewrites, no external SPA.

### R10a · Multi-currency bug fixes `S` (remaining after #798)
- [ ] Fix `ExchangeRateService::updateExchangeRates` — uses `$currency->id` but Currency PK is `currency_id` (→ null `from/to_currency_id`)
- [ ] Add a `CurrencyFactory` / `ExchangeRateFactory` for tests
- [ ] **Acceptance:** `updateExchangeRates` persists rows with correct currency ids; test with `Http::fake`
- **Deps:** #798 (table + `getLatestRates`) — merged. **Boundary:** wiring/correctness only; FX posting is R10b.

---

## P2 — Correctness / core

### R12 · Payroll tax engine (PAYE / NI) `L`
- [ ] `PayrollTaxService` — PAYE income tax by band (personal allowance, basic/higher/additional) from gross + tax code
- [ ] Employee NI + employer NI by threshold/category
- [ ] Student-loan repayment by plan
- [ ] Tax-year tables in `config/` (bands, thresholds, allowances) — replace the hardcoded `0.20`
- [ ] Wire output into `Payroll::calculateNetSalary` and the FPS `employee_data`
- [ ] **Acceptance:** computed PAYE / EE-NI / ER-NI / student loan match HMRC worked examples to the penny in tests
- **Deps:** HMRC RTI submission (done) consumes it. **Boundary:** UK PAYE/NI only — no other jurisdictions; no pensions/auto-enrolment.

### R10b · Multi-currency completion `L`
- [ ] Capture per-transaction currency at entry (UI + API)
- [ ] Configurable **reporting currency** (config + setting)
- [ ] FX **gain/loss** GL posting on revaluation/settlement at a changed rate
- [ ] Currency-aware financial statements (trial balance / GL in reporting currency)
- [ ] **Acceptance:** a foreign-currency invoice settled at a different rate posts a balanced FX gain/loss JE; trial balance renders in reporting currency; tests cover conversion + gain/loss
- **Deps:** R10a (correct rate data). **Boundary:** uses existing `ExchangeRateService` source; no live-FX trading, no hedging.

---

## P3 — Platform

### R13 · API versioning + OpenAPI docs `M`
- [ ] Introduce `/v1` prefix (alias current paths for back-compat)
- [ ] Sanctum token **abilities/scopes** (e.g. `invoices:read`, `payroll:write`) enforced per route
- [ ] Generate **OpenAPI** spec (annotations or generator) served at a docs route
- [ ] Replace the stale `docs/api.md` (documents 2 of ~15 groups)
- [ ] **Acceptance:** all resource endpoints under `/v1`; a limited-ability token is rejected on out-of-scope routes (test); OpenAPI validates + lists every endpoint with schema
- **Deps:** R7 endpoints (done). **Boundary:** no SDK generation, no breaking removal of current unversioned paths in this pass.

### R11 · Xero / Sage integration `L`
- [ ] Extract a shared sync base/contract from `QuickBooksService` (client/refresh/upsert-by-remote-id)
- [ ] `XeroService` + `XeroConnection` (OAuth 2.0, encrypted tokens) — mirror QBO
- [ ] Push/pull accounts, invoices, bills, payments + webhook handling
- [ ] `SageService` after Xero (same base)
- [ ] **Acceptance:** connect a Xero sandbox org, round-trip an invoice both directions, `Http::fake` tests per entity (QBO bar)
- **Deps:** QBO sync (done) as template; the shared base is a soft prerequisite. **Boundary:** Xero first, Sage second; no other providers.

---

## P4 — Invasive refactor (do last)

### R9 · Modularize existing features `M`
- [ ] Convert **Inventory** to `app/Modules/Inventory` (reference module — cleanest service boundary)
- [ ] Convert **Fixed Assets** to a module
- [ ] Convert **Reconciliation** to a module
- [ ] Each: module class extends `BaseModule`, owns models/migrations/Filament resources/services, toggElable in `config/modules.php`
- [ ] **Acceptance:** disabling a module removes its panel resources + routes without errors; enabling restores them; existing tests pass
- **Deps:** none hard — but do AFTER R10–R14 so it doesn't churn files those PRs touch. **Boundary:** one module per PR; keep DB table names stable; framework already exists (no rewrite of `ModuleManager`).

---

## Phase 2 — Out of scope

- Bespoke front-end outside Filament (custom SPA, marketing site, mobile apps).
- Non-UK payroll jurisdictions (R12 is UK PAYE/NI only); pensions / auto-enrolment.
- Live-FX trading / hedging (R10 uses the existing rate source only).
- API SDK generation; removing the current unversioned API paths.
- Integrations beyond Xero then Sage (no Stripe/NetSuite/etc.).
- Migrating off Filament / Livewire; real-time collaborative editing.
- Rewriting the module framework (`ModuleManager`/`BaseModule` already exist — R9 only adds modules).
