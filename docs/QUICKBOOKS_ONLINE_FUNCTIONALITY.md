# QuickBooks Online Functionality

This document describes the QuickBooks Online-compatible functionality that has been added to the Liberu Accounting application.

## Overview

The following critical QuickBooks Online features have been implemented to provide comprehensive accounting capabilities:

1. **Bills and Accounts Payable** - Vendor bill management
2. **Estimates/Quotes** - Customer estimate and quote management
3. **Credit Memos** - Invoice correction and refund tracking
4. **Financial Statements** - Profit & Loss, Balance Sheet, and Cash Flow statements

## Bills and Accounts Payable

### Features

- Complete vendor bill lifecycle management
- Multi-line bill items with account coding
- Payment tracking and partial payment support
- Automatic aging calculations
- Bill approval workflow
- Link bills to purchase orders
- Comprehensive vendor reporting

### Models

- **Bill** - Main bill entity with vendor, dates, amounts, and status
- **BillItem** - Line items for bills with account assignments
- **BillPayment** - Payment tracking for bills

### Bill Statuses

- `draft` - Bill is being created
- `open` - Bill is approved and waiting for payment
- `paid` - Bill has been fully paid
- `overdue` - Bill is past due date
- `void` - Bill has been voided

### Payment Statuses

- `unpaid` - No payments made
- `partial` - Some payments made
- `paid` - Fully paid

### Usage Example

```php
use App\Models\Bill;
use App\Models\BillItem;

// Create a bill
$bill = Bill::create([
    'vendor_id' => 1,
    'bill_date' => now(),
    'due_date' => now()->addDays(30),
    'status' => 'draft',
]);

// Add line items
$bill->items()->create([
    'account_id' => 5, // Expense account
    'description' => 'Office supplies',
    'quantity' => 1,
    'unit_price' => 150.00,
    'amount' => 150.00,
]);

// Calculate totals
$bill->calculateTotals();

// Approve the bill
$bill->approve();

// Record a payment
$bill->recordPayment([
    'payment_date' => now(),
    'amount' => 150.00,
    'payment_method' => 'check',
    'reference_number' => 'CHK-1001',
]);
```

### API Endpoints

All bill management is available through the Filament admin interface at:
- List Bills: `/app/bills`
- Create Bill: `/app/bills/create`
- Edit Bill: `/app/bills/{id}/edit`

## Estimates/Quotes

### Features

- Professional estimate creation and management
- Multiple estimate statuses (draft, sent, viewed, accepted, declined)
- Automatic expiration tracking
- Convert accepted estimates to invoices
- Customer estimate history
- Email delivery tracking

### Models

- **Estimate** - Main estimate entity
- **EstimateItem** - Line items for estimates

### Estimate Statuses

- `draft` - Estimate is being created
- `sent` - Estimate has been sent to customer
- `viewed` - Customer has viewed the estimate
- `accepted` - Customer accepted the estimate
- `declined` - Customer declined the estimate
- `expired` - Estimate has passed expiration date

### Usage Example

```php
use App\Models\Estimate;

// Create an estimate
$estimate = Estimate::create([
    'customer_id' => 1,
    'estimate_date' => now(),
    'expiration_date' => now()->addDays(30),
    'status' => 'draft',
]);

// Add line items
$estimate->items()->create([
    'description' => 'Website development',
    'quantity' => 1,
    'unit_price' => 5000.00,
    'amount' => 5000.00,
]);

// Calculate totals
$estimate->calculateTotals();

// Mark as sent
$estimate->markAsSent();

// Customer accepts
$estimate->accept();

// Convert to invoice
$invoice = $estimate->convertToInvoice();
```

### API Endpoints

All estimate management is available through the Filament admin interface at:
- List Estimates: `/app/estimates`
- Create Estimate: `/app/estimates/create`
- Edit Estimate: `/app/estimates/{id}/edit`

## Credit Memos

### Features

- Issue credit memos for invoice corrections
- Link credit memos to original invoices
- Apply credit memos to multiple invoices
- Track credit memo balances
- Support for various credit memo reasons (returns, errors, discounts)
- Automatic amount tracking

### Models

- **CreditMemo** - Main credit memo entity
- **CreditMemoItem** - Line items for credit memos
- **CreditMemoApplication** - Tracks applications to invoices

### Credit Memo Statuses

- `draft` - Credit memo is being created
- `open` - Credit memo is available for application
- `applied` - Credit memo has been fully applied
- `void` - Credit memo has been voided

### Credit Memo Reasons

- `product_return` - Customer returned products
- `billing_error` - Billing mistake correction
- `discount` - Additional discount given
- `other` - Other reasons

