# HMRC Integration Implementation Summary

This document provides a technical overview of the HMRC (Her Majesty's Revenue and Customs) integration implementation for UK tax submissions.

## Overview

The implementation adds comprehensive support for UK tax compliance through HMRC's Making Tax Digital (MTD) initiative, including:

- **VAT Returns**: Submit quarterly/monthly VAT returns via MTD API
- **PAYE RTI**: Real-Time Information submissions for payroll (FPS, EPS, EYU)
- **Corporation Tax**: Submit corporation tax computations via MTD API

## Architecture

### Database Schema

Six new migrations extend the existing schema:

1. **`hmrc_submissions`** - Central tracking table for all HMRC submissions
   - Tracks submission type, status, HMRC references, and response data
   - Status workflow: draft → pending → submitted → accepted/rejected

2. **`hmrc_vat_returns`** - VAT return data (9-box format)
   - Stores all VAT return boxes as per HMRC requirements
   - Links to hmrc_submissions for tracking

3. **`hmrc_paye_submissions`** - PAYE RTI submission data
   - Full Payment Submission (FPS) data
   - Employee-level payroll information
   - Tax month and payment date tracking

4. **`hmrc_corporation_tax_submissions`** - CT computations
   - Accounting period and profit data
   - Tax calculations with marginal relief
   - Due dates for filing and payment

5. **Extended `companies` table** - HMRC registration details
   - VAT number, PAYE reference, Corporation Tax UTR
   - Accounts Office Reference
   - VAT scheme and period configuration

6. **Extended `employees` table** - PAYE employee data
   - National Insurance numbers
   - Starter declarations and P45 data
   - Student loan plan information

### Models

Four new Eloquent models with business logic:

- **`HmrcSubmission`** - Base submission model
  - Status management methods
  - Polymorphic relationships to specific submission types
  
- **`HmrcVatReturn`** - VAT return management
  - Auto-calculation from invoices and expenses
  - 9-box validation
  - Finalisation workflow
  
- **`HmrcPayeSubmission`** - PAYE RTI data
  - Payroll aggregation
  - Employee data preparation
  - Tax year calculations
  
- **`HmrcCorporationTaxSubmission`** - CT computation
  - Financial data aggregation
  - Tax rate determination
  - Marginal relief calculation

### Services

Four service classes handle HMRC API communication:

1. **`HmrcAuthService`** - OAuth 2.0 authentication
   - Authorization URL generation
   - Token exchange and refresh
   - Token caching with automatic refresh
   
2. **`HmrcMtdVatService`** - VAT API operations
   - Submit VAT returns
   - Retrieve obligations, liabilities, payments
   - 9-box payload building
   
3. **`HmrcRtiPayeService`** - PAYE RTI submissions
   - FPS XML generation
   - EPS and EYU support
   - Configurable XML schema versions
   
4. **`HmrcMtdCorporationTaxService`** - Corporation Tax API
   - Submit computations
   - Retrieve obligations and liabilities
   - Payment tracking

### Filament Resources

Admin UI for managing submissions:

- **`HmrcVatReturnResource`** - Complete VAT return management
  - Form with all 9 boxes
  - Calculate action (auto-populate from transactions)
  - Submit action (send to HMRC)
  - Status badges and filtering

Additional resources can be created following the same pattern for PAYE and CT.

### Configuration

**`config/hmrc.php`** provides centralized configuration:
- API endpoints (sandbox/production)
- OAuth credentials
- Feature flags (VAT, PAYE, CT)
- Configurable tax rates and thresholds
- RTI XML schema versions
- Logging settings

## Key Features

### OAuth 2.0 Authentication
- Full authorization flow implementation
- Automatic token refresh
- Secure token caching
- Multi-scope support

### VAT Returns
- Automatic calculation from invoices and expenses
- Support for all VAT schemes (standard, flat rate, cash accounting)
- EC acquisitions and supplies
- Validation and finalisation workflow

### PAYE RTI
- FPS generation from payroll data
- Employee-level details (gross pay, PAYE, NI, student loans)
- Late submission handling
- XML generation with proper namespacing

### Corporation Tax
- Automatic profit calculation
- Configurable tax rates (19% small profits, 25% main rate)
- Marginal relief for profits £50k-£250k
- Historical rate support via configuration

## Data Flow

### VAT Return Submission
1. Create VAT return with period details
2. Click "Calculate" to auto-populate from transactions
3. Review and adjust boxes as needed
4. Mark as "Finalised"
5. Click "Submit to HMRC"
6. System creates HmrcSubmission record
7. API call to HMRC with OAuth token
8. Response stored, status updated
9. HMRC reference saved

### PAYE RTI Submission
1. Create PAYE submission for tax month
2. Click "Calculate from Payroll"
3. System aggregates payroll data for period
4. Review employee data
5. Submit to HMRC
6. XML generated and sent
7. Correlation ID received and stored

## Security

- OAuth 2.0 token-based authentication
- Tokens cached with expiration handling
- Sensitive data stored in environment variables
- All API calls logged for audit trail
- HTTPS required for all HMRC communication

## Testing

### Unit Tests
- **`HmrcMtdVatServiceTest`** - VAT service testing
  - HTTP response mocking
  - Validation testing
  - Error handling

### Test Coverage
- Service methods
- Validation rules
- Error scenarios
- Token refresh logic

## Configuration Steps

1. **Developer Registration**
   - Register at HMRC Developer Hub
   - Create application
   - Subscribe to required APIs
   - Note client ID and secret

2. **Environment Setup**
   ```env
   HMRC_ENABLED=true
   HMRC_ENVIRONMENT=sandbox
   HMRC_CLIENT_ID=your_client_id
   HMRC_CLIENT_SECRET=your_client_secret
   HMRC_VAT_ENABLED=true
   HMRC_PAYE_ENABLED=true
   HMRC_CT_ENABLED=true
   ```

3. **Company Setup**
   - Add VAT number
   - Add PAYE reference
   - Add Corporation Tax UTR
   - Configure VAT period

4. **Authorization**
   - Trigger OAuth flow
   - Sign in with Government Gateway
   - Grant permissions
   - Tokens stored automatically

## File Structure

```
app/
├── Models/
│   ├── HmrcSubmission.php
│   ├── HmrcVatReturn.php
│   ├── HmrcPayeSubmission.php
│   └── HmrcCorporationTaxSubmission.php
├── Services/
│   ├── HmrcAuthService.php
│   ├── HmrcMtdVatService.php
│   ├── HmrcRtiPayeService.php
│   └── HmrcMtdCorporationTaxService.php
└── Filament/App/Resources/HmrcVatReturns/
    ├── HmrcVatReturnResource.php
    └── Pages/
        ├── ListHmrcVatReturns.php
        ├── CreateHmrcVatReturn.php
        └── EditHmrcVatReturn.php

config/
└── hmrc.php

database/migrations/
├── 2024_02_16_000001_add_hmrc_fields_to_companies_table.php
├── 2024_02_16_000002_add_hmrc_fields_to_employees_table.php
├── 2024_02_16_000003_create_hmrc_submissions_table.php
├── 2024_02_16_000004_create_hmrc_vat_returns_table.php
├── 2024_02_16_000005_create_hmrc_paye_submissions_table.php
└── 2024_02_16_000006_create_hmrc_corporation_tax_submissions_table.php

tests/Unit/Services/
└── HmrcMtdVatServiceTest.php

docs/
└── HMRC_INTEGRATION.md
```

## Future Enhancements

Potential areas for expansion:

1. **Additional Filament Resources**
   - PAYE submission resource
   - Corporation tax resource
   - General HMRC submission resource

2. **Enhanced Reporting**
   - Submission history reports
   - Tax liability summaries
   - Compliance dashboards

3. **Automation**
   - Scheduled VAT return generation
   - Automatic PAYE submissions
   - Reminder notifications

4. **Advanced Features**
   - Multi-company support
   - Agent services (for accountants)
   - Bulk submission handling
   - Import from accounting systems

5. **Additional APIs**
   - Self-Assessment
   - Construction Industry Scheme (CIS)
   - National Insurance

## Compliance Notes

- All submissions are logged for audit purposes
- HMRC requires Making Tax Digital for VAT (turnover > £85k)
- PAYE RTI is mandatory for all employers
- Corporation Tax MTD rollout is gradual

## Support Resources

- **HMRC Developer Hub**: https://developer.service.hmrc.gov.uk/
- **MTD for VAT**: https://www.gov.uk/government/collections/making-tax-digital-for-vat
- **PAYE RTI**: https://www.gov.uk/guidance/what-payroll-information-to-report-to-hmrc
- **Corporation Tax**: https://www.gov.uk/guidance/making-tax-digital-for-corporation-tax

## License

This implementation is provided under the MIT license as part of the Liberu Accounting project.
