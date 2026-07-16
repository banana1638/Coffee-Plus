# AI Backend Task State

## Current Status

Last updated:
- 2026-07-16

Current task:
- Complete the admin-facing UI refactor step by step after the customer UI approval.

Current phase:
- Admin UI step 1 completed on `codex/customer-catalog-ui-refactor`: shared admin shell, permission-aware navigation rail, admin page header, and preparation board are browser-verified. Order operations pages are next.

Current scope and impact:
- View-only changes cover the customer dashboard, catalog, saved recipes, product configuration, cart/checkout, Tangki, ledger, pickup ticket, profile, authentication surfaces, customer navigation, and shared customer UI components.
- No route, controller, API response, pricing, wallet, payment, order, authentication, or authorization behavior changed.
- Local preview uses Laragon at `http://coffee-plus.test/`; no public domain is required for this review.
- Responsive QA confirmed no horizontal overflow at a 375px content width, all 10 local catalog items rendered, and guest product clicks still open the login panel.
- Authenticated browser QA confirmed product-to-cart success handling, live size-price updates, populated checkout totals, Tangki balances, ledger entries, profile forms, and pickup tickets. A long-order-id overflow was found and fixed with bounded grid columns and safe wrapping.
- Targeted customer-flow validation passed with 41 tests and 166 assertions; Vite, Blade compilation, and `git diff --check` passed.
- Admin step 1 preserves all `@adminCan` boundaries, admin routes, CSRF tokens, and PATCH actions. Targeted validation passed with 15 tests and 46 assertions.
- Desktop QA at 1265px and mobile QA at a 375px content width confirmed the preparation queue and navigation drawer render without horizontal overflow.

## Pending Backend Issues

| Priority | Issue | Area | Next Step |
|---|---|---|---|
| Critical | Confirm wallet/Tangki refill trust boundary remains callback-only | Wallet/Payment | Re-review API TangkiController, StripeWebhookController, RefillHandler before refill changes |
| Critical | Confirm payment verification and idempotency on all payment paths | Payment | Pending records and expected-value checks added; continue monitoring failed events |
| Critical | Expose payment/refill status to client without trusting client success | Payment | Added `/api/payments/{sessionId}/status`; monitor client adoption |
| Critical | Restrict manual wallet adjustment to audited admin flow | Wallet/Admin | Added `admin.wallet.adjust` route with ledger and audit logging |
| High | Preserve order item/product snapshot after product changes | Orders | Added order snapshot fields and API resource fallback |
| High | Lock critical API response contracts | API | Added API provider contract tests |
| High | Standardize API error responses | API | Added API-only validation/auth/authorization/not-found error shape |
| High | Let admins inspect and safely retry failed payment events | Payment/Admin | Provider-verified owner-only retry with retry and audit records added |
| High | Confirm API auth middleware for new endpoints | API | Inspect routes/api.php before adding routes |
| High | Confirm order total server-side calculation | Orders | Inspect CheckoutService, CartSnapshotService, PricingService |
| High | Confirm coupon concurrency and per-user redemption | Coupons | Coupon row locking and per-user redemption constraint verified |
| High | Confirm admin authorization on new admin routes | Admin | Use admin.permission middleware and Admin::canPerform |
| Medium | Confirm Reverb auth | Broadcasting | Inspect routes/api.php and routes/channels.php |
| Medium | Confirm CORS/env config | Config | Explicit allowlist added; configure production `CORS_ALLOWED_ORIGINS` |
| Medium | Run production security config checks before deployment | Config | Added `php artisan coffee:security-check --production` |
| Medium | Monitor Flutter adoption of lightweight transaction lists | API/Flutter | Flutter should fetch `/api/transactions/{bill_id}` for detail screens |

## Last Known Validation

