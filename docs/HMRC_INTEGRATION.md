# HMRC Integration Guide

This guide explains how to configure and use the HMRC (Her Majesty's Revenue and Customs) integration for Making Tax Digital (MTD) submissions including VAT, PAYE RTI, and Corporation Tax.

## Table of Contents

- [Overview](#overview)
- [Configuration](#configuration)
- [Features](#features)
- [Getting Started](#getting-started)
- [VAT Returns](#vat-returns)
- [PAYE RTI Submissions](#paye-rti-submissions)
- [Corporation Tax](#corporation-tax)
- [API Reference](#api-reference)

## Overview

The HMRC integration provides support for:

- **Making Tax Digital for VAT**: Submit VAT returns electronically
- **PAYE Real Time Information (RTI)**: Submit Full Payment Submissions (FPS), Employer Payment Summaries (EPS), and Earlier Year Updates (EYU)
- **Making Tax Digital for Corporation Tax**: Submit corporation tax computations

## Configuration

### 1. Environment Variables

Add the following environment variables to your `.env` file:

```env
HMRC_ENABLED=true
HMRC_ENVIRONMENT=sandbox
HMRC_CLIENT_ID=your_client_id
HMRC_CLIENT_SECRET=your_client_secret
HMRC_SERVER_TOKEN=your_server_token
HMRC_CALLBACK_URL=https://your-app.com/hmrc/callback

# Enable specific features
HMRC_VAT_ENABLED=true
HMRC_PAYE_ENABLED=true
HMRC_RTI_ENABLED=true
HMRC_CT_ENABLED=true
```

### 2. HMRC Developer Account

1. Register for a developer account at [HMRC Developer Hub](https://developer.service.hmrc.gov.uk/)
2. Create an application
3. Subscribe to the required APIs:
   - VAT (MTD) API
   - PAYE RTI API
   - Corporation Tax API
4. Note your client ID and client secret

### 3. OAuth 2.0 Authorization

Before submitting returns, you must authorize the application:

1. Navigate to HMRC Settings in the admin panel
2. Click "Authorize with HMRC"
3. Sign in with your Government Gateway credentials
4. Grant the requested permissions
5. You will be redirected back to the application

## Features

### VAT Returns

**Supported Operations:**
- Create and manage VAT returns
- Automatic calculation from invoices and expenses
- Submit returns to HMRC
- Retrieve obligations and liabilities
- View submission history

**9-Box VAT Return:**
- Box 1: VAT due on sales
- Box 2: VAT due on EC acquisitions
- Box 3: Total VAT due (Box 1 + Box 2)
- Box 4: VAT reclaimed on purchases
- Box 5: Net VAT due (Box 3 - Box 4)
- Box 6: Total value of sales (excluding VAT)
- Box 7: Total value of purchases (excluding VAT)
- Box 8: Total value of EC goods supplied
- Box 9: Total value of EC acquisitions

### PAYE RTI Submissions

**Full Payment Submission (FPS):**
- Submit payroll information in real-time
- Employee-level details including gross pay, PAYE, and NI contributions
- Student loan deductions
- Late submission reasons

**Employer Payment Summary (EPS):**
- Report non-payment periods
- Claim employment allowance
- Recover statutory payments

**Earlier Year Update (EYU):**
- Correct previous year submissions
- Update employee records

### Corporation Tax

**Features:**
- Calculate corporation tax liability
- Submit computations to HMRC
- Retrieve obligations and liabilities
- Track payment due dates

## Getting Started

### Company Setup

1. Navigate to Settings > Company Profile
2. Fill in HMRC registration details:
   - **VAT Number**: Your VAT registration number
   - **PAYE Reference**: Format: 123/AB12345
   - **Corporation Tax UTR**: 10-digit Unique Taxpayer Reference
   - **Accounts Office Reference**: For PAYE payments

### Employee Setup (for PAYE)

For each employee, ensure the following fields are completed:
- National Insurance Number
- Starter Declaration (A, B, or C)
- Student Loan Plan (if applicable)

## VAT Returns

### Creating a VAT Return

1. Navigate to HMRC > VAT Returns
2. Click "New VAT Return"
3. Enter the period key (e.g., 23A1 for first quarter of 2023/24)
4. Set period dates and due date
5. Click "Calculate" to auto-populate from transactions
6. Review all boxes
7. Mark as "Finalised"
8. Click "Submit to HMRC"

### Viewing Submissions

All submissions are tracked in the HMRC Submissions table with:
- Submission reference
- Status (draft, pending, submitted, accepted, rejected)
- Response data from HMRC
- Error messages (if any)

## PAYE RTI Submissions

### Creating an FPS

1. Navigate to HMRC > PAYE Submissions
2. Click "New PAYE Submission"
3. Select tax year and month
4. Enter payment date
5. Click "Calculate from Payroll"
6. Review employee data
7. Click "Submit to HMRC"

### Late Submissions

If submitting late:
1. Check "Late Reason Provided"
2. Enter the reason for late submission
3. HMRC may still apply penalties

## Corporation Tax

### Creating a Computation

1. Navigate to HMRC > Corporation Tax
2. Click "New Submission"
3. Enter accounting period dates
4. Click "Calculate from Financials"
5. Review:
   - Turnover
   - Total profits
   - Taxable profits
   - Tax rate applied
   - Marginal relief (if applicable)
6. Click "Submit to HMRC"

## API Reference

### Services

#### HmrcAuthService
- `getAuthorizationUrl(string $scope)`: Get OAuth authorization URL
- `exchangeCodeForToken(string $code)`: Exchange authorization code for access token
- `refreshAccessToken()`: Refresh expired access token
- `getAccessToken()`: Get current valid access token

#### HmrcMtdVatService
- `submitVatReturn(HmrcVatReturn $vatReturn)`: Submit VAT return
- `getObligations(string $vrn, string $from, string $to)`: Get VAT obligations
- `getLiabilities(string $vrn, string $from, string $to)`: Get VAT liabilities
- `getPayments(string $vrn, string $from, string $to)`: Get VAT payments

#### HmrcRtiPayeService
- `submitFps(HmrcPayeSubmission $submission)`: Submit Full Payment Submission
- `submitEps(array $data, string $payeRef)`: Submit Employer Payment Summary
- `submitEyu(array $data, string $payeRef)`: Submit Earlier Year Update

#### HmrcMtdCorporationTaxService
- `submitComputation(HmrcCorporationTaxSubmission $submission)`: Submit computation
- `getObligations(string $utr, string $from, string $to)`: Get obligations
- `getLiabilities(string $utr, string $from, string $to)`: Get liabilities
- `getPayments(string $utr, string $from, string $to)`: Get payments

## Testing

The application uses HMRC's sandbox environment for testing. Set `HMRC_ENVIRONMENT=sandbox` in your `.env` file.

### Test Data

HMRC provides test scenarios and data in their sandbox:
- Test VAT numbers
- Test PAYE references
- Test UTRs

See [HMRC Developer Documentation](https://developer.service.hmrc.gov.uk/) for details.

## Troubleshooting

### Common Issues

**Authorization Failed:**
- Verify client ID and secret are correct
- Check callback URL matches exactly
- Ensure you're using the correct Government Gateway credentials

**Submission Failed:**
- Check all required fields are filled
- Verify VAT number/PAYE reference/UTR format
- Ensure return is marked as finalised
- Review error message from HMRC

**Token Expired:**
- The system automatically refreshes tokens
- If issues persist, re-authorize the application

### Logging

All HMRC API calls are logged. Check `storage/logs/laravel.log` for:
- Request details
- Response codes
- Error messages

Enable debug logging:
```env
HMRC_LOGGING_ENABLED=true
HMRC_LOG_CHANNEL=stack
```

## Support

For issues with:
- **HMRC API**: Contact HMRC Developer Support
- **Application**: Open an issue on GitHub
- **MTD Requirements**: See [HMRC Making Tax Digital](https://www.gov.uk/government/collections/making-tax-digital)

## Compliance

This integration is designed to meet HMRC Making Tax Digital requirements. However:
- You are responsible for ensuring data accuracy
- Always review submissions before sending
- Keep records of all submissions
- Maintain backups of submission data

## License

MIT License - See LICENSE file for details.
