# 02. Web And Auth Controllers

## Web Controllers

## `app/Http/Controllers/Controller.php`

Base Laravel controller. It currently carries no custom behavior.

## `app/Http/Controllers/DashboardController.php`

### Purpose

Renders the main customer dashboard page.

### `index(Request $request, DashboardMenuService $dashboardMenuService)`

Execution flow:

1. reads `search` and `category`
2. if the user selected `collections` and is authenticated:
   - fetches the current user's favorites
   - eager loads related products
   - optionally filters favorites by product name search
   - paginates the result
   - skips normal menu generation
3. otherwise:
   - asks `DashboardMenuService` for menus
   - leaves favorites empty
4. caches the complete category name list from `Menu`
5. returns `user.dashboard`

Why it matters:

- this controller merges two UX modes into one page:
  - normal menu browsing
  - saved recipes / collections browsing

## `app/Http/Controllers/CartController.php`

### Purpose

Owns browser-based cart actions.

### Constructor

Injects `CartServiceInterface`.

### `add(AddCartItemRequest $request)`

Execution flow:

1. validation already happened in `AddCartItemRequest`
2. forwards the authenticated user and normalized payload to `CartService::add`
3. asks the cart service for the updated cart count
4. returns JSON used by the page UI

### `index()`

Execution flow:

1. fetches the authenticated user's cart items through the service
2. returns `user.cart.index`

## `app/Http/Controllers/FavoriteController.php`

### Purpose

Owns browser favorite actions for the web UI.

### Constructor

Injects `FavoriteServiceInterface`.

### `toggle(Request $request)`

Execution flow:

1. validates the favorite payload inline
2. calls `FavoriteService::toggle`
3. returns whether the item was added or removed

### `check(Request $request)`

Execution flow:

1. short-circuits to `false` if no authenticated user exists
2. otherwise delegates to `FavoriteService::check`
3. returns JSON used by the frontend to mark favorite state

### `destroy(int $id)`

Execution flow:

1. delegates delete ownership enforcement to the favorite service
2. returns success JSON

## `app/Http/Controllers/NotificationController.php`

### Purpose

Owns browser notification actions.

### `markAllAsRead()`

- marks all current-user unread notifications as read
- usually used from the web navbar

### `markAsRead(int $id)`

- marks a single notification by id
- ownership comes from the current authenticated user's notification relation

### `destroy(int $id)`

- deletes a single notification from the user's collection

## `app/Http/Controllers/ProductController.php`

### Purpose

Renders a single product detail page for the web UI.

### Constructor

Injects `ProductQueryService`.

### `show(int $id)`

Execution flow:

1. asks `ProductQueryService` for a fully-loaded product
2. reads configurable product options from `config('coffee.options')`
3. returns `user.products.detail`

## `app/Http/Controllers/ProductReviewController.php`

### Purpose

Handles web product review submission.

### `store(StoreProductReviewRequest $request, Order $order)`

Execution flow:

1. validated request guarantees rating/comment shape
2. route-model-bound `Order` identifies the reviewed purchase
3. controller writes the review under purchase ownership rules

This is the browser counterpart to the API review write path.

## `app/Http/Controllers/OrderController.php`

### Purpose

Owns browser checkout and cancellation.

### Constructor

Injects:

- `CheckoutServiceInterface`
- `OrderService`

### `checkout(Request $request)`

Execution flow:

1. reads `use_oz` item ids from the form
2. sends the current user to `CheckoutService::processCheckout`
3. on success redirects to Tangki transactions with a success flash
4. on failure redirects back with the exception message

### `cancel(Order $order)`

Execution flow:

1. receives a route-model-bound order
2. asks `OrderService::cancel` to enforce state, ownership, refund, and stock restoration rules
3. redirects back to Tangki history

## `app/Http/Controllers/PaymentController.php`

### Purpose

Owns browser Stripe checkout initiation and return URL behavior.

### Constructor

Injects:

