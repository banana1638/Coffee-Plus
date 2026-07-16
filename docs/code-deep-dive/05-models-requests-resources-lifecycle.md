# 05. Models, Requests, Resources, Middleware, Observers, Notifications

## Models

## `app/Models/User.php`

### Purpose

The main customer identity model.

### Important behavior

- custom casts
- UUID route key usage
- encrypted/normalized phone and address accessors
- balance accessors
- inviter/referral helpers
- lifecycle hook during creation

### Important methods

- `casts()`: defines type coercion
- `getRouteKeyName()`: uses non-default route binding key
- `phone()` and `address()`: transform sensitive data in and out
- `tangkiBalance()`: balance accessor wrapper
- `getPendingPlaintext()` / `clearPendingPlaintext()`: helper state around encrypted data migration
- `findByPhone(...)`: lookup helper
- `booted()`: sets creation-time defaults

### Relations

- `orders()`
- `transactions()`
- `friends()`
- `cartItems()`
- `favorites()`
- `productReviews()`

## `app/Models/Admin.php`

- `isOwner()`: owner role check
- `isStaff()`: staff role check
- `canPerform(...)`: permission gate helper
- `hasTwoFactorAuthentication()`: 2FA enablement state

## `app/Models/Menu.php`

- `products()`: one-to-many relation for dashboard grouping

## `app/Models/Product.php`

### Important methods

- `price()`: money accessor
- `getImageUrlAttribute()`: public image URL
- `getThumbnailImageUrlAttribute()`: thumbnail URL
- `getDetailImageUrlAttribute()`: detail image URL
- `originalImageUrl()`: internal helper
- `menu()`: owning menu
- `addons()`: add-on rows
- `reviews()`: product reviews
- `getAverageRatingAttribute()`
- `getReviewsCountAttribute()`

## `app/Models/ProductAddon.php`

- `price()`: cents-aware price accessor
- `product()`: parent product

## `app/Models/CartItem.php`

- `product()`: relation
- `unitPrice()`: accessor
- `getRequiredOzAttribute()`: derived OZ cost

## `app/Models/Favorite.php`

- `user()`
- `product()`

## `app/Models/SharedRecipe.php`

- `sender()`
- `recipient()`
- `product()`

## `app/Models/Order.php`

### Important methods

- `subtotal()`: money accessor
- `finalAmount()`: money accessor
- `canBeCancelled()`
- `nextStatus()`
- `canAdvanceStatus()`
- `statusStep()`
- `getPickupQrPayloadAttribute()`

### Relations

- `user()`
- `items()`
- `reviews()`
- `statusHistories()`

## `app/Models/OrderItem.php`

- `price()`: original product price accessor
- `priceAtTime()`: paid price snapshot accessor
- `product()`
- `order()`

## `app/Models/OrderStatusHistory.php`

- `order()`: back-reference

## `app/Models/Transaction.php`

- `bill()`: related order
- `user()`: owner
- `boot()`: prevents unsafe transaction mutation patterns

## `app/Models/WalletLedger.php`

- `user()`: ledger owner

## `app/Models/CartSnapshot.php`

- `user()`: snapshot owner

## `app/Models/PaymentEvent.php`

- `user()`: related payer
- `recordPending(...)`: server-owned pending event creation helper
- `canRetry()`: whether admin retry is allowed

## `app/Models/Coupon.php`

- `value()`: normalized money accessor
- `isValid()`: active/date/remaining-use validation
- `calculateDiscount(...)`
- `calculateDiscountCents(...)`
- `markUsed()`
- `redeemForOrder(...)`: transaction-safe redemption write path
- `redemptions()`: relation

## `app/Models/CouponRedemption.php`

- `coupon()`
- `user()`
- `order()`

## `app/Models/ProductReview.php`

- `user()`
- `product()`
- `order()`

## `app/Models/AuditLog.php`

- `actor()`: who caused the audited action

## `app/Models/IdempotencyKey.php`

Persistence model for idempotent API actions.

## Request Classes

These files centralize validation and sometimes authorization.

## Web requests

