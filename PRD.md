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