- `PaymentGatewayInterface`
- `PaymentHandlerFactory`
- `CartSnapshotService`
- `CheckoutServiceInterface`

### `checkout(Request $request)`

Execution flow:

1. loads the authenticated user's cart
2. reads `use_oz`, `coupon_code`, and `pickup_time`
3. rejects empty carts
4. builds an immutable `CartSnapshot`
5. converts only cash-paid snapshot items into Stripe line items
6. if the order has no cash component:
   - skips Stripe entirely
   - sends the order through normal `CheckoutService`
7. if a coupon reduced the total:
   - collapses the Stripe line items into one final-amount item
8. creates Stripe metadata including:
   - type
   - user id
   - snapshot id
   - coupon code
   - pickup time
   - OZ selections
9. creates a pending Stripe session through the gateway
10. records a pending `PaymentEvent`
11. redirects the browser away to Stripe

### `success(Request $request)`

Execution flow:

1. does **not** create the order
2. does **not** trust client redirect state
3. simply redirects the user back with a “payment received, being confirmed” flash

Why this matters:

- order creation for Stripe flows is webhook-driven, not redirect-driven

## `app/Http/Controllers/ProfileController.php`

### Purpose

Owns browser profile editing and account management.

### `edit(Request $request): View`

- loads the current user profile page

### `update(ProfileUpdateRequest $request): RedirectResponse`

- validates and updates name/email-like profile data

### `updatePassword(Request $request): RedirectResponse`

- validates password change input
- updates the logged-in user's password

### `destroy(Request $request): RedirectResponse`

- verifies destructive account deletion input
- deletes the browser user's account

## `app/Http/Controllers/TangkiController.php`

### Purpose

Owns browser Tangki balance page and refill initiation.

### Constructor

Injects `RefillInitiationService`.

### `index()`

- renders the Tangki page with current user context and recent activity

### `refill(InitiateRefillRequest $request)`

Execution flow:

1. validated request converts amount to cents
2. service creates the Stripe refill session
3. pending payment is recorded server-side
4. browser is redirected to Stripe

## `app/Http/Controllers/TransactionController.php`

### Purpose

Owns browser transaction history and browser order-detail drill-down.

### Constructor

Injects `TransactionQueryService`.

### `index(Request $request)`

Execution flow:

1. asks the query service for a user-scoped transaction query
2. applies optional search and type filters
3. eager loads related order items and products
4. paginates
5. returns `user.tangki.transactions`

### `showOrderDetail(Request $request, string $bill_id)`

Execution flow:

1. asks the query service for an order owned by the current user
2. eager loads items and reviews
3. returns `user.tangki.order-detail`

## Browser Auth Controllers

These are mostly Laravel Breeze-style adapters. They are important because they define the browser login lifecycle.

## `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

### `store(Request $request)`

- validates credentials
- logs in the browser user
- regenerates session

### `destroy(Request $request)`

- logs out the browser user
- invalidates and regenerates the session token

## `app/Http/Controllers/Auth/ConfirmablePasswordController.php`

- `show()`: shows password confirmation page
- `store()`: verifies the current password for sensitive browser flows

## `app/Http/Controllers/Auth/EmailVerificationNotificationController.php`

- `store()`: re-sends verification email

## `app/Http/Controllers/Auth/EmailVerificationPromptController.php`

- `__invoke()`: shows or skips the verification prompt based on state

## `app/Http/Controllers/Auth/NewPasswordController.php`

- `create()`: shows password reset form
- `store()`: resets password using Laravel's password broker

## `app/Http/Controllers/Auth/PasswordController.php`

- `update()`: updates password for an already-authenticated browser user

## `app/Http/Controllers/Auth/PasswordResetLinkController.php`

- `create()`: shows “forgot password” form
- `store()`: sends reset link

## `app/Http/Controllers/Auth/RegisteredUserController.php`

- `store()`: registers a browser user, usually followed by login/session bootstrap

## `app/Http/Controllers/Auth/VerifyEmailController.php`

- `__invoke()`: completes email verification from the signed URL
