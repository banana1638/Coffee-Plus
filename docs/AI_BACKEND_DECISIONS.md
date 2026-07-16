# AI Backend Decisions

### 2026-07-11 - Checkout-Time Pricing and Protected Diagnostics

Context:
- Cart prices could outlive catalog changes, Web validation was weaker than API validation, and local Telescope authorization could expose diagnostics when a development server was network-accessible.

Decision:
- Reprice and validate cart items from current backend product/option data before direct checkout or Stripe snapshot creation.
- Serve Telescope at `/admin/telescope` behind the admin guard and owner-only permission, with daily retention pruning.
- Apply global security headers with HSTS gated behind HTTPS configuration.
- Enforce refill/admin money bounds and positive, balanced wallet ledger movements.

Reason:
- Product price, payment amount, diagnostics, and wallet balances remain backend-controlled even when clients or environment settings are malformed.

Trade-offs:
- Pros:
  - Price changes take effect at checkout and stale cart values cannot determine payment.
  - Diagnostics remain usable without relying on a hidden URL.
  - Invalid ledger writes fail before balance mutation and at the production database boundary.
- Cons:
  - A user may see a changed total between adding to cart and checkout.
  - Telescope access is limited to owner/super_admin and requires the scheduler for automatic pruning.

Affected files:
- app/Services/CartPricingService.php
- app/Services/CheckoutService.php
- app/Services/CartSnapshotService.php
- app/Providers/TelescopeServiceProvider.php
- app/Http/Middleware/SecurityHeaders.php
- app/Services/LedgerService.php
- database/migrations/2026_07_11_000000_add_wallet_ledger_integrity_constraint.php

Validation:
- Targeted checkout, payment, admin, wallet, security-header, and configuration tests.

### 2026-07-07 - Lightweight Lists and Bounded Backend Reads

Context:
- Transaction, refund, Tangki, favorite, export, and owner dashboard paths can grow over time and increase response size, hydration cost, and memory use.

Decision:
- Return lightweight transaction summaries from `/api/tangki`, `/api/transactions`, and `/api/refunds`; keep full order detail on `/api/transactions/{bill_id}`.
- Paginate web dashboard saved recipes at 12 rows per page.
- Add chunk reading and field projection to order exports.
- Cache owner dashboard analytics for 60 seconds and aggregate money from cents columns.
- Select only required menu fields in admin product forms.

Reason:
- Reduce database transfer, Eloquent hydration, JSON response size, and repeated aggregate work without trusting client-provided money or changing backend-owned business rules.

Trade-offs:
- Pros:
  - Lower memory and faster list rendering on growing datasets.
  - Full detail remains available through existing detail endpoints.
- Cons:
  - Flutter must not rely on embedded `order_details` in list endpoints.
  - Owner analytics may be up to 60 seconds stale.

Affected files:
- app/Http/Controllers/API/TransactionController.php
- app/Http/Controllers/API/TangkiController.php
- app/Http/Resources/Api/TransactionResource.php
- app/Http/Controllers/DashboardController.php
- resources/views/user/favorites/list.blade.php
- app/Http/Controllers/Admin/ProductAdminController.php
- app/Http/Controllers/Admin/DashboardController.php
- app/Exports/OrdersExport.php
- docs/AI_API_PROVIDER_CONTRACT.md

Validation:
- Targeted tests: 24 passed, 179 assertions.
- `php artisan test`: 182 passed, 727 assertions.
- `php artisan route:list --except-vendor`: passed.
- `php artisan view:cache`: passed.
- `php artisan route:cache`: passed.
- `php artisan coffee:security-check`: passed.

### 2026-07-03 - Keep Dependency Refresh Within Existing Major Versions

Context:
- Laravel 13 and several package major releases are available, while the application is on Laravel 12 and PHP 8.3.

