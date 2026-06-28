# PRD — Liberu Accounting: Closing the Feature Gaps

**Status:** Draft · **Source:** audit of README `Key Features` vs codebase · **Date:** 2026-06-28

## 1. Purpose

The README advertises features that are missing, partial, or false in the code. This PRD scopes the work to make the product match its claims, in priority order. Each item lists the gap, the deliverable, and acceptance criteria.

## 2. Audit summary

| # | Feature claim | Status | Gap |
|---|---------------|--------|-----|
| 1 | Double-entry engine (balanced journal entries) | ✅ Implemented | — |
| 2 | Post & reverse, all 4 entry types | ✅ Implemented | — |
| 3 | Account hierarchy + opening balances | ✅ Implemented | — |
| 4 | Chart of Accounts, 5 account types | ✅ Implemented | — |
| 5 | Bulk import/export of accounts | ❌ Missing | No import/export action — CRUD only |
| 6 | Ledger — filterable/paginated history | ✅ Implemented | — |
| 7 | Invoices — customer & supplier, line-item detail | ⚠️ Partial | No generic invoice line-item model |
| 8 | Payroll — pay-run + payslip generation | ⚠️ Partial | No payslip model / PDF / download |
| 9 | Inventory — stock + COGS | ⚠️ Partial | No Filament UI, no tests, missing cost-layer model |
| 10 | Fixed Assets — both depreciation methods | ✅ Implemented | — |
| 11 | Bank Reconciliation — match + workflow | ⚠️ Partial | Matching service only, no reconcile UI |
| 12 | HMRC / MTD VAT | ⚠️ Partial | VAT path works, PAYE/CT thin, light tests |
| 13 | QuickBooks Online — two-way sync, OAuth 2.0 | ❌ Missing | No service, no OAuth, no routes — claim false |
| 14 | Plaid bank feed | ✅ Implemented | — |
| 15 | REST API + Sanctum | ⚠️ Partial | Bank endpoints only; no core-accounting endpoints |
| 16 | Modular architecture | ✅ Implemented | Framework only, 1 example module |
| 17 | Comprehensive test suite | ⚠️ Partial | Zero inventory/QBO tests, minimal asset tests |

**Score: 7 implemented, 8 partial, 2 missing.**

---

## 3. Requirements

### P0 — README claim is false

#### R1 · QuickBooks Online two-way sync (`#13`)
README + `docs/QUICKBOOKS_ONLINE_FUNCTIONALITY.md` claim two-way OAuth 2.0 sync. Reality: no `QuickBooksService`, no OAuth flow, no `/api/qbo/*` routes, zero tests. Docs describe local *feature parity* (bills, estimates, credit memos), not sync.

**Decision required first:** build sync, or correct the docs.
- **Build:** OAuth 2.0 connect flow · `QuickBooksService` (push + pull) · entity mapping (accounts, invoices, bills, payments) · webhook/poll sync · token refresh · tests.
- **Doc fix:** drop "two-way sync" from README + QBO doc; reframe as local feature parity.

**Acceptance:** either a connected QBO account round-trips an invoice both directions with tests, OR README/docs no longer claim sync.

---

### P1 — Core accounting gaps

#### R2 · Account import/export (`#5`)
Add Filament header/bulk actions on `ChartOfAccountsResource`: CSV import (validate type, parent, normal-balance) + CSV/Excel export. Use `league/csv` or `maatwebsite/excel`.
**Acceptance:** round-trip — export accounts, re-import to empty tenant, hierarchy + types preserved.

#### R3 · Invoice line items (`#7`)
`Invoice` only links `timeEntries` (hourly). Bills already have `BillItem` (qty × unit_price). Mirror it: `InvoiceItem` model + migration + Filament repeater on `InvoiceResource`; totals roll up to invoice.
**Acceptance:** create invoice with N line items; invoice total = Σ(qty × unit_price); posts balanced journal entry.

#### R4 · Payslip generation (`#8`)
`Payroll` has pay-run fields, no payslip. Add `Payslip` model (or PDF service): per-employee gross/deductions/net breakdown · `dompdf` render · download action on `PayrollResource`.
**Acceptance:** run a pay-run, download a per-employee payslip PDF showing gross, deductions, net.

---

### P2 — Finish partial modules

#### R5 · Inventory UI + tests (`#9`)
Services exist (`InventoryService`, `InventoryValuationService` FIFO/LIFO/avg). Missing: Filament `InventoryItemResource`, stock-movement views, COGS-posting verification, the referenced-but-absent `InventoryCostLayer` model, and tests (currently 0).
**Acceptance:** create item, record stock in/out via UI, COGS journal entry posts, valuation tests pass for FIFO/LIFO/avg.

