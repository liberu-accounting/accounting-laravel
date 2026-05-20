# Double-Entry Accounting System

## Overview

This application implements a complete double-entry accounting system following Generally Accepted Accounting Principles (GAAP). The system ensures that every financial transaction is recorded in at least two accounts, maintaining the fundamental accounting equation:

**Assets = Liabilities + Equity**

## Key Features

### 1. Journal Entries

Journal entries are the foundation of the double-entry accounting system. Each journal entry:

- Contains multiple lines (minimum 2)
- Must be balanced (total debits = total credits)
- Has a unique auto-generated entry number (format: JE-YYYY-NNNNNN)
- Can be posted to update account balances
- Can be reversed to undo the effect on account balances
- Cannot be edited once posted (must be reversed first)

#### Entry Types

- **General Journal**: Standard journal entries for day-to-day transactions
- **Adjusting Entry**: Period-end adjustments (accruals, deferrals, etc.)
- **Closing Entry**: Period-end closing entries
- **Reversing Entry**: Entries that reverse previous adjusting entries

### 2. Chart of Accounts

The chart of accounts organizes all accounts in the system with:

#### Account Types

1. **Assets**: Resources owned by the business
   - Normal Balance: Debit
   - Examples: Cash, Accounts Receivable, Inventory, Equipment

2. **Liabilities**: Obligations owed to others
   - Normal Balance: Credit
   - Examples: Accounts Payable, Loans Payable, Accrued Expenses

3. **Equity**: Owner's interest in the business
   - Normal Balance: Credit
   - Examples: Common Stock, Retained Earnings, Owner's Capital

4. **Revenue**: Income from business operations
   - Normal Balance: Credit
   - Examples: Sales Revenue, Service Revenue, Interest Income

5. **Expenses**: Costs incurred to generate revenue
   - Normal Balance: Debit
   - Examples: Rent Expense, Salaries Expense, Utilities

#### Account Features

- **Account Number**: Unique identifier (typically 4-digit)
- **Account Name**: Descriptive name
- **Description**: Optional detailed description
- **Normal Balance**: Debit or Credit (auto-set based on account type)
- **Opening Balance**: Starting balance for the account
- **Current Balance**: Updated automatically when journal entries are posted
- **Parent-Child Hierarchy**: Accounts can have parent accounts for organizational purposes
- **Active/Inactive Status**: Inactive accounts cannot be used in new transactions
- **Manual Entry Control**: Parent accounts automatically block manual entries

### 3. Account Balance Updates

When a journal entry is posted:

1. System validates that total debits equal total credits
2. For each line in the journal entry:
   - If the account has a **debit** normal balance:
     - Debits increase the balance
     - Credits decrease the balance
   - If the account has a **credit** normal balance:
     - Credits increase the balance
     - Debits decrease the balance
3. All balance updates occur within a database transaction (all-or-nothing)

### 4. Data Validation

The system enforces several validation rules:

- Journal entries must be balanced before posting
- Posted entries cannot be edited (only reversed)
- Parent accounts with children cannot accept journal entries
- Inactive accounts cannot be used in new entries
- Accounts cannot be deleted if they have transactions

## Usage Guide

### Creating a Journal Entry

1. Navigate to **Journal Entries** in the Accounting menu
2. Click **Create**
3. Fill in the entry details:
   - Entry Date (defaults to today)
   - Entry Type (defaults to General Journal)
   - Reference Number (optional)
   - Memo (optional description)
4. Add journal entry lines (minimum 2):
   - Select an account
   - Enter either a debit OR credit amount (not both)
   - Add a description (optional)
5. Verify that **Total Debits = Total Credits** (shown at bottom)
6. Click **Create**

### Posting a Journal Entry

1. Navigate to the journal entry list
2. Find the unposted entry
3. Click the **Post** action button
4. Confirm the posting
5. The entry is now posted and account balances are updated

### Reversing a Posted Entry

1. Navigate to the journal entry list
2. Find the posted entry
3. Click the **Reverse** action button
4. Confirm the reversal
5. Account balances are restored to their pre-posting state
6. The entry can now be edited or deleted

### Setting Up Accounts

1. Navigate to **Chart of Accounts**
2. Click **Create**
3. Fill in account details:
   - Account Number (unique)
   - Account Name
   - Description (optional but recommended)
   - Account Type (Asset, Liability, Equity, Revenue, Expense)
   - Normal Balance (auto-set based on type)
   - Opening Balance (if applicable)
   - Parent Account (optional, for hierarchy)
   - Active status
   - Allow Manual Entry (uncheck for system accounts)
4. Click **Create**

## Examples

### Example 1: Recording a Cash Sale

**Transaction**: Sold goods for $1,000 cash

**Journal Entry**:
```
Date: 2024-01-15
Entry Type: General Journal
Memo: Cash sale of goods

Lines:
- Cash (1010)                    Debit: $1,000
- Sales Revenue (4010)           Credit: $1,000

Total Debits: $1,000
Total Credits: $1,000
Status: Balanced ✓
```