- Local Windows tooling verification on 2026-07-16: persistent user `PATH` resolves PHP 8.3.30, Composer 2.10.1 uses that runtime, required PHP extensions are enabled, and `composer validate --no-check-publish` passes. Optional Stripe CLI installation was removed after the requested scope was narrowed to PHP and Composer only.
- The pending `2026_07_15_000000_add_pickup_reminder_to_orders_table` migration was applied successfully to the local MySQL database as batch 6 during environment verification.
- Full startup documentation work on 2026-07-16 added `docs/FULL_PROJECT_STARTUP.md`, `composer run dev:full`, and `composer run dev:laragon`; README, onboarding, production, Flutter, environment, security, and validation docs were synchronized.
- `/docs` is no longer ignored, so complete project documentation can be committed and delivered with the repository.
- Reverb origins now use `REVERB_ALLOWED_ORIGINS`; production security validation rejects empty or wildcard Reverb origins.
- Realtime backend notification implementation completed on 2026-07-15.
- Stable events: `order.accepted`, `order.preparing`, `order.ready_for_pickup`, `order.pickup_reminder`, `order.completed`, `order.cancelled`, `payment.checkout_succeeded`, `payment.checkout_failed`, `wallet.refill_succeeded`, and `wallet.refill_failed`.
- Broadcast notification channels now use the same user UUID consumed by `routes/channels.php` and Coffee-Plus-App.
- Database and broadcast delivery share one `event/title/message/occurred_at/action/data` payload source; queued notifications are dispatched after transaction commit.
- Pickup reminders run through `orders:send-pickup-reminders`, lock eligible orders, and persist `pickup_reminder_sent_at` before notifying, so repeated scheduler runs are idempotent.
- Stripe synchronous/async success, failure/expiry, and admin provider-verified retry outcomes notify only after server-side payment state handling. Notification transport failure is reported but cannot roll back money, wallet, or order state.
- Targeted security, realtime, Stripe webhook, and admin payment retry validation passed: 28 tests and 95 assertions.
- Final full suite passed with required filesystem test permissions: 202 tests and 806 assertions.
- `event:list --event=OrderPlaced`, `coffee:security-check`, `composer validate --no-check-publish`, changed-file PHP lint, Pint, Vite production build, and `git diff --check` passed.
- Config, route, and Blade view caches compiled successfully and were cleared afterward. A stale route cache first required real write permission on `bootstrap/cache`; no vendor patch was required.
- All local links resolved across README and 28 Markdown files under `docs/`.
- Native `schedule:list` could not acquire its database cache lock because local MySQL on port 3306 was stopped. Re-running the read-only listing with a temporary in-memory CLI cache confirmed `orders:send-pickup-reminders` is registered every minute; command execution and repeated-run idempotency also passed under the test database.
- Flutter realtime notification report completed on 2026-07-15: 812 lines, 13 sections, 68 balanced code-fence markers, zero trailing-whitespace findings, and all referenced backend/Flutter files resolved.
- Report documents the resolved UUID private-channel contract, defines a stable notification envelope, and separates foreground Reverb from background FCM/APNs responsibilities.
- View/runtime deep-dive docs created on 2026-07-15: `docs/code-deep-dive/07-views-layouts-components.md` and `docs/code-deep-dive/08-page-runtime-flows.md`, extending the learning set to `resources/views/`, `app/View/Components/`, and end-to-end browser page execution paths.
- Deep-dive code explanation docs created on 2026-07-15 under `docs/code-deep-dive/` plus `docs/CODE_DEEP_DIVE_INDEX.md`, covering executable first-party backend files in `app/`, `routes/`, `config/`, and `database/migrations/` for project learning and onboarding.
- FavoriteController static-analysis fix on 2026-07-15: both web and API `destroy` actions now type the `{id}` route parameter as `int`, clearing Intelephense missing-type warnings without changing delete behavior or ownership checks.
- DashboardController static-analysis fix on 2026-07-15: `auth()->check()` and implicit authenticated-user chaining were replaced with explicit `Auth` facade usage plus `User` typing in `app/Http/Controllers/DashboardController.php`, clearing the Intelephense undefined-method false positive without changing web dashboard behavior.
- Developer onboarding guide was expanded with full-service scripts, Reverb origin configuration, current notification events, pickup reminder operation, and current test baseline.
- SOLID refactor validation on 2026-07-11: full suite passed with 192 tests and 772 assertions; targeted suite passed with 35 tests and 192 assertions.
- `composer validate --no-check-publish`, route cache, backend security check, changed-file PHP lint, and `git diff --check` passed.
- Shared product option validation, refill initiation, transaction ownership queries, product detail loading, and product review validation now have one backend source of truth.
- Web/API response formatting and Favorite validation remain separate where their contracts differ.
- Passed on 2026-07-11 after security hardening.
- `php artisan test`: 192 tests and 772 assertions passed.
- Final targeted security/checkout/wallet tests: 31 tests and 134 assertions passed after cache cleanup.
- `/admin/telescope` route middleware verified: `auth:admin`, `admin.permission:telescope.view`, and Telescope authorization.
- Scheduler verified: `telescope:prune --hours=168` daily at 02:30.
- `composer validate`, Vite production build, PHP syntax checks, `git diff --check`, config cache, route cache, and view cache passed.
- MySQL migration application remains pending because local MySQL on port 3306 was not running; the migration passed SQLite test-suite execution and skips unsupported SQLite CHECK alteration.

