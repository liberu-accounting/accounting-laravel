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

### R3 · Invoice line items `#7`
- [ ] `InvoiceItem` model (mirror `BillItem`: qty, unit_price, amount)
- [ ] Migration `invoice_items`
- [ ] `Invoice` hasMany `InvoiceItem`
- [ ] Filament repeater on `InvoiceResource`
- [ ] Total rolls up: invoice total = Σ(qty × unit_price)
- [ ] Posts balanced journal entry
- [ ] Test: N line items, total + balanced JE

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

### R6 · Reconciliation workflow UI `#11`
- [ ] Reconcile action on `BankStatementResource`
- [ ] Match / unmatch UI
- [ ] Statement status: open / reconciled
- [ ] Difference display
- [ ] Test: import → auto-match → manual match → reconcile at diff=0

### R7 · Core REST endpoints `#15`
- [ ] `/api` invoices (CRUD, Sanctum)
- [ ] bills
- [ ] estimates
- [ ] journal entries
- [ ] chart of accounts
- [ ] general ledger
- [ ] Apply existing rate-limit pattern
- [ ] Tests per endpoint

---

## P3 — Coverage

### R8 · Test gaps `#12` `#17`
- [ ] Inventory valuation/COGS tests
- [ ] Asset depreciation schedule tests
- [ ] HMRC PAYE/CT tests
- [ ] Coverage for all R1–R7 deliverables
- [ ] No core feature at 0 coverage

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
