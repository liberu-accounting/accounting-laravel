# Plaid Integration - Developer Testing Guide

This guide helps developers test the Plaid bank connection integration locally.

## Prerequisites

- PHP 8.3+
- Composer
- Laravel development environment
- Plaid account (free sandbox account)

## Setup for Local Testing

### 1. Get Plaid Sandbox Credentials

1. Sign up at [Plaid Dashboard](https://dashboard.plaid.com/signup)
2. Navigate to Team Settings → Keys
3. Copy your `client_id` and `sandbox` secret
4. Keep these credentials secure

### 2. Configure Environment

Update your `.env` file:

```env
PLAID_CLIENT_ID=your_sandbox_client_id
PLAID_SECRET=your_sandbox_secret
PLAID_ENV=sandbox
```

### 3. Run Migrations

```bash
php artisan migrate
```

This creates the necessary tables with Plaid-specific fields.

### 4. Seed Test Data (Optional)

Create a test user if you don't have one:

```bash
php artisan tinker
>>> $user = App\Models\User::factory()->create(['email' => 'test@example.com', 'password' => bcrypt('password')]);
>>> $user->createToken('test-token')->plainTextToken;
// Copy the token for API requests
```

## Testing the Integration

### Option 1: Automated Tests

Run the comprehensive test suite:

```bash
# Run all Plaid tests
php artisan test --filter=Plaid

# Run specific test classes
php artisan test tests/Feature/Api/PlaidControllerTest.php
php artisan test tests/Unit/Services/PlaidServiceTest.php

# Run with coverage (if configured)
php artisan test --filter=Plaid --coverage
```

**Expected Results:**
- 15 feature tests should pass
- 11 unit tests should pass
- 100% success rate

### Option 2: Manual API Testing

#### Step 1: Create Link Token

```bash
curl -X POST http://localhost:8000/api/plaid/create-link-token \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"language": "en"}'
```

**Expected Response:**
```json
{
  "success": true,
  "link_token": "link-sandbox-...",
  "expiration": "2026-02-15T00:00:00Z"
}
```

#### Step 2: Use Plaid Link (Frontend)

In sandbox mode, you can use these test credentials:
- **Username:** `user_good`
- **Password:** `pass_good`
- **Institution:** Any (e.g., "Chase")

After successful connection, Plaid Link will provide a `public_token`.

#### Step 3: Store Connection

```bash
curl -X POST http://localhost:8000/api/plaid/store-connection \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "public_token": "public-sandbox-...",
    "institution_id": "ins_3",
    "institution_name": "Chase",
    "accounts": [{"id": "acc_123", "name": "Checking"}]
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Bank connection created successfully",
  "connection": {
    "id": 1,
    "institution_name": "Chase",
    "status": "active",
    "created_at": "..."
  }
}
```

#### Step 4: List Connections

```bash
curl -X GET http://localhost:8000/api/plaid/connections \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response:**
```json
{
  "success": true,
  "connections": [
    {
      "id": 1,
      "institution_name": "Chase",
      "status": "active",
      "last_synced_at": null,
      ...
    }
  ]
}
```

#### Step 5: Sync Transactions

```bash
curl -X POST http://localhost:8000/api/plaid/connections/1/sync \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Transactions synced successfully",
  "summary": {
    "added": 10,
    "modified": 0,
    "removed": 0,
    "total_processed": 10
  },
  "last_synced_at": "2026-02-14T19:30:00.000000Z"
}
```

#### Step 6: Verify Transactions in Database

```bash
php artisan tinker
>>> App\Models\Transaction::where('bank_connection_id', 1)->count()
=> 10
>>> App\Models\Transaction::where('bank_connection_id', 1)->first()->toArray()
```

### Option 3: Using Postman/Insomnia

Import the API collection:

1. Create new collection/folder "Plaid Integration"
2. Add environment variables:
   - `base_url`: `http://localhost:8000`
   - `token`: Your authentication token
3. Create requests for each endpoint (see manual testing above)
4. Save and run collection

## Sandbox Test Data

Plaid sandbox provides test data automatically:

### Test Institutions
- Chase
- Bank of America
- Wells Fargo
- Citi
- US Bank

