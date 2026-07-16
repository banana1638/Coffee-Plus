# AI Backend Architecture Map

## Top-Level Backend Files

| File/Path | Responsibility | Notes |
|---|---|---|
| routes/api.php | API routes | Main mobile API surface; Sanctum protected group |
| routes/web.php | Web routes | User-facing web routes and Stripe success flow |
| routes/admin.php | Admin routes | Mounted at `/admin` from `bootstrap/app.php` |
| routes/channels.php | Broadcast channels | User private channel authorization |
| app/Http/Controllers/ | Controllers | Request handling |
| app/Http/Controllers/API/ | API controllers | Mobile API handlers |
| app/Http/Controllers/Admin/ | Admin controllers | Admin dashboard, products, coupons, orders |
| app/Console/Commands/ | Artisan commands | Maintenance and security checks |
| app/Models/ | Eloquent models | Data model |
| app/Services/ | Services | Business logic |
| app/Services/Payment/ | Payment services | Stripe checkout/refill handlers |
| app/Observers/OrderObserver.php | Order status audit | Immutable actor/source history |
| app/Services/AdminTwoFactorService.php | Admin TOTP | TOTP and recovery-code verification |
| app/Http/Requests/API/ | API FormRequests | API validation |
| app/Http/Middleware/AdminPermission.php | Admin permission middleware | Admin authorization gate |
| app/Providers/AppServiceProvider.php | Service bindings | Payment, pricing, cart, Tangki, checkout interfaces |
| database/migrations/ | Schema | Constraints, money, payment, coupon, ledger tables |
| config/auth.php | Auth config | Web/admin session guards; Sanctum package present |
| config/cors.php | CORS config | Explicit API origin allowlist from environment |
| config/broadcasting.php | Reverb/broadcasting | Reverb/Pusher/Ably/log/null connections |
| config/filesystems.php | Storage config | Local/public disks and storage URL |
| .env.example | Environment template | Safe config reference |

## Critical Backend Modules

| Module | Files | Risk |
|---|---|---|
| Auth | routes/api.php, routes/web.php, app/Http/Controllers/API/Auth/, app/Http/Controllers/Auth/, config/auth.php | High |
| Products | app/Http/Controllers/API/ProductController.php, app/Http/Controllers/Admin/ProductAdminController.php, app/Models/Product.php, app/Services/PricingService.php, app/Services/ProductImageService.php | Medium |
| Orders | app/Http/Controllers/API/OrderController.php, app/Http/Controllers/OrderController.php, app/Http/Controllers/Admin/AdminOrderController.php, app/Services/CheckoutService.php, app/Services/OrderService.php, app/Services/OrderStateMachine.php | High |
| Wallet/Tangki | app/Http/Controllers/API/TangkiController.php, app/Http/Controllers/TangkiController.php, app/Http/Controllers/Admin/WalletAdminController.php, app/Services/TangkiService.php, app/Services/LedgerService.php, app/Models/WalletLedger.php, app/Models/Transaction.php | Critical |
| Payment/Refill | app/Http/Controllers/API/StripeWebhookController.php, app/Http/Controllers/API/PaymentStatusController.php, app/Http/Controllers/PaymentController.php, app/Services/Payment/, app/Models/PaymentEvent.php, app/Models/CartSnapshot.php | Critical |
| Coupons | app/Http/Controllers/API/CouponController.php, app/Http/Controllers/Admin/CouponAdminController.php, app/Models/Coupon.php, app/Models/CouponRedemption.php | High |
| Admin | routes/admin.php, app/Http/Controllers/Admin/, app/Http/Middleware/AdminPermission.php, app/Models/Admin.php, app/Services/AdminTwoFactorService.php | High |
| File Upload | app/Http/Controllers/Admin/ProductAdminController.php, app/Services/ProductImageService.php, config/filesystems.php | High |
| Broadcasting/Reverb | routes/api.php, routes/channels.php, config/broadcasting.php | Medium |

## Validation Commands

Use detected commands only.

Detected commands:

- composer validate
- php artisan route:list
- php artisan test
- composer test
- php artisan coffee:security-check --production