#### R6 · Reconciliation workflow UI (`#11`)
`ReconciliationService` matches transactions but no user-facing flow. Add reconcile action, match/unmatch UI, statement status (open/reconciled), difference display on `BankStatementResource`.
**Acceptance:** import statement, auto-match, manually match remainder, mark reconciled when difference = 0.

#### R7 · Core REST endpoints (`#15`)
API covers only bank/payment integrations (31 routes). Add Sanctum-auth resource endpoints: invoices, bills, estimates, journal entries, chart of accounts, general ledger. Mirror existing rate-limit pattern.
**Acceptance:** authenticated CRUD over each resource; rate limits enforced; tests per endpoint.

---

### P3 — Coverage

#### R8 · Test gaps (`#12`, `#17`)
195 methods, but inventory 0, QBO 0, fixed assets <1, HMRC 3. Add feature tests: inventory valuation/COGS, asset depreciation schedules, HMRC PAYE/CT, plus coverage for all work above.
**Acceptance:** every P0–P2 deliverable ships with tests; no core feature at 0 coverage.

---

## 4. Notes
- "Modular architecture" is real but only `BlogModule` ships; Inventory/Fixed Assets/Reconciliation are not modularized — README implies more breadth than exists.
- Evidence (file:line) for every grade lives in the audit; ask to inline it.
- Implemented features (no work): double-entry engine, post/reverse, account hierarchy, CoA, ledger, fixed-asset depreciation, Plaid (49 tests), module framework.

---

# PRD — Phase 2: Platform & Maturity Backlog

**Status:** Draft · **Added:** 2026-06-28 · **Context:** Phase 1 (R1–R8) closed every README "Key Features" gap. Phase 2 scopes the larger platform investments deliberately left out of scope in Phase 1. Each item is graded by what **already exists** (recon-backed, file:line) vs greenfield, so we build on foundations rather than from scratch.

## Summary

| # | Area | Today | Effort |
|---|------|-------|--------|
| R9 | Modularize existing features | Foundation exists (framework + blueprint), only `BlogModule` ships | M |
| R10 | Multi-currency completion | Partial (models exist; FX + reporting missing; 2 bugs) | L |
| R11 | Xero / Sage integration | Greenfield (mirror QBO pattern) | L |
| R12 | Payroll tax engine (PAYE/NI) | Partial (RTI submission exists; tax is a hardcoded 20%) | L |
| R13 | API versioning + OpenAPI docs | Partial (flat routes, binary Sanctum, stale `api.md`) | M |
| R14 | UI/UX branding + theme | None (default Filament Gray) | S |

---

## R9 · Modularize existing features

**Today.** The module framework is real: `app/Modules/BaseModule.php` (lifecycle hooks), `app/Modules/ModuleManager.php`, `ModuleServiceProvider` (auto-discovery), `config/modules.php`, and a full blueprint in `docs/MODULAR_ARCHITECTURE.md`. Only `app/Modules/BlogModule/` ships. Inventory, Fixed Assets, and Reconciliation are plain app code under `app/Models` + `app/Filament/App/Resources` — not modules.

**Deliverable.** Convert Inventory, Fixed Assets, and Reconciliation into first-class modules under `app/Modules/*` (module class extending `BaseModule`, owning their models/migrations/Filament resources/services), discoverable + toggleable via `config/modules.php`.

**Acceptance.** Disabling a module in config removes its panel resources and routes without errors; enabling restores them; existing tests still pass. One module ships fully migrated as the reference (recommend Inventory — already has the cleanest service boundary).

**Risks.** Migration/namespace moves are invasive; do one module per PR, keep DB table names stable.

## R10 · Multi-currency completion

**Today.** Data model exists: `Currency` (`currency_id`, `code`, `is_default`), `ExchangeRate` model, `Account::getBalanceInCurrency(Currency)`, `Transaction.currency_id` + `getAmountInCurrency()`. `ExchangeRateService::getExchangeRate()` fetches/falls back.

**Bugs to fix first (quick wins).**
- `ExchangeRate` model has **no `exchange_rates` table migration** — create it.
- `routes/api.php` `/exchange-rates` calls `ExchangeRateService::getLatestRates()`, which **does not exist** → the endpoint 500s. Implement it or fix the route.

**Deliverable.** True multi-currency: per-transaction currency captured at entry, a configurable **reporting currency**, FX **gain/loss** posting on revaluation (a GL entry when rates move), and currency-aware financial statements.

**Acceptance.** A foreign-currency invoice posts and, on settlement at a different rate, generates a balanced FX gain/loss journal entry; trial balance renders in the reporting currency; tests cover conversion + gain/loss.

