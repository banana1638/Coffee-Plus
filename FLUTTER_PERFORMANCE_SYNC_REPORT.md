# Flutter Performance Sync Report

Date: 2026-07-07

Backend repository:
- `C:\laragon\www\Coffee-Plus`

Flutter repository:
- `C:\Users\LOQ\Coffee-Plus-App`

## Backend Change Summary

The backend now returns lighter transaction summary payloads for list screens:

- `GET /api/tangki`
- `GET /api/transactions`
- `GET /api/refunds`

These list endpoints no longer embed `order_details` by default.

Full order detail remains available from:

- `GET /api/transactions/{bill_id}`

## Required Flutter Updates

Update Flutter transaction, refund, and Tangki list parsing to treat `order_details` as optional or absent.

List models should rely on summary fields:

- `id`
- `bill_id`
- `type`
- `oz_delta`
- `description`
- `time`
- `timestamp`

When a user opens a transaction or refund detail, Flutter should call:

```http
GET /api/transactions/{bill_id}
```

Then parse:

```json
{
  "order": {
    "bill_id": "CP-...",
    "items": []
  }
}
```

## Compatibility Notes

- Backend ownership and authorization are unchanged.
- Wallet/Tangki balance remains backend-owned.
- Payment/refill success remains backend-owned.
- Order totals remain backend-calculated.
- Existing `bill_id` is still present in transaction summaries.
- A missing `order_details` key in list responses is now valid.

## Suggested Flutter Behavior

1. Render list rows from transaction summary fields only.
2. Show a loading state when opening a transaction detail.
3. Fetch detail with `/api/transactions/{bill_id}`.
4. Cache detail locally for the session if useful.
5. Treat missing `order_details` in list payloads as expected, not an error.

## Expected Benefit

For transaction, refund, and Tangki list screens:

- Estimated API payload reduction: 40% to 80%.
- Estimated backend memory reduction on those requests: 40% to 80%.
- Estimated list response speed improvement on larger accounts: 25% to 60%.

Actual gains depend on order item count and transaction history size.
