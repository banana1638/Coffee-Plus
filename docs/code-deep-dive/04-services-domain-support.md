# 04. Services, Domain Logic, And Support Utilities

## Why This Layer Exists

Controllers in this project are intentionally thin.
The real business rules live in services.

This is where you should look for:

- pricing truth
- checkout truth
- wallet truth
- payment provider truth
- admin audit truth

## Core Services

## `app/Services/DashboardMenuService.php`

### Purpose

Builds dashboard menu/category results for both web and API.

### `menus(?string $search, string $category, bool $activeOnly = false, bool $cacheable = true): Collection`

- orchestrates the final menu list
- optionally limits products to active items
- optionally uses caching

### `query(...)`

- builds the base Eloquent query for menus + products

### `applyProductFilters(...)`

- applies search and active-state filtering to nested product queries

## `app/Services/ProductQueryService.php`

### `detail(int $productId): Product`

- loads one product
- eager loads add-ons
- eager loads latest reviews with user names
- includes review averages and counts

This is the single source of truth for rich product detail hydration.

## `app/Services/PricingService.php`

### Purpose

Calculates backend-authoritative pricing.

### `calculateUnitPrice(...)`

- returns float-form unit price

### `calculateUnitPriceCents(...)`

- returns cent-form unit price

Why cents matter:

- integers prevent float drift in money-critical flows

## `app/Services/CartPricingService.php`

### `refreshUnitPrice(CartItem $cartItem): int`

- ignores stored cart trust
- re-reads current product and add-on rules
- recalculates the item price in cents

This is one of the main “never trust stale cart data” protections.

## `app/Services/CartService.php`

### Purpose

Owns cart row creation, merge logic, quantity changes, and count.

### `add(...)`

Execution flow:

1. validates quantity
2. normalizes add-ons
3. reprices the product using backend truth
4. tries to find an existing cart row with the same product/options
5. falls back to legacy matching when signatures are absent
6. either increments an existing row or creates a new one

### `getCartItems(User $user)`

- returns the user's cart rows

### `updateQuantity(...)`

- finds target row by cart id or product id path
- validates quantity
- writes updated quantity

### `removeItem(...)`

- removes a row by owner-scoped lookup

### `getCartCount(User $user)`

- returns the cart item count used by UI counters

### `clearCart(User $user)`

- deletes all current cart rows for a user

### `assertValidQuantity(int $quantity)`

- central quantity constraint

### `findLegacyCartItem(...)`

- backward-compatibility path for rows created before `addons_signature`

## `app/Services/FavoriteService.php`

### Purpose

Owns favorite/collection logic.

### `getFavorites(User $user)`

- returns all favorites with product relation

### `toggle(...)`

- normalizes add-ons
- finds a matching favorite by structured signature
- falls back to legacy matching if needed
- deletes existing row or creates a new one

### `check(...)`

- returns whether the exact product/options combination exists

### `add(...)`

- throws if the same favorite already exists
- otherwise creates a new favorite row

### `delete(...)`

- owner-scoped delete by favorite id

### `findLegacyFavorite(...)`

- backward compatibility for older rows with no signature column

## `app/Services/CheckoutService.php`

### Purpose

Owns authoritative order creation.

### `processCheckout(...)`

This is the wallet / direct checkout path.

Execution flow:

1. acquires a cache lock for the user
2. loads cart items
3. rejects empty carts
4. opens a DB transaction
5. creates the base order shell
6. loops cart items:
   - deducts stock
   - reprices current item
   - writes order item snapshot data
   - separates OZ-paid and cash-paid math
   - accumulates reward OZ for cash-paid items
7. validates and redeems coupon if supplied
8. writes order subtotal, final amount, discount, OZ used, pickup time
9. emits `OrderPlaced`
10. releases the lock

### `generatePickupCode()`

- creates a unique human-usable pickup code

### `processCartSnapshot(...)`

This is the Stripe completion path.

Execution flow:

