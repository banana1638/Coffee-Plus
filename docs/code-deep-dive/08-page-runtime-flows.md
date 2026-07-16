# 08. Page Runtime Flows

## Purpose

This file explains how major browser pages run end to end.

The earlier documents explained individual files.
This document switches perspective and explains full request/response chains:

- which route is hit
- which controller method runs
- which services/models matter
- which Blade view renders
- which browser-side actions happen next

Use this file when you want to answer:

- "What happens when the user opens this page?"
- "What happens when they click this button?"
- "Which backend files do I read for this screen?"

## Flow 1: Customer Dashboard And Menu Browsing

### Entry point

- `GET /` or dashboard route in `routes/web.php`

### Main backend path

1. route resolves to `DashboardController@index`
2. controller reads:
   - `search`
   - `category`
3. if `category !== 'collections'`:
   - controller asks `DashboardMenuService` for menu groups
4. controller fetches category names from `Menu`
5. controller returns `user.dashboard`

### View path

1. `user.dashboard` renders inside `<x-app-layout>`
2. `AppLayout` resolves `layouts.app`
3. `layouts.app` includes `layouts.navigation`
4. `user.dashboard` renders:
   - top welcome/balance area
   - search form
   - category tabs
5. it conditionally includes:
   - `user.products.index` for normal browsing
   - `user.favorites.list` for collections mode

### What to read when debugging

- `routes/web.php`
- `app/Http/Controllers/DashboardController.php`
- `app/Services/DashboardMenuService.php`
- `resources/views/user/dashboard.blade.php`
- `resources/views/user/products/index.blade.php`
- `resources/views/user/favorites/list.blade.php`

## Flow 2: Collections / Saved Recipes Mode

### Entry point

- dashboard route with `category=collections`

### Main backend path

1. request still hits `DashboardController@index`
2. controller checks authentication
3. controller queries the current user's favorites
4. controller eager-loads related products
5. controller optionally filters by search term
6. controller paginates favorites
7. controller returns `user.dashboard` with `favorites`

### View path

1. `user.dashboard` sees `$category === 'collections'`
2. it includes `user.favorites.list`
3. the partial renders saved recipe cards
4. each card can:
   - open product detail again
   - add the saved recipe to cart

### Browser action after render

The favorites list can send an add-to-cart request directly with `fetch`.
That means the user does not have to reopen the product page to reorder a saved recipe.

### What to read when debugging

- `DashboardController@index`
- `FavoriteService`
- `resources/views/user/favorites/list.blade.php`
- `CartController@add`
- `CartService`

## Flow 3: Product Detail Page

### Entry point

- product detail route in `routes/web.php`

### Main backend path

1. route resolves to `ProductController@show`
2. controller receives product id
3. controller asks `ProductQueryService` for a fully-loaded product
4. service loads:
   - base product data
   - add-ons
   - reviews / counts / averages
5. controller reads option configuration from `config('coffee.options')`
6. controller returns `user.products.detail`

### View path

1. page renders inside `x-app-layout`
2. form targets `route('cart.add')`
3. page prints current product data
4. page prints option groups:
   - temperature
   - size
   - add-ons
5. page prints review summary
6. page includes `components.order-modals`

### Browser-side actions on this page

#### Live price preview

- quantity change
- size change
- add-on change

All trigger `updatePreviewPrice()`.

#### Favorite state sync

Page load and option changes trigger `checkFavoriteStatus()`.
That sends a request to `route('favorites.check')`.

#### Favorite toggle

Clicking the heart button triggers `toggleFavorite()`.
That posts the current recipe shape to `route('favorites.toggle')`.

#### Add to cart

Submitting the form does not immediately submit the native form.
Instead:

1. the page opens `confirmModal`
2. user confirms
3. `executeSubmit()` sends `FormData` to `route('cart.add')`
4. success updates the navbar cart badge
5. success modal is shown

### Trust boundary

The displayed price is informational.
Real validation and repricing happen later in:

- `AddCartItemRequest`
- `CartService`
- pricing helpers

### What to read when debugging

