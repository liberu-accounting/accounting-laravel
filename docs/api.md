

# Accounting API Documentation

## Authentication
This API uses Laravel Sanctum for authentication. To access the API endpoints, you need to:

1. Create an API token through the dashboard
2. Include the token in your requests:
   ```
   Authorization: Bearer <your-token>
   ```

## Rate Limiting
API requests are limited to 60 per minute per user.

## Endpoints

### Transactions

#### GET /api/transactions
List all transactions (paginated)

Response:
```json
{
    "data": [
        {
            "id": 1,
            "account_id": 1,
            "amount": 1000.00,
            "transaction_date": "2024-03-15",
            "description": "Sample transaction",
            "created_at": "2024-03-15T10:00:00Z",
            "updated_at": "2024-03-15T10:00:00Z"
        }
    ],
    "links": {
        "first": "http://example.com/api/transactions?page=1",
        "last": "http://example.com/api/transactions?page=1",
        "prev": null,
        "next": null
    },
    "meta": {
        "current_page": 1,
        "from": 1,
        "last_page": 1,
        "path": "http://example.com/api/transactions",
        "per_page": 15,
        "to": 1,
        "total": 1
    }
}
```

#### GET /api/transactions/{id}
Get a specific transaction

Response:
```json
{
    "data": {
        "id": 1,
        "account_id": 1,
        "amount": 1000.00,
        "transaction_date": "2024-03-15",
        "description": "Sample transaction",
        "created_at": "2024-03-15T10:00:00Z",
        "updated_at": "2024-03-15T10:00:00Z"
    }
}
```

#### POST /api/transactions
Create a new transaction

Required fields:
- account_id: integer
- amount: numeric
- transaction_date: date
- description: string

#### PUT /api/transactions/{id}
Update an existing transaction

Optional fields:
- account_id: integer
- amount: numeric
- transaction_date: date
- description: string

#### DELETE /api/transactions/{id}
Delete a transaction

### Exchange Rates

#### GET /api/exchange-rates
Get latest exchange rates

Response:
```json
{
    "base": "USD",
    "rates": {
        "EUR": 0.92,
        "GBP": 0.79,
        "JPY": 110.86
    },
    "timestamp": "2024-03-15T10:00:00Z"
}