### Usage Example

```php
use App\Models\CreditMemo;

// Create a credit memo
$creditMemo = CreditMemo::create([
    'customer_id' => 1,
    'invoice_id' => 10, // Optional: link to original invoice
    'credit_memo_date' => now(),
    'reason' => 'product_return',
    'status' => 'draft',
]);

// Add line items
$creditMemo->items()->create([
    'description' => 'Returned item XYZ',
    'quantity' => 1,
    'unit_price' => 100.00,
    'amount' => 100.00,
]);

// Calculate totals
$creditMemo->calculateTotals();

// Apply to an invoice
$creditMemo->applyToInvoice($invoiceId, $amount);

// Check remaining balance
$remaining = $creditMemo->amount_remaining;
```

### API Endpoints

All credit memo management is available through the Filament admin interface at:
- List Credit Memos: `/app/credit-memos`
- Create Credit Memo: `/app/credit-memos/create`
- Edit Credit Memo: `/app/credit-memos/{id}/edit`

## Financial Statements

### Features

The FinancialStatementService provides three core financial reports:

1. **Profit & Loss (Income Statement)**
   - Revenue breakdown by account
   - Cost of Goods Sold calculation
   - Operating expenses
   - Net income calculation

2. **Balance Sheet**
   - Assets (current, fixed, other)
   - Liabilities (current, long-term)
   - Equity
   - Retained earnings

3. **Cash Flow Statement**
   - Operating activities
   - Investing activities
   - Financing activities
   - Net change in cash

### Service Class

The `FinancialStatementService` provides methods for generating financial statements:

```php
use App\Services\FinancialStatementService;
use Carbon\Carbon;

$service = new FinancialStatementService();

// Generate Profit & Loss
$profitLoss = $service->profitAndLoss(
    Carbon::parse('2024-01-01'),
    Carbon::parse('2024-12-31')
);

// Generate Balance Sheet
$balanceSheet = $service->balanceSheet(
    Carbon::parse('2024-12-31')
);

// Generate Cash Flow Statement
$cashFlow = $service->cashFlowStatement(
    Carbon::parse('2024-01-01'),
    Carbon::parse('2024-12-31')
);
```

### Profit & Loss Structure

```php
[
    'period' => [
        'start_date' => '2024-01-01',
        'end_date' => '2024-12-31',
    ],
    'revenue' => [
        'accounts' => [...], // Array of revenue accounts
        'total' => 100000.00,
    ],
    'cost_of_goods_sold' => [
        'accounts' => [...],
        'total' => 40000.00,
    ],
    'gross_profit' => 60000.00,
    'expenses' => [
        'accounts' => [...],
        'total' => 30000.00,
    ],
    'net_income' => 30000.00,
]
```

### Balance Sheet Structure

```php
[
    'as_of_date' => '2024-12-31',
    'assets' => [
        'accounts' => [...],
        'total' => 500000.00,
    ],
    'liabilities' => [
        'accounts' => [...],
        'total' => 200000.00,
    ],
    'equity' => [
        'accounts' => [...],
        'retained_earnings' => 100000.00,
        'total' => 300000.00,
    ],
    'total_liabilities_and_equity' => 500000.00,
]
```

## Database Schema

### Bills Tables

```sql
-- bills table
CREATE TABLE bills (
    bill_id BIGINT PRIMARY KEY,
    vendor_id BIGINT,
    bill_number VARCHAR UNIQUE,
    bill_date DATE,
    due_date DATE,
    subtotal_amount DECIMAL(15,2),
    tax_amount DECIMAL(15,2),
    total_amount DECIMAL(15,2),
    amount_paid DECIMAL(15,2),
    status VARCHAR,
    payment_status VARCHAR,
    -- ... additional fields
);

-- bill_items table
CREATE TABLE bill_items (
    item_id BIGINT PRIMARY KEY,
    bill_id BIGINT,
    account_id BIGINT,
    description TEXT,
    quantity INT,
    unit_price DECIMAL(15,2),
    amount DECIMAL(15,2),
    -- ... additional fields
);

-- bill_payments table
CREATE TABLE bill_payments (
    payment_id BIGINT PRIMARY KEY,
    bill_id BIGINT,
    payment_date DATE,
    amount DECIMAL(15,2),
    payment_method VARCHAR,
    -- ... additional fields
);
```

### Estimates Tables

