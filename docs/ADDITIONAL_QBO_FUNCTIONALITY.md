# Additional QuickBooks Online Functionality

This document describes the additional QuickBooks Online-compatible functionality that has been added to complement the existing Bills, Estimates, Credit Memos, and Financial Statements features.

## Overview

The following critical QuickBooks Online features have been implemented:

1. **Sales Receipts** - Immediate payment transactions (cash sales)
2. **Vendor Credits** - Credits received from vendors
3. **Delayed Charges** - Future charges to be added to invoices
4. **Refund Receipts** - Customer refunds for returns/overpayments

## Sales Receipts

### What is a Sales Receipt?

A Sales Receipt is used when you receive full payment at the time of the sale. Unlike invoices (where payment comes later), sales receipts record both the sale and payment simultaneously. This is ideal for:
- Retail point-of-sale transactions
- Cash sales
- Immediate online payments
- Walk-in customer transactions

### Features

- Complete sales receipt lifecycle management
- Multi-line item support with account coding
- Multiple payment methods (cash, check, credit card, debit card, bank transfer)
- Tax calculation support
- Automatic receipt number generation
- Deposit account tracking
- Ability to create refund receipts from sales receipts
- Status tracking (draft, completed, void)

### Models

- **SalesReceipt** - Main sales receipt entity
- **SalesReceiptItem** - Line items for sales receipts

### Usage Example

```php
use App\Models\SalesReceipt;

// Create a sales receipt
$receipt = SalesReceipt::create([
    'customer_id' => 1,
    'sales_receipt_date' => now(),
    'payment_method' => 'credit_card',
    'reference_number' => 'CC-12345',
    'deposit_to_account_id' => 10, // Bank account
    'tax_rate_id' => 1,
]);

// Add line items
$receipt->items()->create([
    'account_id' => 5, // Revenue account
    'description' => 'Product XYZ',
    'quantity' => 2,
    'unit_price' => 50.00,
    'amount' => 100.00,
]);

// Calculate totals
$receipt->calculateTotals();

// Create a refund from this receipt if needed
$refund = $receipt->createRefund([
    ['description' => 'Product XYZ', 'quantity' => 1, 'unit_price' => 50.00]
], 'Customer returned item');
```

### UI Access

- List Sales Receipts: `/app/sales-receipts`
- Create Sales Receipt: `/app/sales-receipts/create`
- Edit Sales Receipt: `/app/sales-receipts/{id}/edit`

Available in the Filament admin interface under **Sales** → **Sales Receipts**

## Vendor Credits

### What is a Vendor Credit?

A Vendor Credit represents a credit received from a supplier for:
- Returned merchandise
- Overpayments
- Billing errors
- Discounts or rebates

Vendor credits can be applied to future bills from the same vendor, reducing the amount you owe.

### Features

- Complete vendor credit lifecycle management
- Multi-line item support with account coding
- Track credit applications to bills
- Automatic remaining balance calculation
- Multiple credit reasons (product return, overpayment, billing error, discount)
- Link to original bills
- Status tracking (draft, open, partial, applied, void)

### Models

- **VendorCredit** - Main vendor credit entity
- **VendorCreditItem** - Line items for vendor credits
- **VendorCreditApplication** - Tracks applications to bills

### Usage Example

```php
use App\Models\VendorCredit;

// Create a vendor credit
$credit = VendorCredit::create([
    'vendor_id' => 1,
    'credit_date' => now(),
    'reason' => 'product_return',
    'bill_id' => 5, // Optional: original bill
]);

// Add line items
$credit->items()->create([
    'account_id' => 6, // Expense account
    'description' => 'Returned defective items',
    'quantity' => 5,
    'unit_price' => 20.00,
    'amount' => 100.00,
]);

// Calculate totals
$credit->calculateTotals();

// Apply credit to a bill
$credit->applyToBill($billId, 50.00); // Apply $50 to bill

// Check remaining balance
$remaining = $credit->amount_remaining;
```

### UI Access

- List Vendor Credits: `/app/vendor-credits`
- Create Vendor Credit: `/app/vendor-credits/create`
- Edit Vendor Credit: `/app/vendor-credits/{id}/edit`

Available in the Filament admin interface under **Vendors** → **Vendor Credits**

## Delayed Charges

### What is a Delayed Charge?

A Delayed Charge records a future charge that will be added to a customer's invoice later. This is useful for:
- Tracking billable work in progress
- Scheduled services not yet invoiced
- Recurring charges to be bundled
- Project time and materials tracking

**Important:** Delayed charges don't affect accounts receivable or income until they are added to an invoice.

### Features

- Simple charge tracking
- Automatic amount calculation from quantity × unit price
- Link charges to invoices when ready
- Account assignment for proper revenue recognition
- Status tracking (pending, invoiced, void)

### Model

- **DelayedCharge** - Single model with all charge details

### Usage Example

