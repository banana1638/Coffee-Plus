# AI Security Baseline

## Backend Security Rules

1. Never trust client-provided money.
2. Never trust client-provided role.
3. Never trust client-provided order status.
4. Never trust client-provided payment success.
5. Never trust client-provided coupon discount.
6. Verify authorization on every sensitive endpoint.
7. Use transactions for money/order/coupon/inventory changes.
8. Use idempotency for payment/refill callbacks.
9. Validate file upload type, size, and path.
10. Do not expose debug stack traces in production.
11. Do not commit secrets.
12. Preserve Sanctum auth on protected API routes.
13. Preserve `admin.permission` middleware on admin actions.
14. Preserve Stripe webhook signature validation.
15. Preserve ledger idempotency keys for wallet movements.
16. Payment/refill status endpoints must not expose another user's session status.
17. Admin wallet adjustments must use `LedgerService` and `AuditLogService`; never update wallet columns directly.
18. Product image uploads must validate MIME type, extension, file size, and decodable image content.
19. Historical order item names and discounts must come from backend snapshots, not current product state.
20. Payment retries must re-query the provider, match the server-owned pending record, preserve idempotency, and write an admin audit log.
21. Stripe callbacks must match the server-created pending payment user, type, amount, and currency before processing.
22. Production CORS origins must be explicitly allowlisted; wildcard origins are forbidden.
23. Every order status creation or transition must append an immutable status history with actor and source context.
24. API tokens must have bounded lifetime, bounded per-user count, device labels, and owner-scoped revocation.
25. Enabled administrator 2FA must complete a short-lived TOTP or one-time recovery-code challenge before authentication.
26. Telescope must use `/admin/telescope`, the admin guard, owner-only permission checks, and scheduled pruning.
27. Checkout must reprice every cart item from current backend product, size, and add-on data before charging or debiting.
28. Wallet ledger movements must be positive and preserve direction/before/after balance invariants in both service and production database layers.
29. Security headers apply globally; HSTS is enabled only after HTTPS is available.
30. Private notification channels must use the authenticated user's UUID and must never authorize by a client-selected database user ID.
31. Payment, refill, and order notifications may be emitted only after the corresponding server-side transaction has committed; notification transport failure must not change money or order truth.
32. Scheduled pickup reminders must use a persisted idempotency marker and a row lock so overlapping scheduler runs cannot repeatedly notify the same order.
33. Production Reverb applications must use explicit trusted origins; wildcard `allowed_origins` is forbidden.

## Critical Review Areas

- wallet/Tangki balance
- payment/refill flow
- checkout
- coupons
- admin actions
- file upload
- broadcasting auth
- API middleware

## Detected Security Controls

- API protected routes use `auth:sanctum`.
- Login/register/checkout/refill/coupon/review/favorite routes have throttling in `routes/api.php`.
- Admin routes use `auth:admin` plus `admin.permission:*` middleware for sensitive actions.
- Stripe webhook uses `Stripe\Webhook::constructEvent`.
- `PaymentEvent` is used to deduplicate processed Stripe events/sessions.
- `LedgerService` uses row locks, transactions, and idempotency keys for wallet credit/debit.
- Checkout uses cart locks, database transactions, stock checks, coupon redemption, and idempotency keys.
- Order lookup filters by authenticated user for API order read/cancel.
- Broadcast auth is under Sanctum and channel authorization compares user UUID.
- `/api/payments/{sessionId}/status` is protected by Sanctum and only reveals user-owned payment events.
- Admin wallet adjustment is protected by `admin.permission:wallet.adjust`, writes wallet ledger entries, and records audit logs.
- Product upload validation rejects SVG and disguised non-image payloads.
- `coffee:security-check --production` checks critical production configuration.
- Admin payment event views are read-only and protected by `admin.permission:payment.view`.
- Payment retry requires `payment.retry`, only accepts failed server-owned pending records, re-verifies Stripe, and records retry audit data.
- API error responses for validation/auth/authorization/not-found use a consistent `status: error` shape.
- Order model observers append status history for user, admin, and system-driven changes.
- Telescope defaults off, uses owner-only admin access when enabled, and prunes records older than the configured retention window.
- Global middleware emits CSP, clickjacking, MIME-sniffing, referrer, and browser-permission headers; HSTS remains HTTPS-only.
- Realtime business notifications are queued after commit, use UUID-scoped private channels, and persist the same minimized payload used for broadcast delivery.
- Stripe async-success, failed, and expired checkout handling locks the server-owned payment session and ignores duplicate or invalid state changes.
- Reverb allowed origins are configured through `REVERB_ALLOWED_ORIGINS`, and the production security command rejects empty or wildcard origin lists.