Decision:
- Update Composer and npm lock files within existing version constraints.
- Defer Laravel 13, Tinker 3, PHPUnit 12, Stripe 20, Tailwind 4, Vite 8, and other frontend major upgrades to dedicated migrations.
- Prefix API favorite route names with `api.` to make production route caching compatible without changing URLs.

Reason:
- Receive compatible fixes while keeping API, payment, auth, build, and runtime behavior stable.

Trade-offs:
- Pros:
  - Current dependency fixes and zero known advisories.
  - Full test, build, platform, package discovery, and cache validation completed.
- Cons:
  - Major-version features and breaking changes remain deferred.

Affected files:
- composer.lock
- package-lock.json
- .gitignore
- routes/api.php

Validation:
- `php artisan test` (181 tests, 716 assertions)
- `composer check-platform-reqs`
- `composer audit --locked`
- `npm audit --audit-level=high`
- `npm run build`
- `php artisan config:cache`
- `php artisan route:cache`

### 2026-06-30 - Bound High-Traffic Collection Queries

Context:
- Payment payload JSON, duplicate checkout eager loading, unbounded pending orders, and unbounded embedded reviews added avoidable query, transfer, or hydration cost.

Decision:
- Exclude payment payloads from the list projection while retaining them on detail.
- Reuse checkout eager-loaded relations.
- Paginate pending admin orders at 10 rows per page.
- Embed only the latest five product reviews while retaining full aggregate count and average; use the existing paginated reviews endpoint for complete history.

Reason:
- Reduce database transfer and PHP memory without changing money, authorization, payment, or order behavior.

Trade-offs:
- Pros:
  - Bounded memory and response work on growing tables.
  - Existing detail and full-review endpoints remain available.
- Cons:
  - Product detail embeds only five recent reviews.

Affected files:
- app/Http/Controllers/Admin/PaymentEventAdminController.php
- app/Http/Controllers/API/OrderController.php
- app/Http/Controllers/Admin/DashboardController.php
- app/Http/Controllers/API/ProductController.php
- app/Http/Controllers/ProductController.php
- app/Http/Resources/Api/ProductResource.php
- resources/views/admin/dashboard.blade.php
- resources/views/user/products/detail.blade.php

Validation:
- `php artisan test` (179 tests, 698 assertions)

Record meaningful backend decisions only.

## Template

### YYYY-MM-DD - Decision Title

Context:
- TODO

Decision:
- TODO

Reason:
- TODO

Trade-offs:
- Pros:
  - TODO
- Cons:
  - TODO

Affected files:
- TODO

Validation:
- TODO

### 2026-06-27 - Server-owned pending payment records

Context:
- Clients need a reliable payment status immediately after Stripe Session creation.

Decision:
- Payment gateways return a structured session ID and redirect URL. The backend stores expected user, type, amount, and currency before redirect and verifies all values in the signed webhook.

Reason:
- Payment success and amounts must remain server-owned and traceable throughout the flow.

Trade-offs:
- Pros:
  - Detects mismatched callbacks and enables immediate status polling.
- Cons:
  - Payment gateway implementations must return structured initiation data.

Affected files:
- app/Contracts/PaymentGatewayInterface.php
- app/Models/PaymentEvent.php
- app/Http/Controllers/API/StripeWebhookController.php

Validation:
- Payment status, refill, Stripe metadata, and webhook feature tests.

### 2026-06-27 - Explicit production CORS allowlist

Context:
- Browser API origins were not represented in repository configuration.

Decision:
- Configure origins through `CORS_ALLOWED_ORIGINS` and reject wildcard origins in production security checks.

Reason:
- Browser access must be explicit and deployment-verifiable.

Trade-offs:
- Pros:
  - Prevents accidental broad browser access.
- Cons:
  - Every production browser origin must be configured during deployment.

Affected files:
- config/cors.php
- app/Console/Commands/CoffeeSecurityCheck.php
- .env.example

Validation:
- CORS behavior and security command feature tests.

