# Plaid Bank Connection Integration

This document describes how to use the Plaid bank connection integration in the Liberu Accounting application.

## Overview

The Plaid integration allows users to securely connect their bank accounts and automatically sync transactions into the accounting system. This eliminates manual data entry and reduces errors.

## Features

- **Secure Bank Connection**: Connect to 12,000+ financial institutions via Plaid
- **OAuth Support**: Full support for OAuth-enabled institutions with automatic redirect handling (NEW)
- **Update Mode**: Re-authentication support for expired or revoked connections (NEW)
- **Multi-tenancy Support**: Each user can manage their own bank connections
- **Transaction Sync**: Automatically import and categorize transactions
- **Real-time Balance Tracking**: Monitor account balances in real-time
- **Webhook Support**: Automatic updates via Plaid webhooks
- **Async Processing**: Background job processing for transaction synchronization
- **Incremental Sync**: Efficient cursor-based synchronization
- **Bank Management**: List, sync, and disconnect bank accounts
- **Encrypted Storage**: All sensitive data (access tokens, credentials) are encrypted at rest
- **Scheduled Sync**: Automated periodic transaction updates
- **Connection Health Monitoring**: Automatic status updates for connection issues

## Setup

### 1. Get Plaid Credentials

1. Sign up for a Plaid account at [https://plaid.com](https://plaid.com)
2. Get your `client_id` and `secret` from the Plaid Dashboard
3. Get your `webhook_verification_key` from the Plaid Dashboard
4. Choose your environment:
   - `sandbox` - For testing with fake credentials
   - `development` - For testing with real bank credentials (limited volume)
   - `production` - For live production use

### 2. Configure Environment Variables

Add the following to your `.env` file:

```env
PLAID_CLIENT_ID=your_client_id_here
PLAID_SECRET=your_secret_here
PLAID_ENV=sandbox
PLAID_WEBHOOK_URL=https://your-domain.com/api/webhooks/plaid
PLAID_WEBHOOK_VERIFICATION_KEY=your_webhook_verification_key
PLAID_OAUTH_REDIRECT_URI=https://your-domain.com/api/plaid/oauth-redirect
```

**Important for OAuth:**
- The `PLAID_OAUTH_REDIRECT_URI` is required for OAuth-enabled institutions
- This URI must be registered in your Plaid Dashboard under "Allowed redirect URIs"
- For local development, use a tool like ngrok to expose your local server and use that URL
- For production, use your actual domain with HTTPS

### 3. Configure OAuth in Plaid Dashboard (Required for OAuth)

1. Go to the [Plaid Dashboard](https://dashboard.plaid.com)
2. Navigate to Team Settings → API
3. Under "Allowed redirect URIs", add your OAuth redirect URI:
   - Development: `https://your-ngrok-url.ngrok.io/api/plaid/oauth-redirect`
   - Production: `https://your-domain.com/api/plaid/oauth-redirect`
4. Save your changes

**Note:** Many modern financial institutions require OAuth for enhanced security. Without proper OAuth configuration, users may not be able to connect to these institutions.

### 4. Configure Webhooks in Plaid Dashboard

1. Go to the Plaid Dashboard
2. Navigate to the Webhooks section
3. Add your webhook URL: `https://your-domain.com/api/webhooks/plaid`
4. Copy the webhook verification key to your `.env` file

### 4. Run Migrations

Execute the migrations to add Plaid-specific fields to your database:

```bash
php artisan migrate
```

This will add:
- `user_id`, `plaid_access_token`, `plaid_item_id`, `plaid_institution_id`, `plaid_cursor`, `institution_name`, and `last_synced_at` to `bank_connections` table
- `external_id`, `bank_connection_id`, `description`, `category`, `type`, and `status` to `transactions` table
- New `bank_account_balances` table for tracking account balances

### 5. Configure Queue Workers

The integration uses Laravel queues for async processing:

```bash
# Set queue connection
QUEUE_CONNECTION=database  # or redis, sqs, etc.

# Run queue worker
php artisan queue:work
```

### 6. Schedule Automated Sync (Optional)

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Sync all active bank connections every 4 hours
    $schedule->command('plaid:sync-transactions --all')
             ->everyFourHours();
}
```

## API Endpoints

All endpoints require authentication via Laravel Sanctum.

### Create Link Token

**Endpoint:** `POST /api/plaid/create-link-token`

Creates a link token for initializing Plaid Link in your frontend. Supports both initial connection and update mode for re-authentication.

**Rate Limit:** 60 requests/minute

**Request (Initial Connection):**
```json
{
  "language": "en"
}
```

**Request (Update Mode for Re-authentication):**
```json
{
  "language": "en",
  "connection_id": 123
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

**OAuth Flow:**
- If `PLAID_OAUTH_REDIRECT_URI` is configured, the link token will include the redirect URI
- For OAuth-enabled institutions, users will be redirected to their bank's website/app for authentication
- After authentication, users are redirected back to your application via the OAuth redirect URI
- The Plaid Link flow will automatically resume after the OAuth redirect

**Update Mode (Re-authentication):**
- Use this when a connection requires re-authentication (status: `requires_reauth`)
- Pass the `connection_id` in the request to create a link token in update mode
- The user will be prompted to re-authenticate with their bank
- After successful re-authentication, the connection status will be updated to `active`

### Store Bank Connection

**Endpoint:** `POST /api/plaid/store-connection`

Exchanges a public token from Plaid Link for an access token and stores the bank connection.

**Rate Limit:** 60 requests/minute

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

**Rate Limit:** 60 requests/minute

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

**Rate Limit:** 10 requests/minute

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

### Get Account Balances (NEW)

**Endpoint:** `GET /api/plaid/connections/{connection}/balances`

Retrieves real-time account balances for all accounts in a connection.

**Rate Limit:** 30 requests/minute

**Response:**
```json
{
  "success": true,
  "message": "Balances synced successfully",
  "accounts": [
    {
      "id": 1,
      "account_name": "Checking Account",
      "account_type": "depository",
      "account_subtype": "checking",
      "current_balance": 1250.50,
      "available_balance": 1200.00,
      "currency": "USD"
    },
    {
      "id": 2,
      "account_name": "Credit Card",
      "account_type": "credit",
      "account_subtype": "credit card",
      "current_balance": -350.75,
      "available_balance": 4649.25,
      "currency": "USD"
    }
  ]
}
```

### OAuth Redirect Handler (NEW)

**Endpoint:** `GET /api/plaid/oauth-redirect`

Handles OAuth redirects from financial institutions during the Plaid Link flow. This is a public endpoint that receives the OAuth callback.

**Rate Limit:** None (public endpoint)

**Request Parameters:**
- `oauth_state_id` - The OAuth state identifier provided by Plaid

**Example:**
```
GET /api/plaid/oauth-redirect?oauth_state_id=oauth-state-abc123
```

**Response:**
```json
{
  "success": true,
  "message": "OAuth redirect received successfully",
  "oauth_state_id": "oauth-state-abc123"
}
```

**Flow:**
1. User initiates connection through Plaid Link
2. For OAuth-enabled institutions, user is redirected to bank's website/app
3. User authenticates at their bank
4. Bank redirects back to this endpoint with `oauth_state_id`
5. Plaid Link automatically resumes in your application
6. User completes the connection flow

**Important Notes:**
- This endpoint must be publicly accessible (no authentication required)
- The URL must match exactly what's configured in your Plaid Dashboard
- For mobile apps, use deep links or universal links instead
- The frontend must be ready to handle the resumption of the Plaid Link flow

### Remove Bank Connection

**Endpoint:** `DELETE /api/plaid/connections/{connection}`

Disconnects a bank and removes it from Plaid.

**Rate Limit:** 60 requests/minute

**Response:**
```json
{
  "success": true,
  "message": "Bank connection removed successfully"
}
```

## Webhooks (NEW)

The integration now supports Plaid webhooks for automatic updates.

### Webhook Endpoint

**Endpoint:** `POST /api/webhooks/plaid` (Public, no authentication required)

All webhooks are verified using HMAC-SHA256 signature validation.

### Supported Webhook Types

#### TRANSACTIONS Webhooks

- `SYNC_UPDATES_AVAILABLE`: New transactions available → Dispatches sync job
- `INITIAL_UPDATE`: Initial sync complete → Dispatches sync job
- `HISTORICAL_UPDATE`: Historical sync complete → Dispatches sync job
- `DEFAULT_UPDATE`: Legacy update notification → Dispatches sync job
- `TRANSACTIONS_REMOVED`: Transactions removed from Plaid

#### ITEM Webhooks

- `ERROR`: Connection error detected
  - `ITEM_LOGIN_REQUIRED` → Sets status to `requires_reauth`
  - `ITEM_LOCKED` → Sets status to `locked`
- `PENDING_EXPIRATION`: Connection will expire soon
- `USER_PERMISSION_REVOKED`: User revoked access → Sets status to `revoked`
- `WEBHOOK_UPDATE_ACKNOWLEDGED`: Webhook update confirmed

### Webhook Security

All webhooks are verified using HMAC-SHA256:

```php
$signature = base64_encode(
    hash_hmac('sha256', $requestBody, $webhookVerificationKey, true)
);

if (!hash_equals($signature, $providedSignature)) {
    // Reject webhook
}
```

## Console Commands (NEW)

### Sync Transactions Command

Manually trigger transaction synchronization:

```bash
# Sync all active connections
php artisan plaid:sync-transactions --all

# Sync a specific connection
php artisan plaid:sync-transactions --connection=123
```

This command:
- Finds active bank connections
- Dispatches async sync jobs
- Shows progress bar for batch operations
- Skips inactive connections

## Frontend Integration

### Using Plaid Link

#### 1. Initial Connection (with OAuth Support)

**Create Link Token:**
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

**Initialize Plaid Link:**
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
  },
  // OAuth configuration (automatically handled by Plaid Link)
  receivedRedirectUri: window.location.href, // Required for OAuth
});