- `AddCartItemRequest`: validates add-to-cart payload
- `InitiateRefillRequest`: validates refill amount and exposes `amountCents()`
- `ProductOptionsRequest`: validates size/temp/add-on combinations and product ownership
- `ProfileUpdateRequest`: validates profile updates
- `StoreProductReviewRequest`: validates review writes
- `StoreSharedRecipeRequest`: validates recipe sharing

## API requests

- `API/CheckoutRequest`:
  - `prepareForValidation()`: normalizes idempotency key and incoming payload
  - `authorize()`: confirms user auth context
  - `rules()`: checkout field validation
- `API/RemoveCartItemRequest`: validates delete target
- `API/StoreFavoriteRequest`: validates API favorite create payload
- `API/UpdateCartItemRequest`: validates cart quantity changes

## Auth requests

- `Auth/LoginRequest`:
  - `authorize()`
  - `rules()`
  - `authenticate()`
  - `ensureIsNotRateLimited()`
  - `throttleKey()`

## Resources

These files shape JSON output for the Flutter app.

- `CartResource`: one cart row
- `CategoryResource`: one dashboard category with nested products
- `FavoriteResource`: one favorite row
- `OrderItemResource`: one order item snapshot
- `OrderResource`: order-level API payload
- `ProductResource`: mobile product payload
- `TransactionResource`: lightweight transaction summary
- `UserResource`: normalized user payload

Every `toArray(Request $request)` is the translation boundary between Eloquent internals and API contract.

## Middleware

## `app/Http/Middleware/AdminPermission.php`

### `handle(...)`

- checks authenticated admin
- verifies one or more required permission strings
- rejects unauthorized actions early

## `app/Http/Middleware/SecurityHeaders.php`

### `handle(...)`

- sends CSP
- blocks clickjacking
- sets referrer and MIME sniffing headers
- optionally emits HSTS when appropriate

## Observers

## `app/Observers/OrderObserver.php`

### Purpose

Writes immutable order status history.

### Methods

- `created(Order $order)`: records initial state
- `updated(Order $order)`: records transitions
- `record(...)`: shared writer
- `actor()`: infers who caused the status change

## `app/Observers/UserObserver.php`

### Purpose

Maintains blind indexes / searchable encrypted user fields.

### Methods

- `salt()`: returns configured hashing salt
- `makeIndex(...)`: builds blind index
- `saving(User $user)`: recomputes indexes before save

## Event + Listener Lifecycle

## `app/Events/OrderPlaced.php`

Carries:

- order
- user
- which cart rows were OZ-paid
- cash amount
- OZ used
- reward OZ

It is the event that fans out post-order side effects.

## Listeners

### `ClearUserCart`

- deletes all cart rows after successful order placement

### `DeductUserBalance`

- applies the cash debit side of a direct checkout

### `RewardUserOz`

- awards loyalty OZ for cash-paid value

### `RewardReferrer`

- awards referral reward in a transaction-safe way

### `SendOrderNotification`

- delegates the accepted-order event to `RealtimeNotificationService`
- runs as an event listener, while the resulting notification itself is queued after the database transaction commits

## Notifications

## `RealtimeBusinessNotification`

- is the unified queued notification for order, payment, and Tangki events
- writes the same typed payload to the `database` and `broadcast` channels
- calls `afterCommit()` so workers cannot observe an uncommitted business state
- uses Laravel's notifiable broadcast channel instead of manually composing a database user ID channel

The notification implements:

- `via(...)`
- `toArray(...)`
- `toBroadcast(...)`
- `broadcastType()`
- `payload()`

Its stable payload contains:

- `notification_id`
- `event`
- `title`
- `message`
- `occurred_at`
- `action`
- `data`

`User::receivesBroadcastNotificationsOn()` routes the broadcast to `private-App.Models.User.{uuid}`. The UUID must match `routes/channels.php` and the identifier exposed to Flutter by `UserResource`.

## View Components

These are lightweight layout wrappers:

- `AdminLayout`
- `AppLayout`
- `GuestLayout`

Their methods mostly just return the Blade view shell used by each UI context.