1. verifies snapshot ownership
2. locks the snapshot row
3. prevents re-processing
4. rejects expired snapshots
5. creates an order from immutable snapshot data
6. writes order items from stored snapshot JSON
7. redeems coupon if still valid
8. marks snapshot processed
9. emits `OrderPlaced`

### `deductStock(...)`

- enforces stock deduction atomically
- throws when inventory is insufficient

## `app/Services/CartSnapshotService.php`

### Purpose

Freezes a cart before external payment redirect.

### `createFromCart(...)`

Execution flow:

1. loads current cart
2. reprices every item
3. decides which items are OZ-paid
4. computes subtotal, final amount, discount, and pickup data
5. writes a durable `CartSnapshot`

Why it matters:

- Stripe redirect introduces time gap
- the snapshot prevents later product edits from silently mutating the pending payment intent

## `app/Services/OrderService.php`

### Purpose

Owns order state transitions after initial creation.

### `cancel(Order $order, User $user)`

Execution flow:

1. validates ownership and cancellable state
2. restores stock
3. reverses or adjusts financial state where needed
4. writes transactions / ledger effects
5. moves the order state

### `complete(Order $order)`

- finalizes an order as completed
- writes state changes in a transaction

### `completeByPickupCode(string $pickupCode)`

- looks up the order by pickup code
- completes it for staff workflows

### `advanceStatus(Order $order)`

- moves order to the next valid step

### helpers

- `calculateCashRewardOz(...)`: reward math helper
- `reverseReferralReward(...)`: rollback helper when cancellation invalidates referral reward
- `recordTransaction(...)`: writes transaction history rows
- `restoreStock(...)`: returns inventory to products

## `app/Services/OrderStateMachine.php`

### `transition(Order $order, string $targetStatus)`

- central state validation gate
- prevents invalid jumps like skipping required states

## `app/Services/TransactionQueryService.php`

### Purpose

Centralizes reusable transaction and order-history queries.

### `forUser(...)`

- returns a query builder for user-scoped transactions

### `refundsForUser(...)`

- returns refund-only query

### `orderForUser(...)`

- resolves one order behind a bill id while enforcing ownership

### helpers

- `applyFilters(...)`
- `applySearch(...)`

## Wallet / Tangki Services

## `app/Services/LedgerService.php`

### Purpose

This is the financial write gate for wallet balance changes.

### `credit(...)`

Execution flow:

1. validates positive amount
2. opens a DB transaction
3. locks the user row
4. checks idempotency by source + idempotency key
5. computes before/after balances
6. updates user balance
7. writes a `WalletLedger` row

### `debit(...)`

Same pattern as credit, but enforces sufficient balance and subtracts value.

### `balanceCents(User $user)`

- returns integer balance

### `assertPositiveAmount(...)`

- shared amount invariant

## `app/Services/TangkiService.php`

### Purpose

High-level wallet behavior built on `LedgerService`.

### `refillBalance(...)`

- converts refill amount to cents and OZ reward value
- uses `LedgerService::credit`
- writes user-facing refill effects

### `drainOz(...)`

- debits balance for OZ redemption flows

### `deductBalanceAndRewardOz(...)`

- debits cash
- also awards OZ in one coordinated flow

## Payment Services

## `app/Services/RefillInitiationService.php`

### `initiate(User $user, int $amountCents)`

Execution flow:

1. enforces RM5-RM500 limits
2. builds Stripe metadata
3. creates a one-line-item Stripe session
4. records a pending `PaymentEvent`
5. returns a `PaymentInitiation` DTO

## `app/Services/Payment/Gateways/StripeGateway.php`

### `createCheckout(...)`

- builds Stripe checkout session from normalized items and metadata

### `getSessionData(string $sessionId)`

- re-queries Stripe for authoritative payment status
- returns a normalized `PaymentResult`

## `app/Services/Payment/PaymentHandlerFactory.php`

### Purpose

Maps payment types like `refill` or `checkout` to completion handlers.

### `registerHandler(...)`

- lets code register or override a type mapping

### `make(string $type)`

- resolves a concrete handler from the container

## `app/Services/Payment/RefillHandler.php`

### `handle(PaymentResult $result, User $user)`