## R11 · Xero / Sage integration

**Today.** None — `grep xero|sage` is empty. The QBO integration is the template: `QuickBooksService` (OAuth 2.0, token refresh, push/pull per entity) + `QboConnection` (encrypted tokens) + webhook controller + `/api/qbo/*` routes.

**Deliverable.** A `XeroService` (and later `SageService`) mirroring the QBO shape: OAuth connect/callback, encrypted `*Connection` model, push/pull for accounts, invoices, bills, payments, plus webhook handling.

**Acceptance.** Connect a Xero sandbox org, round-trip an invoice both directions, tests with `Http::fake` per entity — same bar as the QBO suite.

**Note.** Extract the shared sync shape (client/refresh/upsert-by-remote-id) into a reusable base or contract so each provider is thin.

## R12 · Payroll tax engine (PAYE / NI)

**Today.** `HmrcRtiPayeService` builds + submits RTI/FPS XML with `GrossPay`, `TaxDeducted`, `EmployeeNICs`, `EmployerNICs`, `StudentLoan` fields — but it is **submission-only** and trusts pre-calculated `Payroll.employee_data`. Nothing calculates those: `Payroll::calculateNetSalary()` is a hardcoded `gross * 0.20`. `config/hmrc.php` has Corporation Tax bands but **no PAYE bands or NI thresholds**.

**Deliverable.** A `PayrollTaxService` computing real UK statutory figures from gross + tax code: PAYE income tax by band (personal allowance, basic/higher/additional), employee + employer NI by threshold, and student-loan repayment by plan — driven by configurable tax-year tables in `config/`. Wire its output into `Payroll` and into the FPS `employee_data`.

**Acceptance.** Given a known salary + tax code + NI category, the computed PAYE / employee NI / employer NI / student loan match HMRC worked examples (to the penny) in tests; `calculateNetSalary` uses the engine, not the 20% placeholder.

## R13 · API versioning + OpenAPI docs

**Today.** `routes/api.php` is a flat namespace (no `/v1/`). Auth is binary `auth:sanctum` (no abilities/scopes). `docs/api.md` is hand-written and **documents only 2 of ~15 endpoint groups** (Transactions, Exchange Rates) — stale and incomplete.

**Deliverable.** Introduce a `/v1` prefix (keep current paths aliased for back-compat), add Sanctum token **abilities/scopes** (e.g. `invoices:read`, `payroll:write`) enforced per route, and generate an **OpenAPI** spec (annotations or a generator) served at a docs route. Replace the stale `api.md`.

**Acceptance.** All resource endpoints live under `/v1`; a token minted with limited abilities is rejected on out-of-scope routes (test); OpenAPI spec validates and lists every endpoint with request/response schema.

## R14 · UI/UX branding + theme

**Today.** Both panels (`AdminPanelProvider`, `AppPanelProvider`) use default Filament with `Color::Gray` and the stock `theme.css`; no `brandName`, logo, or custom palette. `tailwind.config.js` is the default preset.

**Deliverable.** Brand both panels: `->brandName('Liberu Accounting')` + logo, a custom primary color palette, dark-mode polish, and a small set of branded assets. Keep Filament defaults for layout (no bespoke component rewrites).

**Acceptance.** Both panels show the brand name + logo and the custom palette; light/dark both legible; no accessibility regressions (contrast).

---

## Out of scope (Phase 2)

- Bespoke front-end outside Filament (custom SPA, marketing site).
- Non-UK payroll jurisdictions (R12 targets UK PAYE/NI first).
- Real-time collaborative editing, mobile apps.
- Migrating off Filament/Livewire.

---

# PRD — Phase 3: Follow-ups

**Status:** Draft · **Added:** 2026-06-28 · **Context:** Phases 1–2 (R1–R14) are shipped. Phase 3 collects the deliberate, non-blocking follow-ups each of those PRs deferred. All are extensions of working, tested code — no new foundations.

## Summary

| # | Follow-up | From | Today | Effort |
|---|-----------|------|-------|--------|
| F1 | Fixed Assets module | R9 | Inventory module shipped as reference; Assets still plain app code | S |
| F2 | Reconciliation module | R9 | same pattern, not yet applied | S |
| F3 | Xero accounts/bills/payments mapping | R11 | Xero does invoices only; QBO does all four | M |
| F4 | Sage integration | R11 | greenfield; mirror Xero/QBO | L |
| F5 | Per-transaction currency UI/API | R10b | `Transaction` model supports it; not exposed | M |
| F6 | Full API scopes + generated OpenAPI | R13 | invoices/bills fully scoped; spec hand-maintained | M |
| F7 | Pay-period payroll apportionment | R12 | engine is annual-basis; no period/cumulative codes | M |

