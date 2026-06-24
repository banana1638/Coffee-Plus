# Flutter Sync Report

Date: 2026-06-24

Repository: Coffee-Plus backend

Audience: Coffee-Plus-App Flutter repository

## Summary

Backend changes added payment status polling, standardized API error JSON for common framework errors, and expanded order response fields for stable historical order display.

Flutter must not treat client-side payment success as final. The backend remains the source of truth for payment, wallet balance, order totals, coupon discounts, and order ownership.

## API Changes

### 1. Payment Status Polling

New endpoint:

```http
GET /api/payments/{sessionId}/status
Authorization: Bearer <token>
```

Success response when the authenticated user has a processed event:

```json
{
  "status": "success",
  "data": {
    "session_id": "cs_test_...",
    "provider": "stripe",
    "type": "checkout.session.completed",
    "status": "processed",
    "amount_cents": 5000,
    "currency": "myr",
    "processed_at": "2026-06-24T07:00:00.000000Z",
    "updated_at": "2026-06-24T07:00:00.000000Z"
  }
}
```

Response while webhook is not processed, session is unknown, or session belongs to another user:

```json
{
  "status": "success",
  "data": {
    "session_id": "cs_test_...",
    "status": "pending"
  }
}
```

Flutter action:
- After Stripe checkout returns to the app, poll this endpoint with backoff.
- Treat only `data.status === "processed"` as confirmed.
- For `pending`, keep showing a confirming state and let the user refresh.
- Do not locally credit Tangki balance.

### 2. Order Response Fields

Order resource now includes:

```json
{
  "coupon_code": "ONCE5",
  "coupon_discount": 5,
  "discount_cents": 500
}
```

Order item `product_name` now comes from the checkout-time snapshot when available:

```json
{
  "product_name": "Latte",
  "quantity": 1,
  "price_at_time": 10,
  "price_at_time_cents": 1000,
  "oz_at_time": 0
}
```

Flutter action:
- Prefer `discount_cents` for calculations/display.
- Continue showing `product_name` from order items.
- Do not recalculate historical order item names from the current product catalog.

### 3. Standard API Error Shape

API validation/auth/authorization/not-found errors now use this shape:

```json
{
  "status": "error",
  "message": "The quantity field must not be greater than 20.",
  "errors": {
    "quantity": [
      "The quantity field must not be greater than 20."
    ]
  }
}
```

Authentication:

```json
{
  "status": "error",
  "message": "Unauthenticated."
}
```

Not found:

```json
{
  "status": "error",
  "message": "Not found."
}
```

Flutter action:
- Update API error parsing to prefer `status`, `message`, and `errors`.
- Preserve handling by HTTP status code: 401, 403, 404, 422.
- For validation errors, display field-specific messages from `errors` when present.

## Backend-Only Changes

No Flutter code is needed for these unless the app exposes admin features:

- Admin wallet adjustment route: `POST /admin/wallet/adjust`
- Admin payment event audit pages:
  - `GET /admin/payment-events`
  - `GET /admin/payment-events/{paymentEvent}`
- Security check command:
  - `php artisan coffee:security-check`
  - `php artisan coffee:security-check --production`
- Product upload validation hardening

## Recommended Flutter Tasks

1. Add payment status polling after Stripe checkout.
2. Add UI state for payment confirmation pending.
3. Refresh Tangki balance from backend after payment is processed.
4. Update error parser for `status/message/errors`.
5. Update order detail models to include `coupon_code`, `coupon_discount`, and `discount_cents`.
6. Ensure order history displays backend-provided order item `product_name`.

## Do Not Do In Flutter

- Do not increase wallet balance locally.
- Do not trust local payment success.
- Do not calculate final order total locally.
- Do not calculate coupon discount locally as final truth.
- Do not use current product price/name to rewrite historical order detail.
