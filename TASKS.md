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
- [ ] **DECISION**: build sync vs correct docs — resolve before any R1 task
- **If build:**
  - [ ] OAuth 2.0 connect flow (authorize, callback, store tokens)
  - [ ] Token refresh + expiry handling
  - [ ] `QuickBooksService` — push (app → QBO)
  - [ ] `QuickBooksService` — pull (QBO → app)
  - [ ] Entity mapping: accounts, invoices, bills, payments
  - [ ] Sync trigger: webhook or poll
  - [ ] `/api/qbo/*` routes (Sanctum-auth)
  - [ ] Tests: round-trip invoice both directions
- **If doc fix:**
  - [ ] Drop "two-way sync" from `README.md`
  - [ ] Reframe `docs/QUICKBOOKS_ONLINE_FUNCTIONALITY.md` as local feature parity
- **Done when:** connected QBO round-trips an invoice w/ tests, OR docs no longer claim sync.

---

## P1 — Core accounting gaps

### R2 · Account import/export `#5`
- [ ] Add export action to `ChartOfAccountsResource` (CSV/Excel)
- [ ] Add import action (header/bulk)
- [ ] Validate on import: account type, parent ref, normal-balance
- [ ] Pick lib: `league/csv` or `maatwebsite/excel`
- [ ] Test: export → re-import to empty tenant, hierarchy + types preserved

### R3 · Invoice line items `#7` — done (branch `feat/r3-invoice-line-items`)
- [x] `InvoiceItem` model (mirror `BillItem`: qty, unit_price, amount, auto-calc + recalc hooks)
- [x] Migration `invoice_items` (+ guarded `invoice_number` column the model expects)
- [x] `Invoice` hasMany `InvoiceItem` + `calculateTotals()`
- [x] Filament repeater on `InvoiceResource`
- [x] Total rolls up: invoice total = Σ(qty × unit_price)
- [x] Posts balanced journal entry (`InvoicePostingService`: debit AR, credit revenue per line)
- [x] Test: line items, total roll-up, balanced JE (`InvoiceLineItemsTest`, 3 tests)
- Fixed pre-existing `str_pad(int)` bug in `Invoice` boot (shared hook, all callers)

### R4 · Payslip generation `#8`
- [ ] `Payslip` model (or PDF service) — gross/deductions/net per employee
- [ ] `dompdf` render
- [ ] Download action on `PayrollResource`
- [ ] Test: run pay-run → download PDF w/ gross, deductions, net

---

## P2 — Finish partial modules

### R5 · Inventory UI + tests `#9`
- [ ] `InventoryCostLayer` model (referenced, absent) + migration
- [ ] Filament `InventoryItemResource`
- [ ] Stock-movement views (in/out)
- [ ] Verify COGS journal posting
- [ ] Tests: FIFO / LIFO / avg valuation + COGS

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