```php
use App\Models\DelayedCharge;

// Create a delayed charge
$charge = DelayedCharge::create([
    'customer_id' => 1,
    'charge_date' => now(),
    'description' => 'Consulting hours - Week 1',
    'quantity' => 8,
    'unit_price' => 150.00,
    'account_id' => 5, // Revenue account
    'notes' => 'Weekly consulting time',
]);
// Amount auto-calculated: 8 × $150 = $1,200

// Later, add to an invoice
$charge->addToInvoice($invoiceId);

// Check if charge is pending
if ($charge->isPending()) {
    // Still waiting to be invoiced
}
```

### UI Access

- List Delayed Charges: `/app/delayed-charges`
- Create Delayed Charge: `/app/delayed-charges/create`
- Edit Delayed Charge: `/app/delayed-charges/{id}/edit`

Available in the Filament admin interface under **Sales** → **Delayed Charges**

## Refund Receipts

### What is a Refund Receipt?

A Refund Receipt records a refund given to a customer for:
- Returned products
- Overpayments
- Service not rendered
- Customer dissatisfaction

Refund receipts decrease your income and show the outgoing payment to the customer.

### Features

- Multi-line item support
- Link to original sales receipt or invoice
- Multiple refund reasons
- Tax calculation based on original transaction
- Multiple payment methods
- Account tracking for refund source
- Status tracking (draft, completed, void)

### Models

- **RefundReceipt** - Main refund receipt entity
- **RefundReceiptItem** - Line items for refund receipts

### Usage Example

```php
use App\Models\RefundReceipt;

// Create a refund receipt
$refund = RefundReceipt::create([
    'customer_id' => 1,
    'sales_receipt_id' => 10, // Original sales receipt
    'refund_date' => now(),
    'payment_method' => 'credit_card',
    'reference_number' => 'REF-12345',
    'reason' => 'product_return',
    'refund_from_account_id' => 10, // Bank account
]);

// Add line items
$refund->items()->create([
    'account_id' => 5, // Revenue account
    'description' => 'Returned Product XYZ',
    'quantity' => 1,
    'unit_price' => 50.00,
    'amount' => 50.00,
]);

// Calculate totals
$refund->calculateTotals();

// Process (complete) the refund
$refund->process();
```

### UI Access

- List Refund Receipts: `/app/refund-receipts`
- Create Refund Receipt: `/app/refund-receipts/create`
- Edit Refund Receipt: `/app/refund-receipts/{id}/edit`

Available in the Filament admin interface under **Sales** → **Refund Receipts**

## Database Schema

### Sales Receipts Tables

```sql
-- sales_receipts table
CREATE TABLE sales_receipts (
    sales_receipt_id BIGINT PRIMARY KEY,
    customer_id BIGINT,
    sales_receipt_number VARCHAR UNIQUE,
    sales_receipt_date DATE,
    tax_rate_id BIGINT,
    payment_method VARCHAR,
    reference_number VARCHAR,
    subtotal_amount DECIMAL(15,2),
    tax_amount DECIMAL(15,2),
    total_amount DECIMAL(15,2),
    deposit_to_account_id BIGINT,
    notes TEXT,
    status VARCHAR,
    -- ... timestamps, soft deletes
);

-- sales_receipt_items table
CREATE TABLE sales_receipt_items (
    item_id BIGINT PRIMARY KEY,
    sales_receipt_id BIGINT,
    account_id BIGINT,
    description TEXT,
    quantity INT,
    unit_price DECIMAL(15,2),
    amount DECIMAL(15,2)
);
```

### Vendor Credits Tables

```sql
-- vendor_credits table
CREATE TABLE vendor_credits (
    vendor_credit_id BIGINT PRIMARY KEY,
    vendor_id BIGINT,
    vendor_credit_number VARCHAR UNIQUE,
    credit_date DATE,
    bill_id BIGINT,
    tax_rate_id BIGINT,
    subtotal_amount DECIMAL(15,2),
    tax_amount DECIMAL(15,2),
    total_amount DECIMAL(15,2),
    amount_applied DECIMAL(15,2),
    amount_remaining DECIMAL(15,2),
    reason VARCHAR,
    notes TEXT,
    status VARCHAR,
    -- ... timestamps, soft deletes
);

-- vendor_credit_items table
CREATE TABLE vendor_credit_items (
    item_id BIGINT PRIMARY KEY,
    vendor_credit_id BIGINT,
    account_id BIGINT,
    description TEXT,
    quantity INT,
    unit_price DECIMAL(15,2),
    amount DECIMAL(15,2)
);

-- vendor_credit_applications table
CREATE TABLE vendor_credit_applications (
    application_id BIGINT PRIMARY KEY,
    vendor_credit_id BIGINT,
    bill_id BIGINT,
    amount_applied DECIMAL(15,2),
    application_date DATE,
    -- ... timestamps
);
```

### Delayed Charges Table