**Effect**:
- Cash account increases by $1,000 (asset increase)
- Revenue account increases by $1,000 (revenue increase)

### Example 2: Recording an Expense Payment

**Transaction**: Paid rent of $2,000 by check

**Journal Entry**:
```
Date: 2024-01-01
Entry Type: General Journal
Memo: Monthly rent payment

Lines:
- Rent Expense (5010)            Debit: $2,000
- Cash (1010)                    Credit: $2,000

Total Debits: $2,000
Total Credits: $2,000
Status: Balanced ✓
```

**Effect**:
- Rent Expense increases by $2,000 (expense increase)
- Cash decreases by $2,000 (asset decrease)

### Example 3: Recording a Purchase on Credit

**Transaction**: Purchased supplies for $500 on credit

**Journal Entry**:
```
Date: 2024-01-10
Entry Type: General Journal
Memo: Purchased office supplies

Lines:
- Office Supplies (1030)         Debit: $500
- Accounts Payable (2010)        Credit: $500

Total Debits: $500
Total Credits: $500
Status: Balanced ✓
```

**Effect**:
- Office Supplies increases by $500 (asset increase)
- Accounts Payable increases by $500 (liability increase)

### Example 4: Multi-Line Entry

**Transaction**: Paid $1,500 total - $1,000 for rent and $500 for utilities

**Journal Entry**:
```
Date: 2024-01-31
Entry Type: General Journal
Memo: Monthly operating expenses

Lines:
- Rent Expense (5010)            Debit: $1,000
- Utilities Expense (5020)       Debit: $500
- Cash (1010)                    Credit: $1,500

Total Debits: $1,500
Total Credits: $1,500
Status: Balanced ✓
```

**Effect**:
- Rent Expense increases by $1,000
- Utilities Expense increases by $500
- Cash decreases by $1,500

## Technical Architecture

### Models

- **JournalEntry**: Header record for each journal entry
- **JournalEntryLine**: Individual debit/credit lines
- **Account**: Chart of accounts with balance tracking

### Key Methods

#### JournalEntry Model

- `isBalanced()`: Checks if total debits equal total credits
- `post()`: Posts the entry and updates account balances
- `reverse()`: Reverses a posted entry
- `generateEntryNumber()`: Creates unique entry numbers

#### Account Model

- `canAcceptEntries()`: Validates if account can accept manual entries
- `getCalculatedBalanceAttribute()`: Calculates balance including child accounts

### Validation

- **DoubleEntryValidator**: Validates that journal entries are balanced
- Form validation in CreateJournalEntry and EditJournalEntry pages
- Database constraints for data integrity

## Best Practices

1. **Always Post Balanced Entries**: The system will prevent unbalanced entries, but verify before posting
2. **Use Descriptive Memos**: Add clear descriptions to help with future reference
3. **Organize Accounts**: Use parent-child relationships to organize your chart of accounts
4. **Regular Reconciliation**: Regularly verify account balances against external records
5. **Don't Edit Posted Entries**: Reverse and create a new entry instead
6. **Use Reference Numbers**: Link entries to source documents (invoices, receipts, etc.)
7. **Period-End Procedures**: Use adjusting entries for accruals and deferrals

## Security & Audit Trail

- All journal entries track the creating user
- Posted entries are immutable (cannot be edited)
- Reversal actions are tracked separately
- Original entry numbers are preserved even after reversal
- All changes can be traced through the audit log

## Reporting Capabilities

The double-entry system enables standard accounting reports:

- **General Ledger**: All transactions by account
- **Trial Balance**: List of all accounts with debit/credit balances
- **Balance Sheet**: Assets, Liabilities, and Equity at a point in time
- **Income Statement**: Revenues and Expenses for a period
- **Cash Flow Statement**: Cash inflows and outflows

## Migration from Legacy Data

When migrating from an existing system:

1. Set up your chart of accounts with opening balances
2. Create opening balance journal entries to establish starting positions
3. Begin recording new transactions using the journal entry system
4. Verify that your accounting equation balances

## Support & Troubleshooting

### Common Issues

**Issue**: Cannot post journal entry
- **Solution**: Verify that total debits equal total credits

**Issue**: Cannot edit a journal entry
- **Solution**: Reverse the entry first, then create a new one

**Issue**: Account not appearing in journal entry form
- **Solution**: Check that account is active and doesn't have child accounts

**Issue**: Balance seems incorrect
- **Solution**: Review all posted journal entries for the account, ensure entries were posted correctly

## Future Enhancements

Potential future improvements:

- Approval workflow for journal entries
- Recurring journal entries
- Template journal entries
- Multi-currency support with automatic revaluation
- Budget vs. actual comparisons
- More advanced financial reporting
- Integration with bank feeds
- Tax calculation and reporting