handler.open();
```

**OAuth Notes:**
- The `receivedRedirectUri` parameter is required for OAuth flows
- Plaid Link will automatically detect when OAuth is needed
- Users will be redirected to their bank's website/app for authentication
- After authentication, they'll return to your app and Link will resume automatically
- No additional code is needed to handle the OAuth redirect in most cases

#### 2. Update Mode (Re-authentication)

When a connection requires re-authentication (e.g., due to `ITEM_LOGIN_REQUIRED` error):

**Create Update Mode Link Token:**
```javascript
const response = await fetch('/api/plaid/create-link-token', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ 
    language: 'en',
    connection_id: connectionId  // Pass the connection ID for update mode
  })
});
const { link_token } = await response.json();
```

**Initialize Plaid Link in Update Mode:**
```javascript
const handler = Plaid.create({
  token: link_token,
  onSuccess: async (public_token, metadata) => {
    // For update mode, the connection is already established
    // You may want to trigger a sync after successful re-authentication
    await fetch(`/api/plaid/connections/${connectionId}/sync`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
      }
    });
  },
  onExit: (err, metadata) => {
    // Handle exit
  },
  receivedRedirectUri: window.location.href,
});

handler.open();
```

#### 3. Sync Transactions

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

### Async Job Processing (NEW)

Transaction synchronization is now handled asynchronously:

1. **Webhook Received**: Plaid sends webhook notification
2. **Job Dispatched**: `SyncPlaidTransactionsJob` is queued
3. **Background Processing**: Job processes transactions without blocking
4. **Automatic Retry**: Failed jobs retry up to 3 times with exponential backoff
5. **Status Updates**: Connection status updated on errors

**Job Configuration:**
- Max attempts: 3
- Retry backoff: 60 seconds
- Timeout: 300 seconds (5 minutes)

### Automatic Categorization

Transactions are automatically categorized based on Plaid's category data. The system uses the most specific category from Plaid's category hierarchy.

### Transaction Status

- `pending` - Transaction is pending and may change
- `posted` - Transaction has posted to the account

### Transaction Type

- `credit` - Money coming into the account (negative amount in Plaid)
- `debit` - Money leaving the account (positive amount in Plaid)

### Connection Status (NEW)

Bank connections can have the following statuses:

- `active` - Connection is working normally
- `requires_reauth` - User needs to re-authenticate (login required)
- `locked` - Account is locked at the institution
- `revoked` - User revoked permissions
- `disconnected` - Connection was manually removed

## OAuth Support (NEW)

### What is OAuth?

OAuth is a more secure authentication method where users authenticate directly with their financial institution instead of providing credentials to Plaid. Many modern banks require OAuth for enhanced security.

### Benefits of OAuth

1. **Enhanced Security**: Users never share credentials with third parties
2. **Better User Experience**: Streamlined authentication flow
3. **Longer Sessions**: OAuth connections typically last longer before requiring re-authentication
4. **Compliance**: Meets requirements for many modern financial institutions
5. **User Trust**: Users authenticate on their bank's official website/app

### How OAuth Works

1. **User Initiates Connection**: User starts the Plaid Link flow
2. **OAuth Detection**: Plaid detects if the institution requires OAuth
3. **Redirect to Bank**: User is redirected to their bank's website/app
4. **Bank Authentication**: User logs in at their bank's official site
5. **Authorization**: User grants permission for data access
6. **Redirect Back**: Bank redirects back to your application
7. **Connection Complete**: Plaid Link resumes and completes the connection

### OAuth Configuration

**Required Setup:**
1. Configure `PLAID_OAUTH_REDIRECT_URI` in your `.env` file
2. Register the redirect URI in your Plaid Dashboard (Team Settings → API → Allowed redirect URIs)
3. Ensure the redirect URI is publicly accessible (use HTTPS in production)

**Development with OAuth:**
- Use ngrok or similar tool to expose your local server
- Example: `https://abc123.ngrok.io/api/plaid/oauth-redirect`
- Update both `.env` and Plaid Dashboard with the ngrok URL

