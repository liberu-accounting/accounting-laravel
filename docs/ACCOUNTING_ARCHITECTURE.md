# Accounting ERP Domain Architecture

## Overview

The accounting domain manages financial transactions, ledgers, invoicing, banking, tax, payroll, fixed assets, and inventory. It provides double-entry bookkeeping, multi-currency support, and comprehensive financial reporting.

## Chart of Accounts

The chart of accounts defines the organizational structure for all financial transactions.

### Account Hierarchy

```
Assets (1000-1999)
├── Current Assets (1000-1099)
│   ├── Cash (1000-1019)
│   ├── Accounts Receivable (1020-1039)
│   └── Inventory (1040-1059)
└── Fixed Assets (1100-1199)

Liabilities (2000-2999)
├── Current Liabilities (2000-2099)
└── Long-term Liabilities (2100-2199)

Equity (3000-3999)
Revenue (4000-4999)
Expenses (5000-5999)
```

### Account Types

| Type | Normal Balance | Description |
|------|---------------|-------------|
| Asset | Debit | Resources owned |
| Liability | Credit | Obligations owed |
| Equity | Credit | Owner's stake |
| Revenue | Credit | Income earned |
| Expense | Debit | Costs incurred |

### Numbering and Mapping

- Configurable numbering scheme per organization
- Account mapping rules for automated categorization
- Multi-company consolidation mapping

## Journal Entries

All financial transactions are recorded as double-entry journal entries.

### Structure

```php
class JournalEntry {
    string $entry_id;
    Carbon $date;
    string $reference;
    string $description;
    array $lines; // JournalLine[]
}

class JournalLine {
    string $account_id;
    float $debit;
    float $credit;
    string $cost_center;
    string $project_id;
}
```

### Posting Rules

- Debits must equal credits (balanced entry)
- Immutable once posted (corrections via reversal)
- Period locking prevents posting to closed periods
- Audit trail with user and timestamp

### Reversals

- Full reversal creates offsetting entry
- Partial reversal with specified amount
- Reversal reason and authorization required

## Invoicing

### Document Types

| Type | Description |
|------|-------------|
| Estimate | Quotation for potential work |
| Invoice | Bill for goods/services delivered |
| Receipt | Payment confirmation |
| Credit Note | Refund or discount adjustment |
| Recurring | Template for repeated billing |

### Invoice Lifecycle

```
draft → sent → viewed → partial → paid → overdue
                         ↓
                      credited
```

### Approvals

- Draft invoices require approval before sending
- Threshold-based approval routing
- Bulk approval for batch operations

## Banking

### Connections

- Open Banking API integration (UK/EU)
- Direct bank feed (CSV, OFX, QIF)
- Manual transaction entry

### Reconciliation

- Automatic matching by amount, date, reference
- Rule-based matching for recurring transactions
- Manual matching interface with suggestions
- Reconciliation report with discrepancies

### Exchange Rates

- Daily rate import from ECB or custom provider
- Historical rate lookup for backdated transactions
- Multi-currency ledger support with base currency reporting

## Tax Engine

### Tax Configuration

| Component | Description |
|-----------|-------------|
| `rate` | Percentage or fixed amount |
| `jurisdiction` | Geographic applicability |
| `exemption` | Conditions for tax exclusion |
| `filing` | Return submission requirements |

### Jurisdictions

- UK VAT (standard 20%, reduced 5%, zero 0%)
- EU VAT with MOSS (Mini One Stop Stop)
- US Sales Tax (state and local)
- Custom tax regimes

### Filings

- VAT return generation (MTD compatible)
- Tax liability calculation per period
- Filing deadline tracking and reminders
- Digital submission via HMRC API

## Payroll

### Employee Management

| Field | Description |
|-------|-------------|
| `employee_id` | Unique identifier |
| `tax_code` | HMRC tax code |
| `ni_number` | National Insurance number |
| `pay_frequency` | `weekly`, `monthly` |
| `salary` | Annual gross salary |

### Payroll Run

```
employee_setup → gross_calculation → deductions → net_pay → payslip → submission
```

### Deductions

- Income Tax (PAYE via tax code)
- National Insurance (employee and employer)
- Student Loan repayments
- Pension contributions (auto-enrolment)
- Court orders and attachments

### HMRC Submission

- RTI (Real Time Information) submissions
- FPS (Full Payment Submission) per pay period
- EPS (Employer Payment Summary) for adjustments
- Year-end P60 generation

## Fixed Assets

### Register

| Field | Description |
|-------|-------------|
| `asset_id` | Unique identifier |
| `description` | Asset description |
| `category` | Asset category |
| `acquisition_cost` | Purchase price |
| `acquisition_date` | Date acquired |
| `useful_life_years` | Depreciation period |
| `salvage_value` | Residual value |

### Depreciation Methods

| Method | Description |
|--------|-------------|
| Straight-line | Equal amount per period |
| Reducing balance | Fixed percentage of book value |
| Sum-of-years-digits | Accelerated depreciation |
| Units of production | Based on usage |

### Disposal and Revaluation

- Disposal with gain/loss calculation
- Partial disposal (component approach)
- Revaluation with surplus/deficit to equity
- Impairment testing and write-downs

## Inventory

### Valuation Methods

| Method | Description |
|--------|-------------|
| FIFO | First in, first out |
| LIFO | Last in, first out |
| Weighted average | Moving average cost |
| Specific identification | Track individual items |

### Costing

- Standard cost with variance analysis
- Actual cost with lot/serial tracking
- Landed cost allocation (freight, duties)
- Overhead absorption

### Adjustments

- Stock take with variance posting
- Write-off for damaged/obsolete stock
- Inter-location transfers
- Assembly/disassembly (bill of materials)

## Financial Reporting

### Core Reports

| Report | Description |
|--------|-------------|
| Balance Sheet | Assets, liabilities, equity at point in time |
| Profit & Loss | Revenue, expenses, profit for period |
| Cash Flow | Cash inflows and outflows by category |
| Trial Balance | All accounts with debit/credit balances |

### Report Features

- Comparative periods (prior year, YTD)
- Drill-down from summary to transaction
- Export to PDF, Excel, CSV
- Scheduled report generation and email

## Approval Workflows

### Workflow Configuration

```php
class ApprovalWorkflow {
    string $document_type; // invoice, expense, etc.
    array $steps; // ApprovalStep[]
    float $threshold_amount;
}

class ApprovalStep {
    int $order;
    string $approver_role;
    string $approver_id; // or role-based
    bool $is_parallel;
}
```

### Features

- Multi-step sequential or parallel approval
- Threshold-based routing (amount, category)
- Delegation with expiry dates
- Escalation on timeout
- Audit trail of all approval actions

## Module Boundaries and Extension Points

| Module | Extension Point |
|--------|---------------|
| `ChartOfAccounts` | Custom account types, numbering schemes |
| `Journal` | Custom posting rules, validation logic |
| `Invoicing` | Custom document types, approval chains |
| `Banking` | Custom bank connectors, matching rules |
| `Tax` | Custom tax regimes, filing integrations |
| `Payroll` | Custom deduction types, HMRC submissions |
| `FixedAssets` | Custom depreciation methods, disposal rules |
| `Inventory` | Custom costing methods, adjustment types |
| `Reporting` | Custom report templates, export formats |
| `Approvals` | Custom workflow definitions, escalation rules |

- Double-entry invariant enforced at domain level
- Period-based locking for data integrity
- Event sourcing for audit trail completeness
- Strategy pattern for tax and depreciation calculations