### 2026-06-29 - Provider-verified payment retry

Context:
- Failed server-owned payment records require controlled recovery without trusting stored client state.

Decision:
- Only owners may retry failed pending records. Retry re-fetches Stripe state, verifies expected user/type/amount/currency/session, and invokes existing idempotent handlers.

Reason:
- Recovery must never become a manual balance-credit path.

Trade-offs:
- Pros:
  - Recoverable payment processing with complete audit data.
- Cons:
  - Requires live Stripe availability during retry.

Affected files:
- app/Services/Payment/PaymentRetryService.php
- app/Http/Controllers/Admin/PaymentEventAdminController.php

Validation:
- tests/Feature/AdminPaymentEventTest.php

### 2026-06-29 - Administrator TOTP two-factor authentication

Context:
- Password-only admin sessions expose high-risk wallet, payment, and order actions.

Decision:
- Use OTPHP standard TOTP with a five-minute pending login challenge, encrypted secrets, hashed one-time recovery codes, rate limiting, and TOTP replay prevention.

Reason:
- Adds interoperable MFA without SMS/email trust or custom cryptographic implementation.

Trade-offs:
- Pros:
  - Compatible with common authenticator applications and works offline.
- Cons:
  - Administrators must securely retain recovery codes.

Affected files:
- app/Services/AdminTwoFactorService.php
- app/Http/Controllers/Admin/TwoFactorChallengeController.php
- app/Http/Controllers/Admin/TwoFactorController.php

Validation:
- tests/Feature/AdminTwoFactorTest.php

## Decisions

### 2026-06-24 - Backend-Owned Payment Status Polling

Context:
- The Flutter app needs to know whether Stripe checkout/refill completed without deciding payment success locally.

Decision:
- Add a Sanctum-protected `GET /api/payments/{sessionId}/status` endpoint.
- Return processed details only for the authenticated user's own `PaymentEvent`.
- Return `pending` when no user-owned event exists.

Reason:
- Webhooks are asynchronous; clients need a safe polling path.
- Returning `pending` avoids leaking whether another user's session exists.

Trade-offs:
- Pros:
  - Keeps payment truth server-side.
  - Handles webhook delay cleanly.
- Cons:
  - Client may need polling/backoff behavior.

Affected files:
- routes/api.php
- app/Http/Controllers/API/PaymentStatusController.php
- tests/Feature/PaymentStatusApiTest.php
- docs/AI_API_PROVIDER_CONTRACT.md

Validation:
- `php artisan test tests/Feature/PaymentStatusApiTest.php`

### 2026-06-24 - Audited Admin Wallet Adjustment

Context:
- Manual wallet corrections may be needed, but wallet balance is security-critical.

Decision:
- Add `POST /admin/wallet/adjust` protected by `admin.permission:wallet.adjust`.
- Use `LedgerService` for credit/debit.
- Use `AuditLogService` for admin audit trail.

Reason:
- Manual wallet changes must not bypass ledger, permissions, or audit logs.

Trade-offs:
- Pros:
  - Preserves ledger source of truth.
  - Limits access to owner/super_admin via existing wildcard permission.
- Cons:
  - No admin UI page was added yet; route is backend-only.

Affected files:
- routes/admin.php
- app/Http/Controllers/Admin/WalletAdminController.php
- app/Models/AuditLog.php
- tests/Feature/AdminWalletAdjustmentTest.php

Validation:
- `php artisan test tests/Feature/AdminWalletAdjustmentTest.php tests/Feature/WalletLedgerTest.php`

### 2026-06-24 - Production Security Check Command

Context:
- Production misconfiguration can expose debug output, broken payment verification, or unprotected tooling.

Decision:
- Add `php artisan coffee:security-check --production`.

Reason:
- A deterministic command is easier to run before deployment than manual config review.

Trade-offs:
- Pros:
  - Catches common production configuration failures.
