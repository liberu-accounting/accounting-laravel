# Plaid OAuth Quick Start Guide

This guide helps you quickly set up OAuth support for Plaid bank connections.

## Prerequisites

- Plaid account with client ID and secret
- Access to Plaid Dashboard
- HTTPS-enabled application (or ngrok for development)

## Setup Steps

### 1. Configure Environment Variables

Add to your `.env` file:

```env
PLAID_CLIENT_ID=your_client_id
PLAID_SECRET=your_secret
PLAID_ENV=sandbox  # or development, production
PLAID_OAUTH_REDIRECT_URI=https://your-domain.com/api/plaid/oauth-redirect
```

**For local development**, use ngrok:
```bash
ngrok http 8000
```

Then update `.env`:
```env
PLAID_OAUTH_REDIRECT_URI=https://abc123.ngrok.io/api/plaid/oauth-redirect
```

### 2. Register Redirect URI in Plaid Dashboard

1. Go to [Plaid Dashboard](https://dashboard.plaid.com)
2. Navigate to: **Team Settings → API**
3. Under **Allowed redirect URIs**, add your URI:
   - `https://your-domain.com/api/plaid/oauth-redirect` (production)
   - `https://abc123.ngrok.io/api/plaid/oauth-redirect` (development)
4. Click **Save**

### 3. Frontend Integration

#### Initial Bank Connection

```javascript
// Step 1: Get link token
const response = await fetch('/api/plaid/create-link-token', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${authToken}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ language: 'en' })
});
const { link_token } = await response.json();

// Step 2: Initialize Plaid Link with OAuth support
const handler = Plaid.create({
  token: link_token,
  receivedRedirectUri: window.location.href, // Required for OAuth
  onSuccess: async (public_token, metadata) => {
    // Exchange public token for connection
    await fetch('/api/plaid/store-connection', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authToken}`,
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
    console.log('User exited Link', err, metadata);
  }
});

handler.open();
```

#### Re-authentication (Update Mode)

When a connection status is `requires_reauth`:

```javascript
// Step 1: Get update mode link token
const response = await fetch('/api/plaid/create-link-token', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${authToken}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({ 
    connection_id: connectionId  // Pass existing connection ID
  })
});
const { link_token } = await response.json();

// Step 2: Initialize Plaid Link in update mode
const handler = Plaid.create({
  token: link_token,
  receivedRedirectUri: window.location.href,
  onSuccess: async () => {
    // Connection re-authenticated successfully
    console.log('Re-authentication successful');
    // Optionally trigger a sync
    await fetch(`/api/plaid/connections/${connectionId}/sync`, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${authToken}` }
    });
  },
  onExit: (err, metadata) => {
    console.log('Re-authentication cancelled', err, metadata);
  }
});

handler.open();
```

## How OAuth Works

```
1. User clicks "Connect Bank"
   ↓
2. Your app creates link token (includes OAuth redirect URI)
   ↓
3. User opens Plaid Link
   ↓
4. User selects their bank
   ↓
5. Plaid detects OAuth required → Redirects to bank's website
   ↓
6. User logs in at bank's official site
   ↓
7. User authorizes data sharing
   ↓
8. Bank redirects to: /api/plaid/oauth-redirect?oauth_state_id=xyz
   ↓
9. Your endpoint receives callback
   ↓
10. Plaid Link automatically resumes
   ↓
11. Connection completes successfully
```

## Testing

### Sandbox Testing

1. Use sandbox credentials in `.env`:
   ```env
   PLAID_ENV=sandbox
   ```

2. Test with OAuth-enabled institutions:
   - Chase (chase)
   - Bank of America (bofa)
   - Wells Fargo (wells)

3. Use test credentials provided by Plaid:
   - Username: `user_good`
   - Password: `pass_good`

### Verify OAuth Configuration

Check that OAuth is working:

```bash
# Make sure your redirect URI is accessible
curl https://your-domain.com/api/plaid/oauth-redirect?oauth_state_id=test

# Expected response:
# {"success":true,"message":"OAuth redirect received successfully","oauth_state_id":"test"}
```

## Troubleshooting

### "Redirect URI mismatch" Error

**Problem**: OAuth redirect fails with "redirect_uri_mismatch"

**Solution**:
- Verify `.env` and Plaid Dashboard have **exact same URI**
- Check for trailing slashes, http vs https
- Restart your application after changing `.env`

### OAuth Keeps Looping

**Problem**: After bank authentication, keeps going back to Plaid Link

**Solution**:
- Ensure `receivedRedirectUri: window.location.href` is set
- Check browser console for errors
- Verify OAuth redirect endpoint returns 200 OK

### Local Development Issues

**Problem**: OAuth doesn't work locally

**Solution**:
1. Install ngrok: `npm install -g ngrok`
2. Start ngrok: `ngrok http 8000`
3. Copy ngrok URL (e.g., `https://abc123.ngrok.io`)
4. Update `.env`: `PLAID_OAUTH_REDIRECT_URI=https://abc123.ngrok.io/api/plaid/oauth-redirect`
5. Add same URI to Plaid Dashboard
6. Restart your application

### Connection Status "requires_reauth"

**Problem**: Connection shows "requires_reauth" status

**Solution**:
Use update mode (see "Re-authentication" section above):
```javascript
// Create link token with connection_id
{ connection_id: existingConnectionId }
```

## API Endpoints

### Create Link Token
```
POST /api/plaid/create-link-token
Authorization: Bearer {token}

Request:
{
  "language": "en",
  "connection_id": 123  // Optional, for update mode
}

Response:
{
  "success": true,
  "link_token": "link-sandbox-...",
  "expiration": "2026-02-15T00:00:00Z"
}
```

### OAuth Redirect Handler
```
GET /api/plaid/oauth-redirect?oauth_state_id={state}

Response:
{
  "success": true,
  "message": "OAuth redirect received successfully",
  "oauth_state_id": "{state}"
}
```

## Security Best Practices

1. ✅ **Always use HTTPS** in production
2. ✅ **Register exact redirect URI** in Plaid Dashboard
3. ✅ **Keep OAuth state ID confidential** (don't log in client-side)
4. ✅ **Validate connection ownership** before update mode
5. ✅ **Use environment variables** for configuration
6. ✅ **Never expose client secret** to frontend

## Next Steps

1. Set up OAuth as described above
2. Test with sandbox environment
3. Integrate into your frontend application
4. Monitor connection status for re-auth needs
5. Move to production environment when ready

## Additional Resources

- [Full Plaid Integration Documentation](./PLAID_INTEGRATION.md)
- [Plaid OAuth Documentation](https://plaid.com/docs/link/oauth/)
- [Plaid Link Documentation](https://plaid.com/docs/link/)
- [Plaid Sandbox Testing](https://plaid.com/docs/sandbox/)

## Support

For issues:
- Check the [main documentation](./PLAID_INTEGRATION.md)
- Review Plaid's [OAuth guide](https://plaid.com/docs/link/oauth/)
- Open an issue in the repository
