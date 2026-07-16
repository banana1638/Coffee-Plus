# AI Payment and Wallet Rules

## Absolute Rule

The backend may increase wallet/Tangki balance only after:

- verified payment callback, or
- trusted admin action with audit log, or
- explicit local development-only path that is disabled in production.

The backend must not expose a production API where the client sends amount and balance increases directly.

Manual admin wallet adjustments are allowed only through an authenticated admin route that:

- requires `wallet.adjust` permission,
- requires a reason,
- writes `wallet_ledger`,
- writes `audit_logs`,
- does not directly update wallet columns outside `LedgerService`.

## Safe Refill Flow

1. Backend creates pending refill/payment record.
2. Payment provider confirms payment.
3. Backend verifies callback/signature/status/amount/reference.
4. Backend credits wallet inside database transaction.
5. Backend writes ledger entry.
6. Backend marks payment completed.
7. Client fetches updated balance.
8. Backend may enqueue a refill success notification only after the ledger, balance, transaction, and `PaymentEvent` changes have committed.

## Safe Wallet Checkout Flow

1. Backend recalculates cart/order total.
2. Backend re-reads and validates current product, size, and add-on prices.
3. Backend validates coupon.
4. Backend checks wallet balance.
5. Backend debits wallet in transaction.
6. Backend creates order.
7. Backend writes ledger entry.
8. Backend returns order result.

## Detected Implementation Notes

- `/api/tangki/refill` creates a Stripe Checkout URL and does not directly credit balance.
- Payment initiation stores a pending `PaymentEvent` with the expected user, type, amount, and currency.
- `StripeWebhookController` validates Stripe signature and paid MYR checkout sessions.
- `RefillHandler` uses the Stripe paid amount and cross-checks metadata amount before crediting.
- `TangkiService::refillBalance` credits through `LedgerService` inside a transaction.
- `LedgerService` writes `WalletLedger` rows and stores before/after cents.
- Checkout uses `CheckoutService`, `CartSnapshotService`, `PricingService`, `Coupon::redeemForOrder`, and `OrderPlaced` event flow.
- `/api/payments/{sessionId}/status` lets the client poll server-side payment/refill status without trusting local client payment state.
- `POST /admin/wallet/adjust` records manual admin wallet credit/debit through ledger and audit logs.
- Admin payment retry is allowed only for failed server-owned pending records and must re-query Stripe before invoking idempotent payment handlers.
- Web and API refill initiation share RM5-RM500 and two-decimal-place validation.
- `LedgerService` rejects non-positive movements and production MySQL/PostgreSQL deployments enforce ledger balance invariants with a CHECK constraint.
- Stripe webhook and provider-verified admin retry both use `RealtimeNotificationService` after payment handling. A notification queue/broadcast failure is reported but cannot reverse or relabel a completed wallet movement.
- Refill success payloads may include the verified amount in cents, but the client must refresh `/api/tangki`; it must not mutate the displayed balance from notification data alone.
