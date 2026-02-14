# Plaid Bank Connection Integration

This document describes how to use the Plaid bank connection integration in the Liberu Accounting application.

## Overview

The Plaid integration allows users to securely connect their bank accounts and automatically sync transactions into the accounting system. This eliminates manual data entry and reduces errors.

## Features

- **Secure Bank Connection**: Connect to 12,000+ financial institutions via Plaid
- **Multi-tenancy Support**: Each user can manage their own bank connections
- **Transaction Sync**: Automatically import and categorize transactions
- **Incremental Sync**: Efficient cursor-based synchronization
- **Bank Management**: List, sync, and disconnect bank accounts
- **Encrypted Storage**: All sensitive data (access tokens, credentials) are encrypted at rest

## Setup

### 1. Get Plaid Credentials

1. Sign up for a Plaid account at [https://plaid.com](https://plaid.com)
2. Get your `client_id` and `secret` from the Plaid Dashboard
3. Choose your environment:
   - `sandbox` - For testing with fake credentials
   - `development` - For testing with real bank credentials (limited volume)
   - `production` - For live production use

### 2. Configure Environment Variables

Add the following to your `.env` file:

```env
PLAID_CLIENT_ID=your_client_id_here
PLAID_SECRET=your_secret_here
PLAID_ENV=sandbox
```

### 3. Run Migrations

Execute the migrations to add Plaid-specific fields to your database:

```bash
php artisan migrate
```

This will add:
- `user_id`, `plaid_access_token`, `plaid_item_id`, `plaid_institution_id`, `plaid_cursor`, `institution_name`, and `last_synced_at` to `bank_connections` table
- `external_id`, `bank_connection_id`, `description`, `category`, `type`, and `status` to `transactions` table

## API Endpoints

All endpoints require authentication via Laravel Sanctum.

### Create Link Token

**Endpoint:** `POST /api/plaid/create-link-token`

Creates a link token for initializing Plaid Link in your frontend.

**Request:**
```json
{
  "language": "en"
}
```

**Response:**
```json
{
  "success": true,
  "link_token": "link-sandbox-...",
  "expiration": "2026-02-15T00:00:00Z"
}
```

### Store Bank Connection

**Endpoint:** `POST /api/plaid/store-connection`

Exchanges a public token from Plaid Link for an access token and stores the bank connection.

**Request:**
```json
{
  "public_token": "public-sandbox-...",
  "institution_id": "ins_123",
  "institution_name": "Chase Bank",
  "accounts": [
    {
      "id": "acc_123",
      "name": "Checking"
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "message": "Bank connection created successfully",
  "connection": {
    "id": 1,
    "institution_name": "Chase Bank",
    "status": "active",
    "created_at": "2026-02-14T19:00:00.000000Z"
  }
}
```

### List Bank Connections

**Endpoint:** `GET /api/plaid/connections`

Lists all bank connections for the authenticated user.

**Response:**
```json
{
  "success": true,
  "connections": [
    {
      "id": 1,
      "institution_name": "Chase Bank",
      "bank_id": "ins_123",
      "status": "active",
      "last_synced_at": "2026-02-14T19:00:00.000000Z",
      "created_at": "2026-02-14T18:00:00.000000Z",
      "updated_at": "2026-02-14T19:00:00.000000Z"
    }
  ]
}
```

### Sync Transactions

**Endpoint:** `POST /api/plaid/connections/{connection}/sync`

Syncs transactions from Plaid for a specific bank connection.

**Response:**
```json
{
  "success": true,
  "message": "Transactions synced successfully",
  "summary": {
    "added": 15,
    "modified": 2,
    "removed": 0,
    "total_processed": 17
  },
  "last_synced_at": "2026-02-14T19:05:00.000000Z"
}
```

### Remove Bank Connection

**Endpoint:** `DELETE /api/plaid/connections/{connection}`

Disconnects a bank and removes it from Plaid.

**Response:**
```json
{
  "success": true,
  "message": "Bank connection removed successfully"
}
```

## Frontend Integration

### Using Plaid Link

1. **Create Link Token:**
   ```javascript
   const response = await fetch('/api/plaid/create-link-token', {
     method: 'POST',
     headers: {
       'Authorization': `Bearer ${token}`,
       'Content-Type': 'application/json',
     },
     body: JSON.stringify({ language: 'en' })
   });
   const { link_token } = await response.json();
   ```

2. **Initialize Plaid Link:**
   ```javascript
   const handler = Plaid.create({
     token: link_token,
     onSuccess: async (public_token, metadata) => {
       // Send to backend
       await fetch('/api/plaid/store-connection', {
         method: 'POST',
         headers: {
           'Authorization': `Bearer ${token}`,
           'Content-Type': 'application/json',
         },
         body: JSON.stringify({
           public_token,
           institution_id: metadata.institution.institution_id,
           institution_name: metadata.institution.name,
           accounts: metadata.accounts,
         })
       });
     },
     onExit: (err, metadata) => {
       // Handle exit
     }
   });
   
   handler.open();
   ```

3. **Sync Transactions:**
   ```javascript
   const response = await fetch(`/api/plaid/connections/${connectionId}/sync`, {
     method: 'POST',
     headers: {
       'Authorization': `Bearer ${token}`,
     }
   });
   const result = await response.json();
   console.log(`Synced ${result.summary.added} new transactions`);
   ```

## Transaction Processing

### Automatic Categorization

Transactions are automatically categorized based on Plaid's category data. The system uses the most specific category from Plaid's category hierarchy.

### Transaction Status

- `pending` - Transaction is pending and may change
- `posted` - Transaction has posted to the account

### Transaction Type

- `credit` - Money coming into the account
- `debit` - Money leaving the account

## Security Considerations

1. **Encryption**: All Plaid access tokens and credentials are encrypted at rest using Laravel's encryption
2. **Authentication**: All endpoints require user authentication
3. **Authorization**: Users can only access their own bank connections
4. **HTTPS**: Always use HTTPS in production
5. **Environment**: Use sandbox environment for development, production only for live data

## Testing

Run the test suite to verify the Plaid integration:

```bash
# Run all Plaid tests
php artisan test --filter Plaid

# Run feature tests only
php artisan test tests/Feature/Api/PlaidControllerTest.php

# Run unit tests only
php artisan test tests/Unit/Services/PlaidServiceTest.php
```

## Troubleshooting

### Common Issues

1. **Invalid credentials error**
   - Verify your `PLAID_CLIENT_ID` and `PLAID_SECRET` are correct
   - Ensure you're using the right environment (sandbox/development/production)

2. **Connection fails**
   - Check that migrations have been run
   - Verify the user is authenticated
   - Check Laravel logs for detailed error messages

3. **Transactions not syncing**
   - Ensure connection status is 'active'
   - Check that the access token is valid
   - Review Plaid API error responses in logs

## Support

For issues related to:
- Plaid API: See [Plaid Documentation](https://plaid.com/docs/)
- This integration: Check the application logs and GitHub issues

## License

This integration is part of the Liberu Accounting application and is licensed under the MIT License.