Command:
- composer validate
- php artisan route:list
- php artisan test
- targeted feature tests for payment status, product uploads, wallet adjustment, security command

Result:
- Passed on 2026-07-06 after full UI refactor.
- `npm.cmd run build`: passed.
- Blade compilation and route cache: passed.
- `php artisan test`: passed, 181 tests and 716 assertions.
- `php artisan coffee:security-check`: passed.
- Config, route, and view caches were cleared individually.
- `optimize:clear` database-cache step could not run because local MySQL was not running; no application validation failed.
- Automated browser screenshots remain unavailable because the in-app browser cannot reach the local preview port in this environment.
- Passed on 2026-07-03 after Composer and npm dependency updates.
- Laravel remains `12.62.0`; Laravel 13 is deferred as a separate major-version migration.
- Composer updated 34 packages within existing constraints and removed one obsolete polyfill; audit reports no advisories.
- npm updated packages within existing constraints; audit reports 0 vulnerabilities and Vite production build passed.
- Composer platform requirements, package discovery, config cache, migration status, and clean-install dry run passed.
- Duplicate API/Web `favorites.destroy` names were resolved by prefixing API resource route names; route cache now passes.
- `php artisan test`: passed, 181 tests and 716 assertions.
- Passed on 2026-07-03 using Laragon PHP 8.3.30.
- `composer validate`: `./composer.json is valid`.
- `php artisan route:list --except-vendor`: passed, showing 115 application routes.
- `php artisan coffee:security-check`: passed.
- `php artisan test`: passed, 181 tests and 716 assertions.
- Product detail contract tests verify populated and empty `addons` arrays; Dashboard add-on loading remains unchanged.
- Passed on 2026-06-30 using Laragon PHP 8.3.30.
- `composer validate`: `./composer.json is valid`.
- `php artisan route:list --except-vendor`: passed, showing 115 application routes.
- `php artisan coffee:security-check`: passed.
- `php artisan test`: passed, 179 tests and 698 assertions.
- `composer audit --locked`: no security vulnerability advisories after Guzzle updates.
- Targeted tests passed for payment list projection, checkout query count, pending-order pagination, and bounded product reviews.
- Passed on 2026-07-07 after backend performance patch.
- Targeted tests: 24 passed, 179 assertions.
- `php artisan route:list --except-vendor`: passed, showing 115 application routes.
- `php artisan view:cache`: passed.
- `php artisan test`: passed, 182 tests and 727 assertions.
- `php artisan route:cache`: passed.
- `php artisan coffee:security-check`: passed.