- Cons:
  - Some warnings remain environment-specific and require human review.

Affected files:
- app/Console/Commands/CoffeeSecurityCheck.php
- tests/Feature/SecurityCheckCommandTest.php

Validation:
- `php artisan test tests/Feature/SecurityCheckCommandTest.php`

### 2026-06-24 - Historical Order Snapshot Fields

Context:
- Historical order details must remain stable after product names or coupon state change.

Decision:
- Add `orders.coupon_code`, `orders.discount_cents`, and `order_items.product_name`.
- Prefer stored `product_name` in API order item resources.

Reason:
- Flutter order history should display what the user bought at checkout time, not current catalog state.

Trade-offs:
- Pros:
  - Stable order history.
  - Better auditability for discounts.
- Cons:
  - Adds schema fields that require migration.

Affected files:
- database/migrations/2026_06_24_000000_add_order_snapshot_fields.php
- app/Services/CheckoutService.php
- app/Http/Resources/Api/OrderResource.php
- app/Http/Resources/Api/OrderItemResource.php

Validation:
- `php artisan test tests/Feature/CheckoutTest.php tests/Feature/ApiOrderTest.php tests/Feature/StripeMetadataTest.php`

### 2026-06-24 - API Error Contract

Context:
- Flutter needs stable error JSON for validation/auth/not-found handling.

Decision:
- Render API validation, authentication, authorization, and not-found errors with `status: error`.

Reason:
- Keeps HTTP status codes while making JSON parsing predictable.

Trade-offs:
- Pros:
  - More stable client error handling.
- Cons:
  - Flutter should verify existing error parser assumptions.

Affected files:
- bootstrap/app.php
- tests/Feature/ApiErrorContractTest.php

Validation:
- `php artisan test tests/Feature/ApiErrorContractTest.php tests/Feature/ApiCartSecurityTest.php tests/Feature/ApiOrderTest.php`

### 2026-06-24 - Read-Only Payment Event Audit

Context:
- Failed and ignored Stripe webhook events need admin visibility.

Decision:
- Add read-only admin payment event index/detail views.
- Do not add retry yet.

Reason:
- Visibility is safe and useful; retry needs a separate idempotency design.

Trade-offs:
- Pros:
  - Operators can inspect failed/ignored events.
- Cons:
  - Manual retry remains unavailable until designed.

Affected files:
- routes/admin.php
- app/Http/Controllers/Admin/PaymentEventAdminController.php
- resources/views/admin/payment-events/

Validation:
- `php artisan test tests/Feature/AdminPaymentEventTest.php`

### 2026-06-14 - Backend-Only AI Memory Boundary

Context:
- Coffee-Plus is the Laravel backend repository.
- Coffee-Plus-App is a separate Flutter client repository.

Decision:
- Keep Codex memory and skills in this repository backend-only.
- Do not create Flutter-specific skills here.

Reason:
- Backend owns money, wallet, order, coupon, auth, admin, payment, storage, broadcasting, and API contract truth.

Trade-offs:
- Pros:
  - Reduces accidental client-side trust.
  - Keeps backend tasks focused on source-of-truth behavior.
- Cons:
  - Flutter/client task memory must live in the separate app repository.

Affected files:
- AGENTS.md
- docs/AI_BACKEND_*.md
- docs/AI_API_PROVIDER_CONTRACT.md
- docs/AI_SECURITY_BASELINE.md
- docs/AI_PAYMENT_WALLET_RULES.md
- .codex/skills/backend-*/SKILL.md
- .codex/skills/laravel-backend-architect/SKILL.md
- .codex/skills/payment-wallet-guard/SKILL.md

Validation:
- Documentation and skill scaffold only; no runtime behavior changed.
### 2026-07-11 - Shared Domain Rules, Separate Interface Contracts

Context:
- Web and API controllers duplicated product option validation, refill initiation, transaction ownership filters, product detail loading, and review validation.