**Production with OAuth:**
- Use your actual domain with HTTPS
- Example: `https://yourdomain.com/api/plaid/oauth-redirect`
- Must match exactly in both `.env` and Plaid Dashboard

### OAuth Re-authentication (Update Mode)

When a connection requires re-authentication:

1. **Detection**: System receives `ITEM_LOGIN_REQUIRED` error or webhook
2. **Status Update**: Connection status changes to `requires_reauth`
3. **User Notification**: Notify user that re-authentication is needed
4. **Update Mode**: Create link token with `connection_id` parameter
5. **Re-authentication**: User goes through OAuth flow again
6. **Status Update**: Connection status returns to `active` after successful re-authentication

**Example Flow:**
```javascript
// Detect connection needs re-authentication
if (connection.status === 'requires_reauth') {
  // Create link token in update mode
  const response = await fetch('/api/plaid/create-link-token', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ 
      connection_id: connection.id 
    })
  });
  
  const { link_token } = await response.json();
  
  // Launch Plaid Link in update mode
  const handler = Plaid.create({
    token: link_token,
    onSuccess: () => {
      // Connection re-authenticated successfully
    },
    receivedRedirectUri: window.location.href,
  });
  
  handler.open();
}
```

### Testing OAuth in Sandbox

Plaid's Sandbox environment supports OAuth testing:

