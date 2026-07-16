# AI Backend Project Memory

## Project Identity

Project:
- Coffee-Plus

Role:
- Laravel backend API and business logic provider

Paired client:
- Coffee-Plus-App, a separate Flutter app repository

## Detected Stack

- Laravel framework: ^12.0
- PHP requirement: ^8.2
- API auth: Laravel Sanctum personal access tokens
- Admin auth: session guard `admin`
- Realtime: Laravel Reverb broadcasting config and protected `/api/broadcasting/auth`
- Payments: Stripe PHP SDK and Stripe webhook handling
- Admin 2FA: OTPHP TOTP with encrypted secrets and hashed one-time recovery codes
- Tests: PHPUnit via `php artisan test`

## Backend Source of Truth

Coffee-Plus is the source of truth for:

- users
- auth
- roles
- products
- prices
- orders
- coupons
- wallet/Tangki balance
- payment/refill state
- admin actions
- file storage
- broadcasting auth

## High-Risk Backend Areas

- Wallet/Tangki refill
- Wallet ledger
- Payment verification
- Checkout total calculation
- Coupon concurrency
- Product price trust boundary
- Order status transition
- Admin authorization
- API auth middleware
- File upload validation
- Public storage access
- Reverb broadcasting auth
- CORS/public origins
- Debug mode
- Secrets exposure