### Test Credentials
- **Success:** `user_good` / `pass_good`
- **Account locked:** `user_account_locked` / `pass_good`
- **Invalid credentials:** `user_bad` / `pass_good`

### Test Scenarios

**Scenario 1: Normal Flow**
1. Create link token ✓
2. Use `user_good` credentials ✓
3. Store connection ✓
4. Sync transactions ✓
5. Verify data in database ✓

**Scenario 2: Multiple Syncs**
1. Store connection ✓
2. First sync (gets all transactions) ✓
3. Second sync (gets only new transactions) ✓
4. Verify cursor is updated ✓

**Scenario 3: Error Handling**
1. Try to sync inactive connection (should fail) ✓
2. Try to access another user's connection (should fail) ✓
3. Try with invalid token (should fail) ✓

## Verifying Database State

Check the database after each operation:

```bash
php artisan tinker

# Check bank connections
>>> App\Models\BankConnection::all(['id', 'institution_name', 'status', 'last_synced_at'])

# Check transactions
>>> App\Models\Transaction::where('bank_connection_id', 1)->count()

# Check bank feed transactions (raw Plaid data)
>>> App\Models\BankFeedTransaction::where('bank_connection_id', 1)->count()

# Check encryption
>>> $conn = App\Models\BankConnection::first()
>>> $conn->plaid_access_token  // Should show decrypted value
>>> $conn->getOriginal('plaid_access_token')  // Shows encrypted value
```

## Common Issues and Solutions

### Issue: "Invalid credentials" error
**Solution:** Verify `PLAID_CLIENT_ID` and `PLAID_SECRET` in `.env`

### Issue: "Token expired" error
**Solution:** Link tokens expire after 30 minutes. Generate a new one.

### Issue: "Connection not found"
**Solution:** Ensure you're using the correct connection ID and user is authenticated

### Issue: Transactions not appearing
**Solution:** 
1. Check connection status is 'active'
2. Verify sync completed successfully
3. Check Laravel logs: `tail -f storage/logs/laravel.log`

### Issue: Tests failing
**Solution:**
1. Ensure database is configured for testing
2. Run migrations: `php artisan migrate --env=testing`
3. Clear cache: `php artisan cache:clear`

## Performance Testing

Test with multiple connections and transactions:

```bash
php artisan tinker

# Create multiple connections
>>> $user = App\Models\User::first();
>>> for ($i = 0; $i < 5; $i++) {
>>>     App\Models\BankConnection::factory()->create(['user_id' => $user->id]);
>>> }

# Test sync performance
>>> $start = microtime(true);
>>> // Run sync endpoint
>>> $duration = microtime(true) - $start;
>>> echo "Sync took: " . $duration . " seconds";
```

## Security Testing

1. **Test authentication:**
   ```bash
   curl http://localhost:8000/api/plaid/connections
   # Should return 401 Unauthorized
   ```

2. **Test authorization:**
   - Create connection as User A
   - Try to access as User B
   - Should return 403 Forbidden

3. **Test encryption:**
   ```bash
   php artisan tinker
   >>> $conn = App\Models\BankConnection::first();
   >>> DB::table('bank_connections')->where('id', $conn->id)->first()->plaid_access_token
   # Should show encrypted string, not plain text
   ```

## Next Steps

After successful local testing:

1. Review the integration documentation: `docs/PLAID_INTEGRATION.md`
2. Test with real credentials in development environment
3. Implement frontend Plaid Link component
4. Add monitoring and logging
5. Plan production deployment

## Support

- **Plaid API Docs:** https://plaid.com/docs/
- **Laravel Docs:** https://laravel.com/docs
- **Project Issues:** Check GitHub repository

## Checklist

Before marking testing complete:

- [ ] All automated tests passing (26/26)
- [ ] Manual API flow tested successfully
- [ ] Database state verified after each operation
- [ ] Encryption verified for sensitive fields
- [ ] Authorization checks working correctly
- [ ] Error scenarios handled gracefully
- [ ] Transaction sync working with cursor
- [ ] Documentation reviewed and understood

Happy testing! 🚀