1. Use sandbox credentials in your `.env` file
2. Configure a development OAuth redirect URI (e.g., ngrok URL)
3. Test with Plaid's test institutions that support OAuth
4. OAuth flow works the same as production but with test credentials

### Mobile OAuth Support

For mobile applications:
- Use deep links (iOS/Android) instead of web redirects
- Configure deep link scheme in Plaid Dashboard
- Example: `yourapp://plaid-oauth-redirect`
- Plaid Link SDK will handle the deep link automatically

### Troubleshooting OAuth

**Common Issues:**

1. **"Redirect URI mismatch" error**
   - Ensure URI in `.env` exactly matches Plaid Dashboard
   - Check for trailing slashes, http vs https, etc.

2. **OAuth redirect not working**
   - Verify URI is publicly accessible
   - Check that endpoint returns 200 OK
   - Ensure no authentication middleware on public endpoint

3. **OAuth flow loops back to start**
   - Check that `receivedRedirectUri` is set correctly
   - Verify Plaid Link is properly handling the redirect

4. **Local development issues**
   - Use ngrok or similar tool for public URL
   - Update both `.env` and Plaid Dashboard with same URL
   - Restart your application after changing `.env`

## Error Handling (NEW)

### Improved Error Handling

The integration now includes comprehensive error handling:

1. **Timeout Configuration**:
   - Request timeout: 15 seconds
   - Connection timeout: 5 seconds

2. **Retry Logic**:
   - Automatic retry on 5xx errors and network issues
   - No retry on 4xx client errors
   - Exponential backoff between retries

3. **Specific Error Handling**:
   - `ITEM_LOGIN_REQUIRED`: Updates connection to `requires_reauth`
   - `INVALID_CREDENTIALS`: Updates connection to `requires_reauth`
   - `ITEM_LOCKED`: Updates connection to `locked`
   - Rate limit errors: Proper retry with backoff

4. **Logging**:
   - All errors logged with context
   - Sensitive data excluded from logs
   - Error codes and messages preserved

## Security Considerations

1. **Encryption**: All Plaid access tokens and credentials are encrypted at rest using Laravel's encryption
2. **Authentication**: All endpoints require user authentication via Sanctum
3. **Authorization**: Users can only access their own bank connections
4. **Webhook Verification**: All webhooks verified with HMAC-SHA256 signatures
5. **Rate Limiting**: API endpoints protected with rate limiting
   - Sync endpoint: 10 requests/minute
   - Balance endpoint: 30 requests/minute
   - Other endpoints: 60 requests/minute
6. **HTTPS**: Always use HTTPS in production
7. **Environment**: Use sandbox environment for development, production only for live data
8. **Token Security**: Access tokens only decrypted when needed for API calls
9. **Error Handling**: Sensitive data excluded from error messages and logs

