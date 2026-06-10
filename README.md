<div align="center">

<img src="https://img.icons8.com/fluency/96/coffee-to-go.png" alt="Coffee Plus Logo" width="96"/>

# ☕ Coffee Plus

**A modern coffee shop ordering backend — built with craft, secured with intention.**

[![PHP](https://img.shields.io/badge/PHP-8.2+-8892BF?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Stripe](https://img.shields.io/badge/Stripe-Payments-635BFF?style=flat-square&logo=stripe&logoColor=white)](https://stripe.com)
[![License](https://img.shields.io/badge/License-MIT-22C55E?style=flat-square)](LICENSE)
[![Tests](https://img.shields.io/badge/Tests-Feature%20%2B%20Unit-3B82F6?style=flat-square&logo=github-actions&logoColor=white)](tests/)

*School assignment turned serious project — a full-stack coffee ordering system with production-grade architecture.*

---

[Features](#-features) · [Architecture](#-architecture) · [API Reference](#-api-reference) · [Getting Started](#-getting-started) · [Security](#-security)

</div>

---

## ✨ Features

<table>
<tr>
<td width="50%">

### 🛒 Ordering Experience
- Browse products by menu category
- Add-ons and customisation per item
- OZ reward points redeemable at checkout
- Coupon code validation with atomic redemption
- Cart persistence across sessions
- Scheduled pickup time selection

</td>
<td width="50%">

### 💳 Payments & Wallet
- **Stripe Checkout** with Webhook verification
- **Tangki Wallet** — top up and pay in-app
- Double-entry ledger for every balance change
- Cart snapshot locks prices before Stripe redirect
- Idempotency keys prevent duplicate charges

</td>
</tr>
<tr>
<td>

### 🔐 Security & Auth
- Laravel Sanctum token authentication
- Rate-limited login, register, and checkout
- UUID-based referral codes (no ID enumeration)
- Atomic referral reward with race-condition guard
- Role-based admin permissions (PBAC)
- Full audit log for all admin actions

</td>
<td>

### 🏪 Admin Dashboard
- Owner & Staff role separation
- Product & stock management
- Order lifecycle management (advance / complete by pickup code)
- Coupon CRUD with usage analytics
- Order export (CSV / Excel)
- Refund tracking

</td>
</tr>
</table>

---

## 🏗 Architecture

```
coffee-plus/
├── app/
│   ├── Contracts/              # Service interfaces (Dependency Inversion)
│   ├── DataTransferObjects/    # Typed PaymentResult DTO
│   ├── Events/                 # OrderPlaced
│   ├── Listeners/              # Async (queued) post-order handlers
│   │   ├── SendOrderNotification   (ShouldQueue + afterCommit)
│   │   ├── DeductUserBalance
│   │   ├── RewardUserOz
│   │   ├── RewardReferrer          (atomic, race-condition safe)
│   │   └── ClearUserCart
│   ├── Http/
│   │   ├── Controllers/API/    # JSON API layer
│   │   ├── Controllers/Admin/  # Blade admin panel
│   │   ├── Middleware/         # AdminPermission (PBAC)
│   │   └── Requests/           # FormRequest validation
│   ├── Models/                 # 19 Eloquent models
│   ├── Services/
│   │   ├── CheckoutService         # Core checkout orchestrator
│   │   ├── CartSnapshotService     # Price-locking before payment
│   │   ├── LedgerService           # Double-entry bookkeeping
│   │   ├── IdempotencyService      # Duplicate-request guard
│   │   ├── OrderStateMachine       # Explicit status transitions
│   │   ├── PricingService          # Coupon + OZ calculation
│   │   ├── TangkiService           # Wallet operations
│   │   └── Payment/
│   │       ├── StripeGateway
│   │       ├── StripeCheckoutHandler
│   │       └── RefillHandler
│   └── Providers/
├── database/migrations/        # 45 migrations
├── routes/
│   ├── api.php                 # Sanctum-protected REST API
│   ├── admin.php               # Blade admin routes
│   └── web.php
└── tests/
    ├── Feature/                # 20+ feature tests
    └── Unit/
```

### Key Design Decisions

| Pattern | Where Used | Why |
|---|---|---|
| **Service Layer + Interfaces** | All business logic | Dependency inversion, testability |
| **CartSnapshot** | Stripe checkout flow | Locks prices & params before redirect |
| **Double-Entry Ledger** | Wallet balance changes | Auditable, tamper-evident financial trail |
| **Idempotency Keys** | Checkout & payments | Safe retries, no duplicate orders |
| **Event-Driven Listeners** | Post-order side effects | Decoupled, async, afterCommit-safe |
| **Order State Machine** | Status transitions | Prevents illegal state jumps |
| **PBAC Middleware** | Admin routes | Fine-grained permission per action |
| **Atomic CAS Update** | Referral rewards | Race-condition-safe one-time reward |

---

## 📡 API Reference

All API routes are prefixed with `/api`. Protected routes require:
```
Authorization: Bearer <sanctum_token>
```

### Auth

| Method | Endpoint | Rate Limit | Description |
|--------|----------|-----------|-------------|
| `POST` | `/login` | 5 / min | Login and receive token |
| `POST` | `/register` | 3 / min | Register (supports `?ref=` referral) |
| `POST` | `/logout` | — | Revoke current token |

### Products & Menu

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/dashboard` | ✗ | Products grouped by menu category |
| `GET` | `/products/{id}` | ✗ | Product detail with add-ons |
| `GET` | `/products/{product}/reviews` | ✗ | Paginated product reviews |

### Cart

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `GET` | `/cart` | ✓ | View cart |
| `POST` | `/cart/add` | ✓ | Add item (with add-ons) |
| `POST` | `/cart/update` | ✓ | Update quantity `min:1 max:99` |
| `POST` | `/cart/remove` | ✓ | Remove item |

### Orders & Checkout

| Method | Endpoint | Rate Limit | Description |
|--------|----------|-----------|-------------|
| `POST` | `/checkout` | 3 / min | Place order (Tangki wallet or Stripe) |
| `GET` | `/orders` | — | Order history |
| `GET` | `/orders/{order}` | — | Order detail (owner-scoped) |
| `POST` | `/orders/{order}/cancel` | 10 / min | Cancel pending order |
| `POST` | `/orders/{order}/reviews` | 10 / min | Submit product review |

### Wallet (Tangki)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/tangki` | Balance + recent transactions |
| `POST` | `/tangki/refill` | Initiate Stripe top-up session |

### Shared Recipes

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/recipes` | My received recipes |
| `POST` | `/recipes` | Share a recipe with another user |
| `POST` | `/recipes/{id}/import` | Import shared recipe to cart |

### Coupons & Misc

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/coupons/validate` | Validate coupon code |
| `GET` | `/profile` | User profile |
| `POST` | `/profile/password` | Update password |
| `GET` | `/profile/notifications` | In-app notifications |

### Stripe Webhook

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/stripe/webhook` | Stripe signed event receiver |

> Webhook verifies `Stripe-Signature` header using `STRIPE_WEBHOOK_SECRET`. All order creation and wallet top-ups are triggered exclusively from here — never from redirect URLs.

---

## 🚀 Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+ (for frontend assets)
- MySQL 8+ or SQLite (for local dev)
- Redis (for queues and cache)
- Stripe account (test mode keys)

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/banana1638/Coffee-Plus.git
cd Coffee-Plus

# 2. Install PHP dependencies
composer install

# 3. Copy and configure environment
cp .env.example .env
php artisan key:generate
```

### Environment Setup

Edit `.env` with your credentials:

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coffee_plus
DB_USERNAME=root
DB_PASSWORD=

# Queue (use Redis in production)
QUEUE_CONNECTION=redis
CACHE_STORE=redis

# Stripe
STRIPE_KEY=pk_test_xxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxx

# Mail
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
```

### Database & Seed

```bash
# Run all 45 migrations
php artisan migrate

# Seed with sample products, menus, and admin account
php artisan db:seed
```

### Run the Application

```bash
# Start development server
php artisan serve

# Start queue worker (required for notifications)
php artisan queue:work

# Listen for Stripe webhooks locally (requires Stripe CLI)
stripe listen --forward-to localhost:8000/api/stripe/webhook
```

### Run Tests

```bash
# Full test suite
php artisan test

# With coverage
php artisan test --coverage
```

---

## 🔐 Security

### Authentication Flow

```
Client → POST /api/login → Sanctum Token
      → Bearer {token} on all protected routes
      → Token scoped per user, revoked on logout
```

### Payment Security

```
User selects items
    → CartSnapshot created (prices locked server-side)
    → Stripe Checkout Session created
    → User pays on Stripe
    → Stripe fires Webhook (signed)
    → Server verifies signature
    → Idempotency check (no duplicate processing)
    → Order created from CartSnapshot
    → Redirect URL is UI-only, triggers no business logic
```

### Rate Limits

| Endpoint | Limit |
|----------|-------|
| `POST /login` | 5 req / min |
| `POST /register` | 3 req / min |
| `POST /checkout` | 3 req / min |
| `POST /orders/*/cancel` | 10 req / min |
| `GET /coupons/validate` | 10 req / min |

### Admin Permissions

Admin actions are guarded by `AdminPermission` middleware with granular permission strings:

```
product.create    product.update    product.delete
coupon.view       coupon.create     coupon.update     coupon.delete
order.view        order.status.update
report.export
```

All admin actions are recorded in the `audit_logs` table with actor, action, target, and timestamp.

---

## 🏆 OZ Rewards System

**OZ** is the in-app loyalty currency.

| Action | OZ Earned |
|--------|-----------|
| Every RM 1 spent | 10 OZ |
| Referring a new user (first order) | 50 OZ bonus |
| --- | --- |
| Redeem at checkout | 100 OZ = RM 1 discount |

Referral rewards use an atomic Compare-And-Swap update on `referral_rewarded` to guarantee one-time issuance even under concurrent load.

---

## 🗄 Database Overview

| Table | Purpose |
|-------|---------|
| `users` | Customers with Tangki balance, OZ, referral code |
| `admins` | Separate admin guard with role + permissions JSON |
| `products` / `product_addons` | Menu items with stock tracking |
| `cart_items` | Persistent user cart |
| `cart_snapshots` | Immutable price lock before Stripe redirect |
| `orders` / `order_items` | Placed orders with pickup code |
| `coupons` / `coupon_redemptions` | Discount codes with atomic redemption |
| `transactions` | Wallet credit/debit history |
| `wallet_ledger` | Double-entry bookkeeping (balance before/after) |
| `idempotency_keys` | Duplicate request deduplication |
| `payment_events` | Stripe webhook event log (processed/failed) |
| `audit_logs` | Admin action trail |
| `shared_recipes` | User-to-user cart sharing |

---

## 📦 Tech Stack

| Layer | Technology |
|-------|-----------|
| **Framework** | Laravel 12.x |
| **Language** | PHP 8.2+ |
| **Database** | MySQL 8 / SQLite (test) |
| **Cache / Queue** | Redis + Laravel Horizon |
| **Authentication** | Laravel Sanctum |
| **Payments** | Stripe (Checkout + Webhooks) |
| **Admin UI** | Blade + Tailwind CSS |
| **Testing** | PHPUnit + Laravel Feature Tests |
| **Code Style** | PSR-12 |

---

## 📄 License

This project is open-sourced under the [MIT License](LICENSE).

---

<div align="center">

Built with ☕ and a lot of `php artisan tinker`

*From a school assignment — to something worth shipping.*

</div>