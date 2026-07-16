# 06. Commands And Migrations

## Console Commands

## `app/Console/Commands/CoffeeSecurityCheck.php`

### Purpose

Runs a production-readiness and security sanity check.

### `handle(): int`

Checks environment-sensitive conditions such as:

- app debug mode
- public origin assumptions
- storage / broadcast / Stripe config expectations
- explicit production CORS and Reverb origins; wildcard Reverb origins fail the production check
- other configuration that should not drift in production

This command is the fastest “is this deployment obviously unsafe?” tool in the repo.

## `app/Console/Commands/EncryptExistingUsers.php`

### Purpose

Migrates or backfills user encryption/blind-index behavior for existing rows.

### `handle(): int`

Execution flow:

1. reads CLI options like chunk size / dry run
2. iterates users in chunks
3. encrypts or re-saves fields so observers/accessors rebuild indexes
4. tracks migrated / skipped / failed counts

## `app/Console/Commands/GenerateProductImageVariants.php`

### Purpose

Backfills optimized product image variants for existing products.

### `handle(ProductImageService $productImageService): int`

Execution flow:

1. filters products that need missing variants
2. processes them in chunks
3. delegates image generation to `ProductImageService`

## `app/Console/Commands/SendPickupReminders.php`

### Purpose

Queues one realtime reminder for each ready order that is approaching its pickup time.

### `handle(RealtimeNotificationService $notificationService): int`

Execution flow:

1. bounds the configured reminder and grace windows to 1-120 minutes
2. selects ready orders with a pickup time in the active window and no sent marker
3. re-locks each candidate in a database transaction
4. re-checks status, time window, and the sent marker under the lock
5. stores `pickup_reminder_sent_at` before dispatching the queued notification

The database marker plus row lock makes repeated scheduler runs idempotent. `routes/console.php` runs this command every minute with `withoutOverlapping()`.

## Migration Strategy

The migration history shows how the project evolved from a simpler order app into a safer, more production-like backend.

Read migrations in chronological order to understand domain growth.

## Foundation migrations

- `0001_01_01_000000_create_users_table.php`
  - creates users, password resets, sessions
- `0001_01_01_000001_create_cache_table.php`
  - creates cache and cache locks
- `0001_01_01_000002_create_jobs_table.php`
  - creates jobs, job batches, failed jobs

## Early commerce model

- `2026_02_03_080924_add_tangki_to_users_table.php`
  - adds wallet balance fields to users
- `2026_02_03_081922_create_menus_table.php`
  - menu categories
- `2026_02_03_081929_create_products_table.php`
  - base product catalog
- `2026_02_03_081940_create_orders_table.php`
  - base order table
- `2026_02_03_081949_create_order_items_table.php`
  - line items
- `2026_02_03_081956_create_transactions_table.php`
  - user-facing transaction history

## Legacy social / invite experiments

- `2026_02_03_082138_create_friendships_table.php`
- `2026_02_03_082148_create_invite_codes_table.php`

These are signs the project previously explored invite/friend features before the later referral hardening.

## Observability and admin identity

- `2026_02_04_090620_create_telescope_entries_table.php`
  - Telescope storage
- `2026_02_13_071628_create_admins_table.php`
  - separate admin identity
- `2026_03_02_133238_add_role_to_admins_table.php`
  - admin role support
- `2026_06_28_020000_add_two_factor_fields_to_admins_table.php`
  - admin TOTP / recovery code support

## Product/media/cart evolution

- `2026_02_17_171632_add_image_to_products_table.php`
  - base product image field
- `2026_02_18_105635_create_cart_items_table.php`
  - cart rows
- `2026_02_18_125007_add_unit_price_to_cart_items_table.php`
  - persistent unit price snapshot
- `2026_03_09_175235_add_is_active_to_products_table.php`
  - active/inactive products
- `2026_03_24_114941_create_product_addons_table.php`
  - add-ons