## Testing

Run the test suite to verify the Plaid integration:

```bash
# Run all Plaid tests
php artisan test --filter Plaid

# Run feature tests only
php artisan test tests/Feature/Api/PlaidControllerTest.php
php artisan test tests/Feature/Api/PlaidWebhookControllerTest.php
php artisan test tests/Feature/Api/PlaidBalanceTest.php

# Run unit tests only
php artisan test tests/Unit/Services/PlaidServiceTest.php
```

### Test Coverage

The test suite includes:
- ✅ Webhook signature verification
- ✅ Balance synchronization
- ✅ Transaction sync with retries
- ✅ Error handling scenarios
- ✅ Connection status updates
- ✅ Authorization checks
- ✅ Rate limiting
- ✅ HTTP mocking for Plaid API
- ✅ OAuth redirect URI configuration (NEW)
- ✅ Update mode for re-authentication (NEW)
- ✅ OAuth redirect handler (NEW)

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
   - Verify queue workers are running for async processing

4. **Webhook not working**
   - Verify webhook URL is publicly accessible (use ngrok for local testing)
   - Check webhook verification key is correctly configured
   - Review webhook signature verification in logs
   - Ensure webhook URL is configured in Plaid dashboard

5. **Balance not updating**
   - Verify connection supports balance endpoint
   - Check if account type supports real-time balances
   - Ensure access token has proper permissions

6. **Queue jobs not processing**
   - Verify `QUEUE_CONNECTION` is configured
   - Check queue workers are running: `php artisan queue:work`
   - Review failed jobs table: `php artisan queue:failed`
   - Check job logs for errors

### Debugging Tips

1. **Enable verbose logging**:
   ```env
   LOG_LEVEL=debug
   ```

2. **Monitor queue jobs**:
   ```bash
   php artisan queue:listen --verbose
   ```

3. **Check failed jobs**:
   ```bash
   php artisan queue:failed
   php artisan queue:retry {job-id}
   ```

4. **Test webhooks locally**:
   ```bash
   # Use ngrok to expose local server
   ngrok http 8000
   # Update PLAID_WEBHOOK_URL with ngrok URL
   ```

## Performance Optimization

### Best Practices

1. **Use Webhooks**: Enable webhooks for real-time updates instead of polling
2. **Queue Processing**: Always use async jobs for transaction syncing
3. **Cursor-based Sync**: The integration uses efficient cursor-based pagination
4. **Connection Pooling**: Use persistent connections for database and Redis
5. **Batch Processing**: Sync jobs process multiple transactions efficiently
6. **Rate Limiting**: Respect Plaid's rate limits with built-in throttling

### Monitoring

Monitor these metrics for optimal performance:

- Queue job processing time
- Failed job rate
- Webhook response time
- API request success rate
- Connection status distribution

## What's New in This Version

### Version 2.1 Improvements (Latest)

✨ **New Features:**
- **Full OAuth Support**: Complete OAuth implementation for modern financial institutions
- **Update Mode**: Re-authentication support for expired or revoked connections
- **OAuth Redirect Handler**: Automatic handling of OAuth callbacks
- **Enhanced Link Token Creation**: Support for both initial connection and update mode

🔒 **Security Enhancements:**
- OAuth authentication for enhanced security
- Secure redirect URI handling
- Update mode for safe re-authentication

📚 **Better Documentation:**
- Comprehensive OAuth setup guide
- Re-authentication flow documentation
- Mobile OAuth support guidelines
- Troubleshooting guide for OAuth issues

### Version 2.0 Improvements

✨ **New Features:**
- Real-time account balance tracking
- Webhook support for automatic updates
- Async job processing for transactions
- Console command for scheduled syncing
- Connection health monitoring
- Comprehensive error handling

🔒 **Security Enhancements:**
- HMAC-SHA256 webhook signature verification
- Rate limiting on all API endpoints
- Improved error handling without exposing sensitive data
- Request timeout configuration

⚡ **Performance Improvements:**
- Background job processing
- Automatic retry with exponential backoff
- Optimized database queries
- Cursor-based incremental sync

📚 **Better Testing:**
- Webhook controller tests
- Balance synchronization tests
- Improved unit test coverage
- Mock HTTP responses for reliable testing
- OAuth flow tests

## Support

For issues related to:
- Plaid API: See [Plaid Documentation](https://plaid.com/docs/)
- This integration: Check the application logs and GitHub issues

## License

This integration is part of the Liberu Accounting application and is licensed under the MIT License.
