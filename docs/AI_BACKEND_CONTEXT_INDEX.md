# AI Backend Context Index

This file exists to save tokens.

## Important Files

| File | Responsibility | When to Read |
|---|---|---|
| AGENTS.md | Backend agent operating rules | Before any non-trivial backend task |
| docs/DEVELOPER_ONBOARDING.md | Human developer setup, architecture, workflows, deployment, and troubleshooting | First day on the project or environment handoff |
| docs/FULL_PROJECT_STARTUP.md | Executable full-stack startup, Stripe/Reverb integration, operations, production, and recovery | Installing, starting, deploying, or troubleshooting the complete project |
| docs/FLUTTER_REALTIME_NOTIFICATION_INTEGRATION_REPORT.md | Reverb notification payload, Flutter changes, lifecycle, navigation, fallback, and testing plan | Implementing realtime order/payment/Tangki notifications across backend and Flutter |
| docs/CODE_DEEP_DIVE_INDEX.md | Deep learning-oriented map for first-party backend runtime files | When onboarding into file-by-file backend behavior |
| docs/code-deep-dive/07-views-layouts-components.md | View-layer, layout, and Blade component learning map | When learning `resources/views/` and layout/component responsibilities |
| docs/code-deep-dive/08-page-runtime-flows.md | End-to-end browser page flow map | When tracing page behavior from route to controller to service to Blade |
| routes/api.php | API routes | API contract, auth, mobile endpoints |
| routes/web.php | Web routes | User web behavior and Stripe success route |
| routes/admin.php | Admin routes | Admin routes, permissions, status changes |
| routes/channels.php | Broadcast auth channels | Reverb/private channel authorization |
| bootstrap/app.php | Laravel 12 routing/middleware bootstrap | Route registration, middleware aliases |
| app/Http/Controllers/API/ | API controllers | Endpoint logic |
| app/Http/Controllers/Admin/ | Admin controllers | Admin behavior |
| app/Console/Commands/ | Artisan commands | Maintenance/security command behavior |
| app/Http/Requests/ | Shared Web/API requests | Validation rules shared by both interfaces |
| app/Http/Requests/API/ | API-only requests | Validation rules specific to the API contract |
| app/Models/ | Models | Ownership, schema, money data |
| app/Services/ | Services | Business logic |
| app/Services/Payment/ | Payment handlers | Stripe checkout/refill handling |
| database/migrations/ | Schema | Constraints and table design |
| config/auth.php | Auth | Web/admin guards; API uses Sanctum middleware |
| config/cors.php | CORS | API origin allowlist and browser access control |
| config/broadcasting.php | Reverb | Realtime auth/config |
| config/filesystems.php | Storage | Upload/public URL logic |
| app/Providers/AppServiceProvider.php | Bindings and rate limiters | Interface implementation lookup |

## High-Risk File Shortcuts

