# 01. Entry Points, Routes, And Config

## `bootstrap/app.php`

### Purpose

This is the real application entrypoint for Laravel 12-style bootstrapping.

### What it does

- registers route files for web, API, console, and broadcast channels
- mounts `routes/admin.php` under `/admin` with the `admin.` name prefix
- appends global security headers middleware
- aliases `admin.permission` middleware
- changes guest redirect behavior so admin guests go to the admin login page instead of the normal user login
- customizes API exception responses for:
  - validation errors
  - authentication errors
  - authorization errors
  - not found errors

### Why it matters

This file is the reason admin and normal users behave like separate applications even though they live in one codebase.

## Route Files

## `routes/web.php`

### Purpose

Owns the browser-based customer experience.

### Main groups

- public dashboard and auth pages
- authenticated user flows
- cart
- wallet / Tangki pages
- favorites
- notifications
- Stripe browser redirect endpoints

### How to think about it

This file maps human-facing browser pages to controllers that usually return Blade views or redirects.

## `routes/api.php`

### Purpose

Owns the JSON API consumed by the Flutter client.

### Public API endpoints

- login
- register
- Stripe webhook
- product detail
- product reviews
- dashboard

### Protected API endpoints

Protected under `auth:sanctum`:

- broadcasting auth
- logout
- token management
- cart operations
- checkout
- payment status
- coupon validation
- order history / detail / cancel
- reviews
- profile updates and notification actions
- Tangki
- transactions and refunds
- favorites
- shared recipes

### Why it matters

This file is the API contract surface. If a mobile behavior exists, it usually starts here.

## `routes/admin.php`

### Purpose

Owns the browser-based admin interface.

### Main groups

- admin login and two-factor challenge
- authenticated admin dashboard
- owner analytics dashboard
- product CRUD
- coupon CRUD
- order management
- wallet adjustment
- payment event inspection and retry

### Important trait of this file

Almost every sensitive route has a permission middleware like `admin.permission:product.update`.

## `routes/auth.php`

### Purpose

Standard Laravel Breeze-style auth support routes for browser users.

### What lives here

- password reset
- email verification
- password confirmation

## `routes/channels.php`

### Purpose

Broadcast authorization rules.

### Behavior

- authorizes private user channels by comparing the authenticated user UUID with the route parameter

This is why realtime notifications stay owner-scoped.

## `routes/console.php`

### Purpose

Application console routes.

### Current usage

- includes Laravel's example inspire command
- prunes expired Sanctum tokens daily
- prunes Telescope records using the configured retention period
- runs `orders:send-pickup-reminders` every minute without overlap
- command implementation lives in `app/Console/Commands/`

## Config Files

## `config/app.php`

Core Laravel application identity and provider configuration.

## `config/auth.php`

Defines guards and providers.

In this project:

- normal browser users use the default user auth path
- admins use a separate `admin` guard
- API protection depends on Sanctum middleware rather than a custom JWT package

## `config/broadcasting.php`

Broadcast driver settings.

Important because this project supports Reverb and authenticated private channels.

## `config/cache.php`

Controls cache backend and lock storage.

Important because checkout locking and analytics caching rely on cache behavior.

## `config/coffee.php`

Project-specific configuration.

This is where product option defaults such as sizes and temperatures live, so product pages and validation have a shared reference point.

## `config/cors.php`

API browser-origin allowlist.

Important production boundary:

- this app expects explicit origins
- wildcard origins are a security smell for this project

## `config/database.php`

Database connections and migration behavior.

Important because many high-risk flows depend on transactions and row locks.

## `config/filesystems.php`

Storage disks and public media URLs.

Important for product image upload, public product images, and image variant generation.

## `config/logging.php`

Controls log channels and stack behavior.

Important when tracing payment or queue issues.

## `config/mail.php`

Mail transport config.

Used by standard auth and any future mail notifications.

## `config/queue.php`

Queue driver configuration.

Important because several order side effects are queued or queue-compatible.

## `config/reverb.php`

Realtime server configuration for Laravel Reverb.

Important for private notifications and broadcast auth. Local development may use wildcard origins, but production must set `REVERB_ALLOWED_ORIGINS` to explicit HTTPS origins; `coffee:security-check --production` rejects wildcard or empty lists.

## `config/realtime_notifications.php`

Controls the pickup reminder lead time and late grace window through `PICKUP_REMINDER_MINUTES` and `PICKUP_REMINDER_GRACE_MINUTES`.

## `config/sanctum.php`

Sanctum settings, especially stateful domains and token behavior.

Important because the API is protected by `auth:sanctum`.

## `config/security.php`

Project-defined security-header settings.

Used by `SecurityHeaders` middleware to emit CSP and related headers.

## `config/services.php`

Third-party integrations.

Most important integration here is Stripe, especially webhook secret configuration.

## `config/session.php`

Browser session behavior.

Important for:

- normal web auth
- admin auth
- two-factor challenge state

## `config/telescope.php`

Telescope observability settings.

Important because Telescope is available in admin-only mode when explicitly enabled.

## Route-To-Controller Reading Order

When you are trying to understand a request, use this order:

1. route file
2. controller method
3. request validation class, if any
4. service layer
5. models or resources touched by that service
6. events/listeners triggered after write actions

That reading order will explain most of the project faster than reading files alphabetically.
