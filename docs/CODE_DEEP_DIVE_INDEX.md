# Coffee-Plus Code Deep Dive

## Purpose

This document set is a learning-oriented, code-grounded explanation of the backend.

It is meant for:

- understanding how a request enters the app
- understanding which file owns which part of the business logic
- understanding what each major function does, in execution order
- onboarding a new developer without forcing them to reverse-engineer the repo

## Scope

This guide covers the first-party executable backend code in:

- `app/`
- `routes/`
- `config/`
- `database/migrations/`
- `resources/views/`
- `app/View/Components/`

## How To Read This Guide

Read the documents in this order:

1. [01-entry-routes-config.md](code-deep-dive/01-entry-routes-config.md)
2. [02-web-and-auth-controllers.md](code-deep-dive/02-web-and-auth-controllers.md)
3. [03-api-and-admin-controllers.md](code-deep-dive/03-api-and-admin-controllers.md)
4. [04-services-domain-support.md](code-deep-dive/04-services-domain-support.md)
5. [05-models-requests-resources-lifecycle.md](code-deep-dive/05-models-requests-resources-lifecycle.md)
6. [06-commands-migrations.md](code-deep-dive/06-commands-migrations.md)
7. [07-views-layouts-components.md](code-deep-dive/07-views-layouts-components.md)
8. [08-page-runtime-flows.md](code-deep-dive/08-page-runtime-flows.md)

## Mental Model Of The Project

At a high level, the runtime flow is:

1. Laravel boots through `bootstrap/app.php`.
2. Route files decide which controller handles the request.
3. Form requests validate input when a route uses a request class.
4. Controllers stay thin and delegate work to services or models.
5. Services implement business rules:
   - pricing
   - cart behavior
   - checkout
   - wallet / Tangki ledger changes
   - payment initiation and payment completion
   - admin security and audit behavior
6. Models define relationships and domain helpers.
7. Events and listeners run post-order side effects.
8. Resources shape API responses for the Flutter client.
9. Migrations define how the database structure evolved.
10. Blade views and components turn backend state into the browser experience.

## Key Domain Concepts

- `User`: customer identity and ownership boundary
- `Admin`: separate admin guard and permission model
- `Menu` and `Product`: source of truth for what can be sold
- `CartItem`: pre-checkout user selections
- `CartSnapshot`: immutable checkout snapshot before Stripe redirect
- `Order` and `OrderItem`: committed purchase records
- `Transaction`: user-facing Tangki and order-related activity history
- `WalletLedger`: hard financial trail for balance changes
- `PaymentEvent`: server-owned payment/refill processing state
- `Coupon` and `CouponRedemption`: discount rules and one-time use tracking
- `Favorite`: saved recipe / collection behavior
- `SharedRecipe`: user-to-user recipe sharing
- `OrderStatusHistory`: immutable order state timeline
- `IdempotencyKey`: duplicate request guard for risky actions

## Most Important Runtime Flows

### 1. Dashboard / menu browsing

- Route enters web or API dashboard controller
- Controller reads `search` and `category`
- `DashboardMenuService` builds the menu query
- Cache stores category names
- Response is either a Blade view or JSON payload

### 2. Add to cart

- Request validation happens in `AddCartItemRequest`
- Controller delegates to `CartService`
- `CartService` normalizes add-ons, reprices from backend truth, merges legacy or matching rows, then saves cart state

### 3. Wallet-only checkout

- Request enters `OrderController` or API `OrderController`
- `CheckoutService` locks the user checkout path
- cart items are repriced from current product and add-on truth
- stock is deducted
- coupon is revalidated
- order and order items are created in a transaction
- `OrderPlaced` is emitted
- listeners debit balance, reward OZ, clear the cart, notify the user, and possibly reward the referrer

### 4. Stripe-backed checkout

- `PaymentController@checkout` creates a `CartSnapshot`
- Stripe checkout session is created by `StripeGateway`
- server writes a pending `PaymentEvent`
- Stripe calls the webhook later
- `StripeWebhookController` verifies signature, event type, ownership, amount, type, and currency
- a payment handler completes the order or refill

### 5. Tangki refill

- request goes through `InitiateRefillRequest`
- `RefillInitiationService` creates a Stripe session
- pending `PaymentEvent` is recorded
- webhook later routes the paid session to `RefillHandler`
- `RefillHandler` calls `TangkiService`
- `TangkiService` uses `LedgerService` to produce a durable balance change

### 6. Admin order operations

- admin routes require `auth:admin`
- sensitive actions require `admin.permission:*`
- controllers delegate state transitions to `OrderService` or payment retry services
- `AuditLogService` records who changed what
- `OrderObserver` writes immutable status history rows

## Quick Reading Map

If you want to understand a feature quickly, start here:

- menu browsing: `routes/web.php`, `routes/api.php`, `DashboardController`, `DashboardMenuService`
- product detail and reviews: `ProductController`, `ProductQueryService`, `ProductReviewController`
- cart: `CartController`, `API/CartController`, `CartService`, `CartPricingService`
- checkout: `OrderController`, `API/OrderController`, `CheckoutService`, `OrderService`
- Stripe checkout: `PaymentController`, `CartSnapshotService`, `StripeGateway`, `StripeWebhookController`
- refill: `TangkiController`, `API/TangkiController`, `RefillInitiationService`, `RefillHandler`, `TangkiService`, `LedgerService`
- favorites: `FavoriteController`, `API/FavoriteController`, `FavoriteService`
- admin operations: `routes/admin.php`, admin controllers, `AdminPermission`, `AuditLogService`
- security and prod safety: `SecurityHeaders`, `CoffeeSecurityCheck`, `TelescopeServiceProvider`, `config/security.php`
- page rendering and interaction: `resources/views/`, layout components, and the page runtime flow guide

## Deliverable Shape

Each module document follows the same pattern:

- file purpose
- where the file sits in the request path
- function-by-function explanation
- important caveats and trust boundaries

That is intentional: it lets you jump between files without relearning the notation.