Decision:
- Move identical validation into shared Form Requests and identical domain/query behavior into focused services.
- Keep Web redirects/views and API JSON/resources in their respective controllers.
- Do not merge superficially similar requests when their accepted inputs differ, such as Favorite validation.

Reason:
- One source of truth reduces security and behavior drift without coupling interface-specific contracts.

Trade-offs:
- Pros: thinner controllers, fewer duplicated trust-boundary rules, easier focused testing.
- Cons: small service/request classes add indirection; intentionally different contracts still contain some similar code.

Affected files:
- app/Http/Requests/ProductOptionsRequest.php
- app/Http/Requests/InitiateRefillRequest.php
- app/Services/RefillInitiationService.php
- app/Services/TransactionQueryService.php
- app/Services/ProductQueryService.php
- app/Http/Controllers/API/
- app/Http/Controllers/

Validation:
- Targeted feature tests for cart, refill, transactions, products, reviews, recipes, and favorites.

### 2026-07-15 - Unified After-Commit Realtime Business Notifications

Context:
- Order, Stripe, and Tangki notifications had separate payloads, incomplete event coverage, and a database-ID versus UUID channel mismatch.
- Pickup reminders and payment failure notifications were absent, while payment and wallet state must remain independent from notification transport availability.

Decision:
- Use one `RealtimeNotificationService` and one queued `RealtimeBusinessNotification` for database and broadcast delivery.
- Use stable business event names and a shared payload envelope.
- Route order transitions through the existing observer/state machine, payment outcomes through verified webhook/retry flows, and pickup reminders through a locked idempotent scheduler command.
- Queue notification delivery after database commit and treat transport errors as reportable side-effect failures, never as payment, wallet, or order failures.

Reason:
- Centralizing event construction prevents Web/API/admin drift and keeps backend-owned business truth separate from Reverb availability.

Trade-offs:
- Database notification persistence also depends on the queue worker because database and broadcast channels share one queued notification job.
- Reverb provides foreground realtime delivery but does not guarantee background or terminated-device delivery; FCM/APNs remains a separate client/platform phase.

Affected files:
- app/Notifications/RealtimeBusinessNotification.php
- app/Services/RealtimeNotificationService.php
- app/Observers/OrderObserver.php
- app/Http/Controllers/API/StripeWebhookController.php
- app/Services/Payment/PaymentRetryService.php
- app/Console/Commands/SendPickupReminders.php
- routes/console.php

Validation:
- Realtime notification, Stripe webhook, admin payment retry, full feature suite, event registration, security check, Composer validation, lint, and diff checks.

### 2026-07-16 - Documentation Is a Versioned Runtime Deliverable

Context:
- The primary onboarding guide was tracked, but newer AI, deep-dive, and realtime documents were hidden by a repository-wide `/docs` ignore rule.
- `composer run dev` did not start Reverb or the scheduler, so a successful HTTP page did not prove the complete project was running.

Decision:
- Track the complete `docs/` directory.
- Add `docs/FULL_PROJECT_STARTUP.md` as the executable source for installation, all runtime processes, Stripe/Reverb testing, deployment, updates, and recovery.
- Preserve the lighter `composer run dev`, and add `dev:full` plus `dev:laragon` for explicit complete-stack startup modes.
- Configure Reverb allowed origins from environment and enforce restricted origins in the production security command.

Reason:
- Runtime documentation, process definitions, and security configuration must agree or onboarding creates false confidence.

Trade-offs:
- More documentation files become part of review and must be maintained with code changes.
- `dev:full` runs more processes and is intentionally heavier than the existing development script.

Affected files:
- composer.json
- .env.example
- .gitignore
- config/reverb.php
- app/Console/Commands/CoffeeSecurityCheck.php
- docs/

Validation:
- Composer validation, security command tests, full test suite, frontend build, command registration, Markdown reference checks, lint, Pint, and diff checks.
