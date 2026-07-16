# AI API Provider Contract

This file documents the backend API contract provided to Coffee-Plus-App.

## Rule

Backend API changes must update this file.

The Flutter app consumes this contract but does not define business truth.

## API Contract Table

| Feature | Method | Path | Handler | Auth | Request | Response | Risk | Status |
|---|---|---|---|---|---|---|---|---|
| Login | POST | /api/login | App\Http\Controllers\API\Auth\LoginController@login | No | email, password, optional device_name; throttled | JSON auth result/token | High | Detected |
| Register | POST | /api/register | App\Http\Controllers\API\Auth\RegisterController@register | No | registration payload; throttled | JSON auth/user result | High | Detected |
| Logout | POST | /api/logout | App\Http\Controllers\API\Auth\LoginController@logout | Yes, Sanctum | none | JSON logout result | Medium | Detected |
| Device tokens | GET/DELETE | /api/tokens, /api/tokens/{token} | App\Http\Controllers\API\TokenController | Yes, Sanctum | owned token ID for targeted revoke | Token metadata/revoke result | High | Added |
| Dashboard | GET | /api/dashboard | App\Http\Controllers\API\DashboardController@index | No | query optional | JSON dashboard data | Medium | Detected |
| Product detail | GET | /api/products/{id} | App\Http\Controllers\API\ProductController@show | No | path id | JSON product with add-ons array, full review totals, and latest 5 reviews | Medium | Verified |
| Product reviews | GET | /api/products/{product}/reviews | App\Http\Controllers\API\ProductReviewController@index | No | path product | JSON reviews | Medium | Detected |
| Cart index | GET | /api/cart | App\Http\Controllers\API\CartController@index | Yes, Sanctum | none | JSON cartItems | High | Detected |
| Cart add | POST | /api/cart/add | App\Http\Controllers\API\CartController@add | Yes, Sanctum | AddCartItemRequest | JSON cartCount | High | Detected |
| Cart update | POST | /api/cart/update | App\Http\Controllers\API\CartController@update | Yes, Sanctum | UpdateCartItemRequest | JSON status | High | Detected |
| Cart remove | POST | /api/cart/remove | App\Http\Controllers\API\CartController@destroy | Yes, Sanctum | RemoveCartItemRequest | JSON status | High | Detected |
| Checkout | POST | /api/checkout | App\Http\Controllers\API\OrderController@checkout | Yes, Sanctum | CheckoutRequest; requires Idempotency-Key | OrderResource JSON | Critical | Detected |
| Coupon validate | GET | /api/coupons/validate | App\Http\Controllers\API\CouponController@validateCode | Yes, Sanctum | coupon code query | JSON validation result | High | Detected |
| Order history | GET | /api/orders | App\Http\Controllers\API\OrderController@index | Yes, Sanctum | pagination | JSON orders/meta | High | Detected |
| Order detail | GET | /api/orders/{order} | App\Http\Controllers\API\OrderController@show | Yes, Sanctum | path order id | OrderResource JSON | High | Detected |
| Order cancel | POST | /api/orders/{order}/cancel | App\Http\Controllers\API\OrderController@cancel | Yes, Sanctum | path order id | OrderResource JSON | High | Detected |
| Wallet balance | GET | /api/tangki | App\Http\Controllers\API\TangkiController@index | Yes, Sanctum | none | JSON recent transaction summaries and user | Critical | Updated |
| Wallet refill | POST | /api/tangki/refill | App\Http\Controllers\API\TangkiController@initiateRefill | Yes, Sanctum | amount min 5 max 500 | Stripe session ID and redirect URL JSON | Critical | Detected |
| Payment status | GET | /api/payments/{sessionId}/status | App\Http\Controllers\API\PaymentStatusController | Yes, Sanctum | Stripe checkout session id | JSON status; returns pending when no user-owned event exists | Critical | Added |
| Stripe webhook | POST | /api/stripe/webhook | App\Http\Controllers\API\StripeWebhookController | No | Stripe signed completed, async-failed, or expired checkout event | JSON received/status; verified business result may enqueue user notification | Critical | Updated |
| Transactions | GET | /api/transactions | App\Http\Controllers\API\TransactionController@index | Yes, Sanctum | query optional | JSON paginated transaction summaries; order detail is not embedded | High | Updated |
| Transaction detail | GET | /api/transactions/{bill_id} | App\Http\Controllers\API\TransactionController@showOrderDetail | Yes, Sanctum | bill id | JSON order/transaction detail | High | Detected |
| Refunds | GET | /api/refunds | App\Http\Controllers\API\TransactionController@refunds | Yes, Sanctum | none | JSON paginated refund summaries; order detail is not embedded | High | Updated |
| Favorites | API resource | /api/favorites | App\Http\Controllers\API\FavoriteController | Yes, Sanctum | resource requests | JSON favorites/status | Medium | Detected |
| Shared recipes | GET/POST/POST import | /api/recipes | App\Http\Controllers\API\SharedRecipeController | Yes, Sanctum | recipe payload/import id | JSON recipes/status | Medium | Detected |
| Broadcasting auth | POST | /api/broadcasting/auth | Illuminate\Broadcasting\BroadcastController@authenticate | Yes, Sanctum | channel auth payload | Broadcast auth JSON | Medium | Detected |