- `routes/web.php`
- `ProductController@show`
- `ProductQueryService`
- `FavoriteController`
- `CartController@add`
- `CartService`
- `resources/views/user/products/detail.blade.php`
- `resources/views/components/order-modals.blade.php`

## Flow 4: Add To Cart Request

### Entry point

- `POST` add-to-cart route from product detail or favorites list

### Main backend path

1. request hits `CartController@add`
2. `AddCartItemRequest` validates payload
3. controller delegates to `CartService::add`
4. service:
   - validates product-option combination
   - normalizes add-ons
   - recalculates price from backend truth
   - merges matching cart rows when appropriate
   - saves the cart
5. controller returns JSON with updated cart count

### Browser result

- product detail updates the top cart badge
- favorites list can also use the returned success state

### What to read when debugging

- `app/Http/Requests/AddCartItemRequest.php`
- `app/Http/Controllers/CartController.php`
- `app/Services/CartService.php`
- `app/Services/PricingService.php`

## Flow 5: Cart Page And Balance Checkout

### Entry point

- `GET` cart route

### Main backend path

1. route resolves to `CartController@index`
2. controller asks cart service for current-user cart rows
3. controller returns `user.cart.index`

### View path

Page renders:

- product rows
- OZ redemption checkboxes
- user Tangki and cash balance summary
- direct balance checkout button
- Stripe checkout button

### Browser-side calculations

`updateCalculations()` recomputes:

- remaining cash amount
- OZ that will be deducted
- insufficient balance state
- checkbox disable state when OZ is not enough

### Balance checkout submit path

1. form submits to `route('order.checkout')`
2. `OrderController@checkout` reads `use_oz[]`
3. controller calls `CheckoutService::processCheckout`
4. service:
   - locks the checkout path
   - reprices cart items
   - validates coupon if relevant
   - checks stock
   - checks wallet/balance
   - creates order and order items
   - emits `OrderPlaced`
5. controller redirects to Tangki transactions/order area with flash state

### What to read when debugging

- `CartController@index`
- `resources/views/user/cart/index.blade.php`
- `OrderController@checkout`
- `CheckoutService`
- `CartPricingService`
- `OrderService`
- order listeners

## Flow 6: Cart Page To Stripe Checkout

### Entry point

- same cart page, but user clicks "Pay with Stripe"

### Main backend path

1. browser posts same cart form to `route('stripe.checkout')`
2. request hits `PaymentController@checkout`
3. controller loads user cart
4. controller reads:
   - `use_oz`
   - `coupon_code`
   - `pickup_time`
5. controller creates a `CartSnapshot`
6. controller determines how much remains payable in cash
7. if cash payable is zero:
   - falls back to normal checkout service
8. otherwise:
   - builds Stripe line items
   - creates pending session metadata
   - asks payment gateway to create Stripe session
   - records pending `PaymentEvent`
   - redirects browser to Stripe

### Trust boundary

Redirecting to Stripe does not create a completed order.
Completion is still webhook-driven.

### What to read when debugging

- `PaymentController@checkout`
- `CartSnapshotService`
- `app/Services/Payment/StripeGateway.php`
- `PaymentEvent`

## Flow 7: Stripe Success Redirect

### Entry point

- Stripe redirects browser back to success URL

### Main backend path

1. request hits `PaymentController@success`
2. controller does not trust the redirect as proof of payment
3. controller only shows a "received / waiting confirmation" style state

### Why this exists

Users expect to come back somewhere after Stripe.
But the backend correctly treats webhook confirmation as the real source of truth.

### What to read when debugging

- `PaymentController@success`
- `StripeWebhookController`
- payment handlers

## Flow 8: Tangki Page And Refill Initiation

### Entry point

- `GET` Tangki page
- `POST` refill form

### Read page path

1. route hits `TangkiController@index`
2. controller loads current user context and recent transactions
3. returns `user.tangki.index`

### Refill submit path

1. user clicks preset or submits custom amount
2. browser posts to `route('tangki.refill')`
3. request hits `TangkiController@refill`
4. `InitiateRefillRequest` validates amount
5. controller calls `RefillInitiationService`
6. service creates Stripe checkout session
7. server records pending `PaymentEvent`
8. browser is redirected to Stripe

