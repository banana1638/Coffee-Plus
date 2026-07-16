# 03. API And Admin Controllers

## API Controllers

## `app/Http/Controllers/API/Auth/LoginController.php`

### Purpose

Issues Sanctum access tokens for the Flutter app.

### `login(Request $request)`

Execution flow:

1. validates credentials and optional device name
2. authenticates the user
3. uses `ApiTokenService` to issue a device-labeled token
4. returns token + user payload

### `logout(Request $request)`

- deletes or revokes the current access token

## `app/Http/Controllers/API/Auth/RegisterController.php`

### `register(Request $request)`

Execution flow:

1. validates registration fields
2. creates the user
3. issues a Sanctum token through `ApiTokenService`
4. returns auth success payload

## `app/Http/Controllers/API/DashboardController.php`

### Purpose

Supplies the Flutter home screen payload.

### `index(Request $request, DashboardMenuService $dashboardMenuService)`

Execution flow:

1. reads `search` and `category`
2. asks `DashboardMenuService` for active-only menus
3. caches all category names
4. returns:
   - menus as `CategoryResource`
   - category names
   - configurable options
   - query echo values
   - either authenticated user data or a guest placeholder block

## `app/Http/Controllers/API/ProductController.php`

### `show($id)`

- delegates to `ProductQueryService::detail`
- returns a product payload suitable for the app detail screen

## `app/Http/Controllers/API/ProductReviewController.php`

### `index(Product $product)`

- returns paginated reviews for a product

### `store(StoreProductReviewRequest $request, $order)`

- writes a review only when the request and order ownership rules allow it

## `app/Http/Controllers/API/CartController.php`

### Purpose

Owns mobile cart CRUD.

### `add(AddCartItemRequest $request)`

- validated add-to-cart write path
- delegates to `CartService`
- returns lightweight cart success data

### `index()`

- returns current cart content

### `update(UpdateCartItemRequest $request)`

- updates quantity by validated row identifier

### `destroy(RemoveCartItemRequest $request)`

- removes a row by validated identifier

## `app/Http/Controllers/API/CouponController.php`

### `validateCode(Request $request)`

Execution flow:

1. reads coupon code and subtotal context
2. checks coupon validity and usage rules
3. returns discount and validity info without trusting client-side discount math

## `app/Http/Controllers/API/FavoriteController.php`

### `index(Request $request)`

- returns paginated favorites for the authenticated user

### `store(StoreFavoriteRequest $request)`

- adds a favorite through `FavoriteService::add`
- handles duplicate favorite exceptions cleanly

### `destroy(int $id)`

- removes a favorite using owner-scoped service logic

### `perPage(Request $request)`

- clamps page size between safe bounds

## `app/Http/Controllers/API/OrderController.php`

### Purpose

Owns mobile checkout and order history.

### Constructor

Injects:

- `CheckoutServiceInterface`
- `OrderService`
- `IdempotencyService`

### `checkout(CheckoutRequest $request)`

Execution flow:

1. reads validated `use_oz`, `coupon_code`, `pickup_time`
2. extracts idempotency key
3. hashes the business payload
4. asks `IdempotencyService::run` to either:
   - replay a previous completed response
   - or run checkout once
5. callback calls `CheckoutService::processCheckout`
6. loads the completed order with relations
7. returns `OrderResource`

This is one of the most important trust-boundary methods in the app.

### `index()`

- returns paginated user-owned order history

### `show($order)`

- returns a single user-owned order

### `cancel($order)`

- sends cancellation to `OrderService`
- keeps ownership and refund logic server-side

## `app/Http/Controllers/API/PaymentStatusController.php`

### `__invoke(Request $request, string $sessionId)`

Purpose:

- lets the app poll server-side payment/refill completion
- avoids trusting local client payment success
- avoids leaking other users' payment state

## `app/Http/Controllers/API/ProfileController.php`

### `edit(Request $request)`

- returns user profile JSON

### `update(ProfileUpdateRequest $request)`

- updates profile fields

### `updatePassword(Request $request)`

- validates and updates password
- may rotate or reissue auth token behavior through `ApiTokenService`

### `destroy(Request $request)`

- deletes the authenticated account

### `notifications(Request $request)`

- returns current user notifications

### `markAsRead(Request $request, $id)`

- marks one notification as read

### `batchDeleteNotifications(Request $request)`

- deletes multiple notifications by id

### `deleteReadNotifications(Request $request)`

- bulk deletes already-read notifications

### `perPage(Request $request)`

- clamps pagination

## `app/Http/Controllers/API/SharedRecipeController.php`

### Purpose

Owns recipe sharing and import.

### `index(Request $request)`

- returns paginated shared recipes for the current user

### `store(StoreSharedRecipeRequest $request)`

- stores a shared recipe message / payload between users

### `import(Request $request, $id)`

- loads a shared recipe
- validates ownership and existence
- pushes the recipe back into the cart through `CartService`

### `perPage(Request $request)`

- clamps page size

## `app/Http/Controllers/API/StripeWebhookController.php`

### Purpose

This is the canonical payment completion path for Stripe.

### `__invoke(Request $request)`

Execution flow:

1. verifies Stripe signature
2. rejects unsupported or malformed events
3. extracts session and metadata
4. ignores unpaid or unsupported sessions when necessary
5. enters a database transaction
6. loads or creates the relevant pending `PaymentEvent`
7. verifies:
   - type
   - user
   - amount
   - currency