- validates provider result
- credits Tangki through `TangkiService`

## `app/Services/Payment/StripeCheckoutHandler.php`

### `handle(PaymentResult $result, User $user)`

- resolves the related cart snapshot
- completes the order through checkout service snapshot path

## `app/Services/Payment/PaymentRetryService.php`

### `retry(PaymentEvent $paymentEvent)`

Execution flow:

1. locks the payment event
2. checks retry eligibility
3. re-queries provider state
4. if safe, replays the relevant completion handler
5. updates retry counters / timestamps / status

## Realtime Notification Service

## `app/Services/RealtimeNotificationService.php`

This service is the single backend entry point for user-facing realtime business notifications.

It converts trusted backend state changes into a stable notification payload without allowing the client to decide whether an order, payment, or refill succeeded.

### Order methods

- `orderAccepted(...)`: emits `order.accepted` after order placement
- `orderStatusChanged(...)`: maps preparing, ready, completed, and cancelled states to typed events
- `pickupReminder(...)`: emits the one-time `order.pickup_reminder` event

### Payment methods

- `paymentProcessed(...)`: emits checkout success or Tangki refill success after provider verification
- `paymentFailed(...)`: emits checkout or Tangki failure without changing money state

### Delivery behavior

- delegates storage and broadcasting to `RealtimeBusinessNotification`
- catches notification transport failures so a Reverb outage cannot roll back a completed financial operation
- includes navigation hints and public identifiers, but no secrets or trusted client-side success flags
- requires clients to re-fetch order, payment, or wallet state from the API after receiving an event

## Security And Auth Services

## `app/Services/AdminTwoFactorService.php`

- `createTotp(...)`: constructs a TOTP instance for an admin
- `verifySecret(...)`: verifies a code against a raw secret
- `verify(...)`: top-level admin 2FA verification entry
- `verifyTotp(...)`: TOTP-specific path
- `generateRecoveryCodes()`: makes one-time backup codes
- `hashRecoveryCodes(...)`: stores recovery codes safely

## `app/Services/ApiTokenService.php`

- `issue(...)`: issues a Sanctum token with device labeling

## `app/Services/AuditLogService.php`

- `record(...)`: writes a normalized audit log row describing actor, action, target, before, and after

## File / Media Services

## `app/Services/ProductImageService.php`

### `store(UploadedFile $image)`

- validates uploaded file
- stores original image
- generates thumbnail and detail variants

### `backfill(Product $product)`

- regenerates variants for existing product images

### `delete(...)`

- removes stored image files

### helpers

- `createVariant(...)`: actual resize/encode work
- `validateImage(...)`: MIME, extension, size, and decodability guard

## Support Utilities

## `app/Support/AddonsSignature.php`

- `normalize(array $addons)`: canonicalizes addon arrays
- `from(array $addons)`: builds a stable signature string

Used to compare favorites and cart items safely.

## `app/Support/Money.php`

- `toCents(...)`: canonical integer money conversion
- `fromCents(...)`: reverse conversion for display/storage helpers

## `app/Support/ProductAddonSelection.php`

- `normalize(...)`: canonicalize addon request payload
- `belongsToProduct(...)`: verify selected add-ons belong to the product

## DTOs And Contracts

## `app/DataTransferObjects/PaymentInitiation.php`

- value object returned when a Stripe session is created
- carries session id and redirect URL

## `app/DataTransferObjects/PaymentResult.php`

- normalized provider response
- `isSuccess()`: convenience success check
- `getType()`: returns normalized payment type

## `app/Contracts/*`

These files define the seam between controllers/services and concrete implementations:

- `CartServiceInterface`
- `CheckoutServiceInterface`
- `FavoriteServiceInterface`
- `PaymentCompletionHandler`
- `PaymentGatewayInterface`
- `PricingServiceInterface`
- `TangkiServiceInterface`

Their value is architectural:

- they make the service layer swappable
- `AppServiceProvider` binds them to concrete classes

## `app/Traits/ApiResponse.php`

- `success(...)`: standard success JSON wrapper
- `error(...)`: standard error JSON wrapper