### Trust boundary

The page copy and the backend both enforce the same rule:
refill does not credit balance immediately.

### What to read when debugging

- `TangkiController`
- `InitiateRefillRequest`
- `RefillInitiationService`
- `PaymentEvent`
- `resources/views/user/tangki/index.blade.php`

## Flow 9: Tangki Transaction List

### Entry point

- `GET` Tangki transactions page with optional filters

### Main backend path

1. request hits `TransactionController@index`
2. controller delegates query building to `TransactionQueryService`
3. service scopes transactions to current user
4. controller applies optional filter/search behavior
5. related order/product data is eager loaded
6. results are paginated
7. controller returns `user.tangki.transactions`

### View role

The view mostly lists rows and provides links to detail pages.

### What to read when debugging

- `TransactionController@index`
- `TransactionQueryService`
- `resources/views/user/tangki/transactions.blade.php`

## Flow 10: Tangki Order Detail / Pickup Ticket

### Entry point

- detail route containing `bill_id`

### Main backend path

1. request hits `TransactionController@showOrderDetail`
2. controller asks `TransactionQueryService` for a user-owned order
3. service/query scopes by ownership
4. controller eager-loads:
   - items
   - product relations
   - reviews
5. controller returns `user.tangki.order-detail`

### View behavior

The page shows:

- order state
- items and options
- review forms for completed items
- pickup QR and code
- cancel button when allowed

### Follow-up actions from this page

- review form posts to `ProductReviewController@store`
- cancel form posts to `OrderController@cancel`

### What to read when debugging

- `TransactionController@showOrderDetail`
- `TransactionQueryService`
- `OrderController@cancel`
- `ProductReviewController@store`
- `resources/views/user/tangki/order-detail.blade.php`

## Flow 11: Browser Login/Register Modal

### Entry point

- guest clicks Login or Register in navbar or dashboard

### View path

1. `layouts.app` exposes Alpine `authModal` state
2. guest pages include `components.auth-modals`
3. modal component switches tabs between login/register

### Browser-side submit path

#### Login

1. modal calls `submitLogin()`
2. `fetch` posts JSON to `route('login')`
3. browser expects JSON response
4. on success page reloads
5. on failure validation errors are shown inline

#### Register

1. modal calls `submitRegister()`
2. `fetch` posts JSON to `route('register')`
3. on success page reloads
4. on failure validation errors are shown inline

### Why it matters

This is a modal UX layered on top of standard Laravel auth routes.
If auth responses or middleware behavior change, this component may break even if the routes still work for normal form posts.

### What to read when debugging

- `resources/views/components/auth-modals.blade.php`
- browser auth controllers under `app/Http/Controllers/Auth/`
- `routes/auth.php`

## Flow 12: Admin Login And Admin Navigation

### Entry point

- `/admin/login`
- later, any admin page

### Main backend path

1. admin login routes point to admin auth controllers
2. successful login uses the `admin` guard
3. protected admin routes require `auth:admin`
4. sensitive routes also require `admin.permission:*`

### View path

1. admin pages use `<x-admin-layout>`
2. layout resolves `layouts.admin`
3. layout includes `layouts.admin-navigation`
4. sidebar shows only actions allowed by current admin permissions

### What to read when debugging

- `routes/admin.php`
- admin auth controllers
- `AdminPermission` middleware
- `resources/views/admin/login.blade.php`
- `resources/views/layouts/admin-navigation.blade.php`

## Flow 13: Admin Preparation Dashboard

### Entry point

- admin dashboard route

### Main backend path

1. route resolves to admin dashboard controller
2. controller fetches pending orders and related user/items
3. controller returns `admin.dashboard`

### View behavior

Page renders:

- operational summary cards
- pending/preparing queue
- "mark completed" action for permitted admins

### Action follow-up

When a permitted admin clicks the completion button:

1. browser submits to the admin order completion route
2. admin order service/controller advances the order state
3. observers/history tracking record the transition

### What to read when debugging

- `app/Http/Controllers/Admin/DashboardController.php`
- `resources/views/admin/dashboard.blade.php`
- `OrderService`
- `OrderObserver`