---

## F1 · Fixed Assets module `S`
**Today.** R9 (#806) shipped Inventory as the reference module (gate-in-place: `InventoryModule::isActive()` + `canAccess()`/`shouldRegisterNavigation()` on the resource; code/tables unchanged). Fixed Assets is still plain app code.
**Deliverable.** `app/Modules/FixedAssets` (module class + `module.json`); gate `AssetResource` (+ `AssetAcquisitionResource`) on the module state — same pattern as Inventory.
**Acceptance.** Disabling the module removes the asset panel resources without errors; enabling restores them; existing asset tests pass.
**Deps:** R9 pattern (done). **Boundary:** gate-in-place; no model/migration relocation; keep table names.

## F2 · Reconciliation module `S`
**Today.** Reconciliation is integrated into `ReconciliationService` + the `BankStatement` resource; not a module.
**Deliverable.** `app/Modules/Reconciliation` module gating the reconcile actions / statement resource.
**Acceptance.** Disable → reconcile UI gone; enable → restored; reconciliation tests pass.
**Deps:** R9 pattern. **Boundary:** as F1.

## F3 · Xero accounts / bills / payments mapping `M`
**Today.** `XeroService` (R11, #805) round-trips invoices only. QBO maps invoices, accounts, bills, payments.
**Deliverable.** `pushAccount`/`pullAccounts`, `pushBill`/`pullBills`, `pushPayment`/`pullPayments` on `XeroService` (Xero `Accounts`/`Bills` are `ACCPAY` invoices/`Payments`), mirroring the QBO methods; add `xero_id` where needed.
**Acceptance.** Each entity round-trips both directions with `Http::fake` tests (QBO bar).
**Deps:** R11. **Boundary:** Xero only.

## F4 · Sage integration `L`
**Today.** None. QBO + Xero exist as the template.
**Deliverable.** `SageService` + `SageConnection` (OAuth 2.0, encrypted tokens), invoice round-trip first, then accounts/bills/payments.
**Acceptance.** Connect a Sage sandbox, round-trip an invoice both directions, faked tests.
**Deps:** R11 (template). **Boundary:** consider extracting a shared sync contract now that there are 3 providers (QBO/Xero/Sage).

## F5 · Per-transaction currency UI/API `M`
**Today.** `Transaction` has `currency_id` + `exchange_rate` + `getAmountInCurrency()`, and the boot hook auto-sets the rate. R10b added FX gain/loss + reporting currency. But the currency isn't exposed at entry.
**Deliverable.** Currency selector on the transaction Filament form; `currency_id` (+ optional `exchange_rate`) accepted on the transaction API; surface FX gain/loss posting (`FxRevaluationService`) at settlement.
**Acceptance.** Create a foreign-currency transaction via UI + API; on settlement at a changed rate, the FX gain/loss entry posts; tests cover both surfaces.
**Deps:** R10b (done). **Boundary:** uses existing rate source.

## F6 · Full API scopes + generated OpenAPI `M`
**Today.** R13 (#804) added `/v1`, Sanctum ability scopes (invoices + bills fully gated), and a hand-maintained OpenAPI doc at `/api/v1/openapi.json`.
**Deliverable.** Apply the `<resource>:read`/`:write` ability convention to **all** v1 resources (estimates, journal-entries, chart-of-accounts, general-ledger); replace the hand-written spec with an annotation/attribute-driven generator so it can't drift.
**Acceptance.** A limited-ability token is rejected on every out-of-scope route; the generated spec lists every endpoint with request/response schemas and validates.
**Deps:** R13. **Boundary:** still no breaking removal of unversioned aliases; no SDK generation.

## F7 · Pay-period payroll apportionment `M`
**Today.** `PayrollTaxService` (R12, #802) computes correct **annual** UK PAYE/NI/student-loan and `calculateNetSalary` uses it. There is no pay-period (monthly/weekly) apportionment or cumulative tax-code operation.
**Deliverable.** Period-aware calculation (annualise the period gross, compute, de-annualise) with cumulative vs non-cumulative (`W1/M1`) code handling; a tax-year selector so multiple years' tables coexist in `config/payroll.php`.
**Acceptance.** A monthly run for a known salary + code matches HMRC monthly worked examples; switching tax year picks the right tables; tests per scenario.
**Deps:** R12. **Boundary:** UK only; no pensions/auto-enrolment.

---

## Out of scope (Phase 3)
- Same global exclusions as Phase 2 (non-UK payroll, live-FX trading, non-Filament front-ends, migrating off Filament/Livewire, module-framework rewrite, API SDK generation, providers beyond QBO/Xero/Sage).