- `2026_06_06_080000_add_stock_fields_to_products_table.php`
  - stock tracking
- `2026_06_12_010000_add_optimized_images_to_products_table.php`
  - thumbnail/detail variant fields
- `2026_06_12_020000_add_addons_signature_to_cart_and_favorites.php`
  - stable option matching

## Notifications and auth tokens

- `2026_02_21_172641_create_notifications_table.php`
  - database notifications
- `2026_03_03_113714_create_personal_access_tokens_table.php`
  - Sanctum tokens

## User privacy and referral hardening

- `2026_03_11_053111_add_phone_address_to_users_table.php`
  - more profile fields
- `2026_03_11_053741_add_blind_indexes_to_users_table.php`
  - searchable encrypted data
- `2026_03_25_100000_add_referrer_id_to_users_table.php`
  - referral linkage
- `2026_06_06_010000_add_secure_referral_fields_to_users_table.php`
  - secure referral fields

## Favorites, coupons, and recipe sharing

- `2026_03_21_000000_create_favorites_table.php`
  - saved recipes
- `2026_03_25_000000_add_soft_deletes_and_favorites_indexes.php`
  - favorite indexing improvements
- `2026_03_25_200000_create_coupons_table.php`
  - coupon system
- `2026_06_06_070000_create_coupon_redemptions_table.php`
  - safe redemption tracking
- `2026_03_25_300000_create_shared_recipes_table.php`
  - recipe sharing

## Order lifecycle improvements

- `2026_03_25_400000_add_pickup_time_to_orders_table.php`
  - pickup scheduling
- `2026_06_04_000000_add_cancelled_at_to_orders_table.php`
  - cancellation timestamps
- `2026_06_04_010000_add_pickup_code_to_orders_table.php`
  - pickup code workflow
- `2026_06_24_000000_add_order_snapshot_fields.php`
  - preserves historical product names / discounts / cents values
- `2026_06_28_010000_create_order_status_histories_table.php`
  - immutable transition history
- `2026_07_15_000000_add_pickup_reminder_to_orders_table.php`
  - one-time pickup reminder marker and scheduler query index

## Reviews

- `2026_06_04_020000_create_product_reviews_table.php`
  - post-order reviews

## Payment and money hardening

- `2026_06_06_000000_create_payment_events_table.php`
  - pending/processed payment lifecycle
- `2026_06_06_020000_create_cart_snapshots_table.php`
  - Stripe-safe immutable cart state
- `2026_06_06_030000_create_wallet_ledger_table.php`
  - durable ledger
- `2026_06_06_040000_create_idempotency_keys_table.php`
  - duplicate request guard
- `2026_06_06_041000_add_payment_uniques_to_orders_table.php`
  - payment uniqueness constraints
- `2026_06_06_050000_create_audit_logs_table.php`
  - admin accountability
- `2026_06_06_090000_add_cents_columns_to_money_tables.php`
  - integer money storage
- `2026_06_28_000000_add_retry_fields_to_payment_events_table.php`
  - provider retry support
- `2026_07_11_000000_add_wallet_ledger_integrity_constraint.php`
  - stricter ledger invariants

## Performance and integrity migrations

- `2026_02_24_072612_add_index_to_transactions_bill_id.php`
- `2026_03_14_191500_add_indexes_for_performance.php`
- `2026_06_06_060000_add_cart_quantity_check_constraint.php`
- `2026_06_12_000000_add_performance_composite_indexes.php`

These migrations show the project started caring about production scale and data integrity later in its lifecycle.

## How To Read A Migration

For each migration:

1. read the filename first
2. inspect `up()` to see what structure was added or changed
3. inspect `down()` to know the rollback assumption
4. ask whether the migration:
   - adds a new feature
   - hardens an existing feature
   - improves performance
   - repairs an old design choice

That reading method lets you reconstruct the system's history instead of only its final state.