## Flow 14: Admin Order Operations

### Entry point

- admin orders index route

### Main backend path

1. route resolves to `AdminOrderController@index`
2. controller loads paginated orders plus related data
3. controller returns `admin.orders.index`

### View behavior

This page exposes several operations:

- inspect one order
- verify pickup code
- advance order status
- open refund list
- open export center

### Important action chains

#### Pickup code verification

1. admin submits code form
2. request hits `complete-by-code` action
3. backend finds matching eligible order
4. backend applies the transition if allowed

#### Advance status

1. admin submits row action
2. request hits `advance-status`
3. backend asks state machine/order service for next valid transition
4. observer/history logging persists audit trail

### What to read when debugging

- `routes/admin.php`
- `AdminOrderController`
- `OrderService`
- `OrderStateMachine`
- `resources/views/admin/orders/index.blade.php`
- `resources/views/admin/orders/show.blade.php`

## Flow 15: Admin Product Management

### Entry point

- admin products index/create/edit routes

### Main backend path

1. admin controller loads product/menu data
2. create/update requests validate input
3. image upload flows go through `ProductImageService`
4. persistence writes product and add-on data
5. controller returns list page or redirects

### View behavior

The create/edit pages help the admin build add-on rows dynamically in the browser before submit.

### What to read when debugging

- `Admin\ProductAdminController`
- `ProductImageService`
- admin product views

## Flow 16: Admin Coupon Management

### Entry point

- admin coupon routes

### Main backend path

1. coupon controller loads current coupon data
2. create/edit actions validate payload
3. controller writes coupon state
4. index page lists results

### View behavior

The shared partial form keeps coupon-field structure consistent between create and edit screens.

### What to read when debugging

- `Admin\CouponAdminController`
- `Coupon` model
- coupon views

## Flow 17: Admin Payment Event Inspection And Retry

### Entry point

- admin payment event routes

### Main backend path

1. admin controller lists or loads payment events
2. detail page shows stored payment metadata and processing state
3. retry action, when present and authorized, re-verifies with provider before retrying handler logic

### Why it matters

This is the operational window into webhook/payment issues.
If a refill or Stripe-backed order behaves unexpectedly, these pages are part of the first debugging path.

### What to read when debugging

- `Admin\PaymentEventAdminController`
- `PaymentEvent`
- payment retry services/handlers
- admin payment event views

## Flow 18: Realtime Business Notifications And Pickup Reminders

### Order path

1. successful order placement dispatches `OrderPlaced`
2. `SendOrderNotification` delegates `order.accepted` to `RealtimeNotificationService`
3. later status changes are detected by `OrderObserver`
4. preparing, ready, completed, and cancelled states become typed realtime events
5. `RealtimeBusinessNotification` queues the same payload for database storage and Reverb broadcasting after commit

### Payment and Tangki path

1. Stripe webhook or an authorized retry re-verifies provider state
2. the relevant payment handler completes the order or Tangki mutation transactionally
3. `RealtimeNotificationService` queues a success or failure notification
4. the client treats the event as a refresh hint and re-fetches trusted API state

### Pickup reminder path

1. the scheduler runs `orders:send-pickup-reminders` every minute
2. the command locks each eligible ready order
3. it writes `pickup_reminder_sent_at` once
4. it queues `order.pickup_reminder` on the user's UUID private channel

### What to read when debugging

- `routes/console.php`
- `SendPickupReminders`
- `RealtimeNotificationService`
- `RealtimeBusinessNotification`
- `OrderObserver`
- `User::receivesBroadcastNotificationsOn()`
- `routes/channels.php`

## Final Mental Model

When you trace any web page in Coffee-Plus, use this order:

1. find the route
2. find the controller method
3. find any request validation class
4. find the service or model doing the real work
5. return to the Blade view
6. check whether the Blade file also contains JavaScript/fetch behavior
7. identify which next request that browser-side behavior triggers

That method keeps the project understandable even when one visible user action spans:

- a page render
- a modal or fetch call
- a later checkout/payment callback
- a follow-up detail page