| Area | Inspect First |
|---|---|
| API auth | routes/api.php, app/Http/Controllers/API/Auth/, config/auth.php |
| Admin auth | routes/admin.php, app/Http/Middleware/AdminPermission.php, app/Models/Admin.php, app/Services/AdminTwoFactorService.php |
| Cart validation | app/Http/Controllers/API/CartController.php, app/Http/Requests/AddCartItemRequest.php, app/Http/Requests/ProductOptionsRequest.php, app/Services/CartService.php |
| Checkout | app/Http/Controllers/API/OrderController.php, app/Http/Requests/API/CheckoutRequest.php, app/Services/CheckoutService.php |
| Pricing | app/Services/PricingService.php, app/Models/Product.php, app/Models/ProductAddon.php |
| Stripe webhook | app/Http/Controllers/API/StripeWebhookController.php, app/Models/PaymentEvent.php, app/Services/Payment/ |
| Payment status | app/Http/Controllers/API/PaymentStatusController.php, app/Models/PaymentEvent.php |
| Payment event audit | app/Http/Controllers/Admin/PaymentEventAdminController.php, resources/views/admin/payment-events/ |
| Admin pending orders | app/Http/Controllers/Admin/DashboardController.php, resources/views/admin/dashboard.blade.php |
| Owner dashboard analytics | app/Http/Controllers/Admin/DashboardController.php, resources/views/admin/owner_dashboard.blade.php |
| Product detail reviews | app/Services/ProductQueryService.php, app/Http/Controllers/API/ProductController.php, app/Http/Controllers/ProductController.php, app/Http/Resources/Api/ProductResource.php |
| Transaction list performance | app/Services/TransactionQueryService.php, app/Http/Controllers/API/TransactionController.php, app/Http/Controllers/API/TangkiController.php, app/Http/Resources/Api/TransactionResource.php |
| Web saved recipes pagination | app/Http/Controllers/DashboardController.php, resources/views/user/favorites/list.blade.php |
| Order export memory | app/Exports/OrdersExport.php |
| Refill | app/Http/Requests/InitiateRefillRequest.php, app/Services/RefillInitiationService.php, app/Http/Controllers/API/TangkiController.php, app/Services/Payment/RefillHandler.php |
| Wallet ledger | app/Services/LedgerService.php, app/Models/WalletLedger.php |
| Checkout repricing | app/Services/CartPricingService.php, app/Services/CheckoutService.php, app/Services/CartSnapshotService.php |
| Security headers | app/Http/Middleware/SecurityHeaders.php, config/security.php, bootstrap/app.php |
| Telescope access | app/Providers/TelescopeServiceProvider.php, config/telescope.php, routes/console.php |
| Admin wallet adjustment | routes/admin.php, app/Http/Controllers/Admin/WalletAdminController.php, app/Services/LedgerService.php, app/Services/AuditLogService.php |
| Coupons | app/Models/Coupon.php, app/Models/CouponRedemption.php, app/Http/Controllers/API/CouponController.php |
| Order state | app/Services/OrderService.php, app/Services/OrderStateMachine.php, app/Observers/OrderObserver.php, app/Models/OrderStatusHistory.php |
| Order snapshots | database/migrations/2026_06_24_000000_add_order_snapshot_fields.php, app/Services/CheckoutService.php, app/Http/Resources/Api/OrderResource.php, app/Http/Resources/Api/OrderItemResource.php |
| Product upload | app/Http/Controllers/Admin/ProductAdminController.php, app/Services/ProductImageService.php |
| Broadcasting | routes/api.php, routes/channels.php, config/broadcasting.php, config/reverb.php, app/Models/User.php |
| Realtime business notifications | app/Services/RealtimeNotificationService.php, app/Notifications/RealtimeBusinessNotification.php, app/Observers/OrderObserver.php, app/Listeners/SendOrderNotification.php |
| Pickup reminders | app/Console/Commands/SendPickupReminders.php, config/realtime_notifications.php, routes/console.php, orders.pickup_reminder_sent_at migration |
| Payment result notifications | app/Http/Controllers/API/StripeWebhookController.php, app/Services/Payment/PaymentRetryService.php, app/Models/PaymentEvent.php |

## Common Backend Debug Paths

| Symptom | Inspect First |
|---|---|
| API 401 | routes/api.php, auth config, Sanctum token issuance |
| API 403 | policies, gates, admin.permission middleware, ownership filters |
| API 422 | validation/FormRequest/controller |
| API 500 | controller/service root cause |
| Wrong wallet balance | Tangki controller, TangkiService, LedgerService, WalletLedger |
| Wrong order total | CheckoutService, CartSnapshotService, PricingService, product price, coupon |
| Coupon abuse | Coupon model, CouponRedemption, coupon service/controller, migrations |
| Upload risk | ProductAdminController, ProductImageService, validation, filesystems config |
| Broadcasting auth fail | routes/api.php, routes/channels.php, broadcasting config |
| Production config risk | app/Console/Commands/CoffeeSecurityCheck.php, config/services.php, config/broadcasting.php, config/filesystems.php |
| Full runtime startup | composer.json, .env.example, docs/FULL_PROJECT_STARTUP.md, config/reverb.php, routes/console.php |
| API response contract drift | tests/Feature/ApiProviderContractTest.php, tests/Feature/ApiErrorContractTest.php |
| Route cache failure | routes/api.php, routes/web.php; check duplicate route names with `route:list` |