## Backend Must Calculate

- product price
- subtotal
- discount
- final amount
- wallet debit/credit
- coupon validity
- order ownership
- payment success

## Notes

- Checkout uses `CheckoutRequest` and `IdempotencyService`; clients must send `Idempotency-Key`.
- Direct and Stripe-backed checkout re-read current backend product, size, and add-on pricing before order/payment snapshot creation; stored cart prices are not authoritative.
- Both Web and API Tangki refill initiation accept RM5-RM500 with at most two decimal places.
- Web and API share refill initiation and product-option validation services, while retaining their existing redirect/JSON response contracts.
- Tangki refill creates a server-owned pending `PaymentEvent` and returns `session_id`; crediting still happens only through Stripe webhook and `RefillHandler`.
- Stripe webhook validates pending payment ownership, business type, amount, and currency before processing it.
- Stripe webhook verifies signature and ignores unpaid/non-payment/non-MYR/unsupported sessions.
- Stripe completed, async-succeeded, async-failed, and expired events update server-owned `PaymentEvent` state before user notification. Duplicate event/session delivery does not repeat the money movement or notification.
- Private notification channels are `private-App.Models.User.{uuid}`. `/api/broadcasting/auth` authorizes only when the authenticated user's UUID matches the requested channel.
- Database notification `data` and Reverb broadcast payload use the same business envelope: `notification_id`, `event`, `title`, `message`, `occurred_at`, `action`, and nested `data`.
- Notification payloads are hints only. Flutter must re-fetch order, payment, or Tangki APIs before treating balances or terminal states as authoritative.
- Payment status endpoint only returns processed details for the authenticated user's own `PaymentEvent`; unknown or other-user sessions return `pending` to avoid session existence leaks.
- Order resources now include `coupon_code`, `coupon_discount`, and `discount_cents`.
- Order item resources prefer stored `order_items.product_name` so historical orders do not change when a product is renamed.
- API validation/auth/authorization/not-found errors now include `status: error` while preserving HTTP status codes.
- Token endpoints expose metadata only, scope every operation to the authenticated user, and support targeted or all-device revocation.
- Product detail embeds at most the latest five reviews; use the paginated product reviews endpoint for complete history.
- Product detail always returns `product.addons` as an array; each entry contains `id`, `name`, `price`, and `price_cents`. Dashboard products do not load add-ons.
- `/api/tangki`, `/api/transactions`, and `/api/refunds` return lightweight transaction summary rows and no longer embed `order_details` by default. Clients must call `GET /api/transactions/{bill_id}` when the user opens a transaction/order detail.
