# AI Backend Validation Checklist

Try safe commands:

composer validate
php artisan route:list
php artisan test
php artisan coffee:security-check --production
composer run dev:full

If database or dependencies are unavailable, record the limitation.

For security-sensitive changes, also validate:

- route middleware
- request validation
- ownership checks
- admin checks
- transaction boundaries
- API response shape

## Targeted Tests By Area

- Auth: `tests/Feature/Auth/`, `tests/Feature/AdminLoginThrottleTest.php`
- Admin permissions: `tests/Feature/AdminPermissionTest.php`, `tests/Feature/AdminSecurityTest.php`
- Cart: `tests/Feature/ApiCartSecurityTest.php`, `tests/Feature/CartOptionSignatureTest.php`
- Checkout/order: `tests/Feature/CheckoutTest.php`, `tests/Feature/CheckoutIdempotencyTest.php`, `tests/Feature/ApiOrderTest.php`
- Coupons: `tests/Feature/ApiCouponTest.php`, `tests/Feature/CouponAdminTest.php`
- Payment/refill: `tests/Feature/StripeWebhookTest.php`, `tests/Feature/StripeMetadataTest.php`, `tests/Feature/TangkiRefillApiTest.php`
- Wallet ledger/refunds: `tests/Feature/WalletLedgerTest.php`, `tests/Feature/ApiRefundTest.php`, `tests/Feature/RefundRecordsTest.php`
- Products/uploads: `tests/Feature/ProductAdminTest.php`
- Payment status: `tests/Feature/PaymentStatusApiTest.php`
- Admin wallet adjustment: `tests/Feature/AdminWalletAdjustmentTest.php`
- Environment/security command: `tests/Feature/SecurityCheckCommandTest.php`
- Realtime/scheduler: `tests/Feature/RealtimeNotificationTest.php`, `php artisan schedule:list`
- API provider contract: `tests/Feature/ApiProviderContractTest.php`
- API error contract: `tests/Feature/ApiErrorContractTest.php`
- Payment event admin audit: `tests/Feature/AdminPaymentEventTest.php`
- API device tokens: `tests/Feature/ApiTokenManagementTest.php`
- Admin TOTP 2FA: `tests/Feature/AdminTwoFactorTest.php`
- Order status history: `tests/Feature/OrderStateMachineTest.php`, `tests/Feature/CheckoutTest.php`

For documentation/startup changes also validate:

- `composer validate --no-check-publish`
- Composer script names and process list.
- Markdown links and referenced repository paths.
- `.env.example` contains placeholders only.
- README, onboarding, full startup, production, Flutter, and AI environment docs agree.