```sql
-- delayed_charges table
CREATE TABLE delayed_charges (
    delayed_charge_id BIGINT PRIMARY KEY,
    customer_id BIGINT,
    charge_date DATE,
    description TEXT,
    quantity INT,
    unit_price DECIMAL(15,2),
    amount DECIMAL(15,2),
    account_id BIGINT,
    invoice_id BIGINT,
    notes TEXT,
    status VARCHAR,
    -- ... timestamps, soft deletes
);
```

### Refund Receipts Tables

```sql
-- refund_receipts table
CREATE TABLE refund_receipts (
    refund_receipt_id BIGINT PRIMARY KEY,
    customer_id BIGINT,
    sales_receipt_id BIGINT,
    invoice_id BIGINT,
    refund_receipt_number VARCHAR UNIQUE,
    refund_date DATE,
    payment_method VARCHAR,
    reference_number VARCHAR,
    subtotal_amount DECIMAL(15,2),
    tax_amount DECIMAL(15,2),
    total_amount DECIMAL(15,2),
    refund_from_account_id BIGINT,
    reason VARCHAR,
    notes TEXT,
    status VARCHAR,
    -- ... timestamps, soft deletes
);

-- refund_receipt_items table
CREATE TABLE refund_receipt_items (
    item_id BIGINT PRIMARY KEY,
    refund_receipt_id BIGINT,
    account_id BIGINT,
    description TEXT,
    quantity INT,
    unit_price DECIMAL(15,2),
    amount DECIMAL(15,2)
);
```

## Installation

### 1. Run Migrations

```bash
php artisan migrate
```

This will create all necessary tables for Sales Receipts, Vendor Credits, Delayed Charges, and Refund Receipts.

### 2. Access the UI

The new functionality is available in the Filament admin interface:

- **Sales Receipts**: Sales → Sales Receipts
- **Vendor Credits**: Vendors → Vendor Credits
- **Delayed Charges**: Sales → Delayed Charges
- **Refund Receipts**: Sales → Refund Receipts

## Model Relationships

### Updated Relationships

**Customer Model** - Added relationships:
```php
public function salesReceipts()
{
    return $this->hasMany(SalesReceipt::class, 'customer_id', 'customer_id');
}

public function delayedCharges()
{
    return $this->hasMany(DelayedCharge::class, 'customer_id', 'customer_id');
}

public function refundReceipts()
{
    return $this->hasMany(RefundReceipt::class, 'customer_id', 'customer_id');
}
```

**Vendor Model** - Added relationship:
```php
public function vendorCredits()
{
    return $this->hasMany(VendorCredit::class, 'vendor_id', 'id');
}
```

## Best Practices

### Sales Receipts

1. Always use sales receipts for immediate payment transactions
2. Set the correct deposit account for proper cash flow tracking
3. Include reference numbers for credit card or check payments
4. Use appropriate revenue accounts on line items

### Vendor Credits

1. Always link to the original bill when applicable
2. Specify the reason for the credit
3. Apply credits promptly to reduce accounts payable
4. Review unapplied credits regularly

### Delayed Charges

1. Convert to invoices regularly to recognize revenue
2. Use for project time tracking before final billing
3. Don't use for charges that should be invoiced immediately
4. Review pending charges monthly

### Refund Receipts

1. Always link to the original transaction (sales receipt or invoice)
2. Specify the reason for the refund
3. Use the same payment method as the original transaction when possible
4. Track refund trends to identify product or service issues

## QuickBooks Online Feature Parity - Complete List

This implementation provides comprehensive QuickBooks Online feature parity:

✅ **Fully Implemented**
- Sales Receipts (immediate payments)
- Vendor Credits (supplier credits)
- Delayed Charges (future billing)
- Refund Receipts (customer refunds)
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
- Bank Connections
- Bank Statements
- Fixed Assets
- Inventory

## Comparison: Sales Receipt vs Invoice

| Feature | Sales Receipt | Invoice |
|---------|--------------|---------|
| Payment timing | Immediate | Later (credit terms) |
| Accounts receivable | No | Yes |
| Use case | Cash sales, POS | Credit sales, services |
| Payment tracking | Single payment | Multiple payments possible |
| Status | Draft/Completed/Void | Draft/Sent/Paid/Overdue |

## Comparison: Vendor Credit vs Credit Memo

| Feature | Vendor Credit | Credit Memo |
|---------|---------------|-------------|
| Who receives it | Your business | Your customer |
| Direction | From supplier | To customer |
| Reduces | Accounts payable | Accounts receivable |
| Applied to | Bills | Invoices |
| Common reasons | Returns to supplier | Returns from customer |

## Support

For issues or questions about these features:

1. Check the application logs: `storage/logs/laravel.log`
2. Review the model documentation in the code
3. Open an issue on GitHub

## License

This functionality is part of the Liberu Accounting application and is licensed under the MIT License.
