# Coffee Plus API Contract

Base path: `/api`

Protected endpoints require:

```http
Authorization: Bearer <sanctum_token>
Accept: application/json
```

Client-supplied prices, totals, discounts, user IDs, roles, wallet balances, and payment statuses are untrusted. The backend calculates authoritative values.

## Auth

### POST `/login`

Auth: public

Body:

```json
{"email":"user@example.com","password":"secret"}
```

Success: returns Sanctum access token and user payload.

Errors: `422` validation, `401` invalid credentials, `429` throttled.

### POST `/logout`

Auth: required

Revokes the current token.

## Dashboard

### GET `/dashboard`

Auth: optional

Query: `search`, `category`.

Success: product menus, category names, options, and user summary. Product add-ons are server-defined and must be used by name when adding cart items.

## Cart

### GET `/cart`

Auth: required. Returns current user's cart items only.

### POST `/cart/add`

Auth: required

Body:

```json
{
  "product_id": 1,
  "quantity": 1,
  "size": "Regular",
  "temp": "Hot",
  "addons": ["Extra Shot"]
}
```

Validation:

- `quantity`: integer `1..20`
- `size`: one of backend `coffee.options.sizes[*].name`
- `temp`: one of backend `coffee.options.temps`
- `addons`: max 20 strings; every add-on name must belong to `product_id`

### POST `/cart/update`

Auth: required

Body:

```json
{"cart_item_id":1,"quantity":2}
```

`product_id` fallback is supported for legacy clients. Quantity is `1..20`.

### POST `/cart/remove`

Auth: required. Body contains `cart_item_id` or legacy `product_id`. Ownership is enforced by authenticated user.

## Checkout

### POST `/checkout`

Auth: required

Headers:

```http
Idempotency-Key: <stable-random-key-16-to-128-chars>
```

Body:

```json
{
  "use_oz": [1, 2],
  "coupon_code": "SAVE5",
  "pickup_time": "2026-06-13T15:00:00+08:00"
}
```

Compatibility: `idempotency_key` in body is accepted for legacy clients, but Flutter must send the header.

Behavior:

- same key and same payload returns the original response
- same key and different payload returns `409`
- missing key returns `422`
- backend computes prices, coupons, wallet deductions, stock, rewards, and pickup code

## Orders

### GET `/orders`

Auth: required. Returns authenticated user's orders only.

### GET `/orders/{order}`

Auth: required. User must own the order.

### POST `/orders/{order}/cancel`

Auth: required. Cancels eligible orders only. Refunds and stock restoration are server-side decisions.

## Coupons

### GET `/coupons/validate`

Auth: required

Query: `code`, `subtotal`.

Validation response is informational. Final redemption happens atomically during checkout and creates a coupon redemption record.

## Tangki Wallet

### GET `/tangki`

Auth: required. Returns server balance and recent transactions.

### POST `/tangki/refill`

Auth: required

Body:

```json
{"amount":50}
```

Success:

```json
{
  "status": "success",
  "redirect_url": "https://checkout.stripe.com/...",
  "message": "Redirect to Stripe checkout."
}
```

Client must open `redirect_url`. Client must not mutate wallet balance locally. Balance changes only after the Stripe webhook processes a paid MYR Checkout session.

## Profile

### GET `/profile`

Auth: required. Returns authenticated user's profile.

### POST `/profile/update`

Auth: required. Updates profile fields. Server validation applies.

### POST `/profile/password`

Auth: required. If backend returns a new `access_token`, the mobile client must persist it immediately and use it for subsequent requests.

## Notifications

Auth: required

- `GET /profile/notifications`
- `POST /profile/notifications/{id}/read`
- `POST /profile/notifications/delete-read`
- `POST /profile/notifications/batch-delete`

## Favorites

Auth: required. Resource ownership is scoped by authenticated user. Add-ons follow the same server-defined names used by cart items.

## Shared Recipes

### GET `/recipes`

Auth: required. Returns recipes received by authenticated user.

### POST `/recipes`

Auth: required

Body fields: `recipient_id`, `product_id`, `name`, `size`, `temp`, `addons`, `remark`.

Validation:

- recipient must exist and be an accepted friend
- size/temp must be valid backend options
- add-ons must belong to product
- remark max length is 1000

### POST `/recipes/{id}/import`

Auth: required. Only the recipient can import the recipe.

## Stripe Webhook

### POST `/stripe/webhook`

Auth: Stripe signature only

Required header:

```http
Stripe-Signature: <stripe-generated-signature>
```

Backend processes only `checkout.session.completed` events that are:

- `payment_status = paid`
- `mode = payment`
- `currency = myr`
- `amount_total > 0`
- metadata type in `refill`, `tangki_refill`, or `checkout`
- metadata has a valid `user_id`
- event ID/session ID not already processed

Redirect URLs never create orders or credit Tangki.