```sql
-- estimates table
CREATE TABLE estimates (
    estimate_id BIGINT PRIMARY KEY,
    customer_id BIGINT,
    estimate_number VARCHAR UNIQUE,
    estimate_date DATE,
    expiration_date DATE,
    total_amount DECIMAL(15,2),
    status VARCHAR,
    -- ... additional fields
);

-- estimate_items table
CREATE TABLE estimate_items (
    item_id BIGINT PRIMARY KEY,
    estimate_id BIGINT,
    description TEXT,
    quantity INT,
    unit_price DECIMAL(15,2),
    amount DECIMAL(15,2),
    -- ... additional fields
);
```

### Credit Memos Tables

```sql
-- credit_memos table
CREATE TABLE credit_memos (
    credit_memo_id BIGINT PRIMARY KEY,
    customer_id BIGINT,
    invoice_id BIGINT,
    credit_memo_number VARCHAR UNIQUE,
    credit_memo_date DATE,
    total_amount DECIMAL(15,2),
    amount_applied DECIMAL(15,2),
    status VARCHAR,
    reason VARCHAR,
    -- ... additional fields
);

-- credit_memo_items table
CREATE TABLE credit_memo_items (
    item_id BIGINT PRIMARY KEY,
    credit_memo_id BIGINT,
    description TEXT,
    quantity INT,
    unit_price DECIMAL(15,2),
    amount DECIMAL(15,2),
    -- ... additional fields
);

-- credit_memo_applications table
CREATE TABLE credit_memo_applications (
    application_id BIGINT PRIMARY KEY,
    credit_memo_id BIGINT,
    invoice_id BIGINT,
    amount_applied DECIMAL(15,2),
    application_date DATE,
    -- ... additional fields
);
```

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

This will create all necessary tables for Bills, Estimates, Credit Memos, and related entities.

### 2. Access the UI

The new functionality is available in the Filament admin interface:

- **Bills**: Navigate to "Vendors" → "Bills"
- **Estimates**: Navigate to "Sales" → "Estimates"
- **Credit Memos**: Navigate to "Sales" → "Credit Memos"

### 3. Generate Financial Statements (API)

Use the FinancialStatementService in your code:

```php
$service = app(FinancialStatementService::class);
$profitLoss = $service->profitAndLoss($startDate, $endDate);
```

## Model Relationships

### Updated Relationships

The following existing models have been updated with new relationships:

**Vendor Model**
```php
public function bills()
{
    return $this->hasMany(Bill::class);
}
```

**Customer Model**
```php
public function estimates()
{
    return $this->hasMany(Estimate::class);
}

public function creditMemos()
{
    return $this->hasMany(CreditMemo::class);
}
```

**Invoice Model**
```php
public function creditMemos()
{
    return $this->hasMany(CreditMemo::class);
}

public function estimate()
{
    return $this->hasOne(Estimate::class);
}
```

**PurchaseOrder Model**
```php
public function bills()
{
    return $this->hasMany(Bill::class);
}
```

## Best Practices

### Bills

1. Always link bills to purchase orders when applicable
2. Use the approval workflow for better control
3. Record payments promptly to keep balances accurate
4. Set proper account codes on bill items for accurate expense tracking

### Estimates

1. Set reasonable expiration dates
2. Track estimate acceptance rates
3. Convert accepted estimates to invoices promptly
4. Use templates for common estimate types

### Credit Memos

1. Always specify the reason for credit memos
2. Link to original invoices when applicable
3. Apply credit memos promptly to avoid customer confusion
4. Track credit memo trends to identify billing issues

### Financial Statements

1. Run statements monthly for trend analysis
2. Compare periods to identify changes
3. Verify account balances before generating statements
4. Use date ranges that match your fiscal periods

## QuickBooks Online Feature Parity

This implementation provides the following QuickBooks Online features:

✅ **Implemented**
- Bills and Accounts Payable
- Estimates/Quotes
- Credit Memos
- Profit & Loss Statement
- Balance Sheet
- Cash Flow Statement
- Multi-line items on all documents
- Status tracking and workflows
- Vendor and customer relationships
- Payment tracking

🔄 **Existing Features** (already in the system)
- Invoicing
- Customers
- Vendors
- Chart of Accounts
- Journal Entries
- Purchase Orders
- Payments
- Tax Rates

## Future Enhancements

Potential future improvements:

- Customer portal for estimate approval
- Batch bill payment processing
- Recurring estimates
- Credit memo templates
- Advanced financial statement customization
- Statement comparison periods
- Export to PDF functionality
- Email delivery for estimates
- Payment reminders for overdue bills

## Support

For issues or questions about these features:

1. Check the application logs: `storage/logs/laravel.log`
2. Review the model documentation in the code
3. Open an issue on GitHub

## License

This functionality is part of the Liberu Accounting application and is licensed under the MIT License.