8. asks the payment gateway for normalized session data
9. asks `PaymentHandlerFactory` for the right handler
10. handler completes refill or checkout
11. marks the event processed

### Helper methods

- `shouldIgnoreSession(...)`: fast reject path
- `paymentEventQuery(...)`: locates related payment event rows
- `normalizePaymentType(...)`: maps metadata to known types
- `assertPendingPaymentMatches(...)`: enforces ownership/amount/currency trust boundaries
- `sessionAmount(...)`: extracts amount from provider payload

## `app/Http/Controllers/API/TangkiController.php`

### `index()`

- returns recent transaction summaries and user data

### `initiateRefill(InitiateRefillRequest $request)`

- delegates refill creation to `RefillInitiationService`
- returns session id + redirect URL
- does not credit balance directly

## `app/Http/Controllers/API/TokenController.php`

### Purpose

Owns API device-session management.

### `index(Request $request)`

- lists the current user's tokens / device sessions

### `destroy(Request $request, int $token)`

- revokes one token owned by the current user

### `destroyAll(Request $request)`

- revokes all tokens for the current user

## `app/Http/Controllers/API/TransactionController.php`

### `index(Request $request)`

- returns user-scoped transaction summaries

### `showOrderDetail(Request $request, string $bill_id)`

- returns the order behind a transaction id

### `refunds(Request $request)`

- returns paginated refund summaries for the user

## Admin Controllers

## `app/Http/Controllers/Admin/LoginController.php`

### Purpose

Owns admin login/logout.

### `showLoginForm()`

- renders admin login

### `login(Request $request)`

Execution flow:

1. validates credentials
2. applies admin throttle rules
3. authenticates with admin guard
4. branches into 2FA challenge when needed
5. records login-related audit data

### `logout(Request $request)`

- logs out admin guard
- clears session state

## `app/Http/Controllers/Admin/DashboardController.php`

### `index()`

- staff-focused dashboard
- if the admin can view reports, it redirects to owner analytics
- otherwise it loads pending orders with user and product context

### `ownerDashboard()`

Execution flow:

1. enforces report permission
2. calculates date windows
3. caches analytics for 60 seconds
4. computes:
   - total revenue
   - today's revenue
   - monthly revenue
   - total orders
   - total users
   - recent sales graph data
   - top products
5. returns the analytics view

## `app/Http/Controllers/Admin/ProductAdminController.php`

### Purpose

Owns admin product CRUD and add-on management.

### Main methods

- `index()`: lists products
- `create()`: shows create form
- `store()`: validates and creates a product, image, and add-ons
- `edit($id)`: loads edit form
- `update(Request $request, $id)`: updates product fields and add-ons
- `destroy($id)`: removes a product

### Important helpers

- `fillProductFromRequest(...)`: centralizes request-to-model mapping
- `syncAddons(...)`: keeps add-on rows aligned with submitted payload

## `app/Http/Controllers/Admin/CouponAdminController.php`

### Main methods

- `index()`: lists coupons
- `create()`: shows create screen
- `store()`: validates, creates, audits
- `edit(Coupon $coupon)`: loads edit screen
- `update(...)`: updates and audits
- `destroy(Coupon $coupon)`: deletes and audits

### Helpers

- `rules(...)`: shared validation rules
- `fillCoupon(...)`: maps normalized request data into the coupon model

## `app/Http/Controllers/Admin/AdminOrderController.php`

### Purpose

Owns admin order operations.

### Main methods

- `index()`: paginated admin order listing
- `show(Order $order)`: loads order with user, items, and status history
- `complete(Order $order)`: marks an order completed through `OrderService`
- `advanceStatus(Order $order)`: moves order to next allowed state
- `completeByPickupCode(Request $request)`: staff completes pickup using a code
- `exportPage()`: shows export center
- `refunds(Request $request)`: lists refund transactions
- `export(Request $request)`: downloads Excel export

All status-changing writes also record audit logs.

## `app/Http/Controllers/Admin/WalletAdminController.php`

### `adjust(Request $request)`

Execution flow:

1. validates target user, amount, direction, and reason
2. opens a DB transaction
3. delegates actual balance movement to `LedgerService`
4. writes an audit log

This method exists specifically to prevent direct, unaudited wallet edits.

## `app/Http/Controllers/Admin/PaymentEventAdminController.php`

### `index(Request $request)`

- lists payment events with filters for admin review

### `show(PaymentEvent $paymentEvent)`

- shows one payment event in detail

### `retry(Request $request, PaymentEvent $paymentEvent)`

- re-queries provider state through `PaymentRetryService`
- only works for safe retryable events

## `app/Http/Controllers/Admin/TwoFactorChallengeController.php`

### `show(Request $request)`

- shows the TOTP/recovery-code challenge page after password auth

### `store(Request $request)`

- verifies the second factor and completes login

### `pendingAdmin(Request $request)`

- loads the admin currently waiting in challenge state

## `app/Http/Controllers/Admin/TwoFactorController.php`

### `show(Request $request)`

- displays current 2FA status

### `confirm(Request $request)`

- confirms enabling TOTP

### `regenerateRecoveryCodes(Request $request)`

- replaces recovery codes

### `disable(Request $request)`

- disables 2FA
