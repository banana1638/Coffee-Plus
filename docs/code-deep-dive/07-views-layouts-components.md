# 07. Views, Layouts, And Blade Components

## Why This Layer Matters

The earlier deep-dive files explain where the business rules live.

This file explains the presentation layer that sits on top of those rules:

- Blade page files in `resources/views/`
- layout shells
- reusable UI components
- small amounts of page-local JavaScript

Important boundary:

- controllers and services decide truth
- Blade decides how that truth is shown
- some Blade files also contain browser-side helpers for UX, previews, modal toggles, and fetch calls

That means the view layer is mostly presentation, but it is not trivial.
Several pages contain JavaScript that coordinates the web flow.

## The Three Main Layout Contexts

## `app/View/Components/AppLayout.php`

### Purpose

Maps `<x-app-layout>` to `resources/views/layouts/app.blade.php`.

### Runtime role

Most customer-facing pages use this component as their outer shell.
When a page starts with `<x-app-layout>`, Laravel resolves this PHP class, then renders the Blade layout.

## `app/View/Components/AdminLayout.php`

### Purpose

Maps `<x-admin-layout>` to `resources/views/layouts/admin.blade.php`.

### Runtime role

Used by admin dashboard, orders, products, coupons, payment event screens, and admin security pages.

## `app/View/Components/GuestLayout.php`

### Purpose

Maps `<x-guest-layout>` to `resources/views/layouts/guest.blade.php`.

### Runtime role

Used by unauthenticated/auth utility pages such as password reset and email verification flows.

## `resources/views/layouts/app.blade.php`

### Purpose

The main browser-user shell.

### What it does

1. prints the HTML document wrapper
2. loads CSRF token meta tag
3. loads Vite CSS and JavaScript
4. initializes Alpine state for auth modal control:
   - `authModal`
5. includes `layouts.navigation`
6. renders optional page header slot
7. renders the main page slot
8. renders `<x-auth-modals />` for guests only
9. renders any deferred scripts pushed by child views

### Why it matters

This layout is where cross-page customer behavior is centralized:

- navbar
- login/register modal state
- common styling shell
- shared scripts stack

## `resources/views/layouts/navigation.blade.php`

### Purpose

The customer navbar and top-level interaction hub.

### Main responsibilities

- shows brand and top navigation
- shows cart count
- shows notification dropdown
- shows user dropdown
- shows login/register buttons for guests
- handles mobile navigation drawer state

### Important data assumptions

This file expects shared view data such as:

- `$cartCount`
- `$navbarUnreadCount`
- `$navbarNotifications`

That strongly suggests one or more view composers/service providers are injecting navbar state globally.

### Important actions wired here

- `route('dashboard')`
- `route('tangki.index')`
- `route('cart.index')`
- `route('profile.edit')`
- `route('logout')`
- `route('notifications.markAllAsRead')`
- `route('notifications.markAsRead', $notification->id)`
- `route('notifications.destroy', $notification->id)`

### JavaScript role

This file does not use a separate JS module.
Instead it relies on Alpine state:

- `activeMenu`
- `mobileOpen`

This is a recurring pattern in the repo:
small local UI state is embedded directly inside Blade instead of being moved into a large frontend framework.

## `resources/views/layouts/admin.blade.php`

### Purpose

The admin shell.

### What it does

1. prints the HTML wrapper
2. loads Vite assets
3. includes `layouts.admin-navigation`
4. renders the admin page slot
5. exposes a scripts stack

### Why it matters

This separates admin navigation and spacing behavior from customer pages.
Admin pages therefore share one consistent left-sidebar shell.

## `resources/views/layouts/admin-navigation.blade.php`

### Purpose

The admin sidebar and mobile admin navigation.

### Main responsibilities

- reads the authenticated admin from `Auth::guard('admin')`
- shows navigation sections by permission
- switches active menu styling based on current route
- exposes logout and security settings actions
- provides mobile open/close state with Alpine

### Security significance

This file is only presentation, but it mirrors backend RBAC rules in the UI through:

- `@adminCan(...)`
- `$admin->canPerform(...)`

The true authorization still lives in middleware/controllers.
This view only hides or shows actions based on the same permission system.

## `resources/views/layouts/guest.blade.php`

### Purpose

Minimal shell for guest/auth pages.

### What it does

- document wrapper
- Vite assets
- raw page slot

It intentionally avoids navbar and app-shell complexity.

## Main Customer Page Views

## `resources/views/user/dashboard.blade.php`

### Purpose

The main browsing page for the customer web app.

### Inputs it expects

- `$menus`
- `$favorites`
- `$category`
- `$allCategoryNames`

### Main sections

- auth-aware welcome panel
- Tangki summary for logged-in users
- search form
- category tabs
- conditional include:
  - `user.favorites.list` for `collections`
  - `user.products.index` otherwise

### Important runtime behavior

This file is the page where the controller's two modes become visible:

- menu browsing mode
- saved collections mode

The controller decides the mode.
This view decides which partial to include.

## `resources/views/user/products/index.blade.php`

### Purpose

Renders the grouped product catalog inside the dashboard page.

### Runtime role

Usually receives menu groups already prepared by `DashboardMenuService` and the controller.
It should therefore stay simple and mostly iterate and render cards.

## `resources/views/user/favorites/list.blade.php`

### Purpose

Renders saved recipes / favorite combinations.

### Important actions

- links to product detail with `favorite_id` context
- posts add-to-cart requests through `fetch`

### Why it matters

This partial is not passive HTML.
It gives the user a shortcut from a saved recipe directly into cart behavior.

## `resources/views/user/products/detail.blade.php`

### Purpose

The most interactive customer page.

### Inputs it expects

- `$product`
- `$options`

### Major UI sections

- image panel
- name / rating header
- favorite toggle button
- optional favorite remark input
- temperature selection
- cup-size selection
- add-on selection
- recent reviews
- sticky bottom order bar
- modal partial include

### Browser-side logic inside this page

This file contains several important browser helpers:

- `updatePreviewPrice()`
  - recalculates estimated total from selected size, add-ons, and quantity
- `changeQty(val)`
  - updates quantity
- `executeSubmit()`
  - posts `FormData` to `route('cart.add')`
  - updates the navbar cart badge on success
- `toggleModal(id, show)`
  - opens/closes confirm and success modals
- `checkFavoriteStatus()`
  - calls `route('favorites.check')`
  - updates the heart icon and note area
- `toggleFavorite()`
  - posts to `route('favorites.toggle')`
  - refreshes the current favorite state

### Important trust boundary

The live price shown here is only a preview.
The backend still revalidates product, size, and add-on pricing in the cart and checkout flow.

That distinction is critical:

- Blade preview improves UX
- backend pricing remains authoritative

## `resources/views/user/cart/index.blade.php`

### Purpose

Shows cart contents and lets the user choose between:

- direct balance checkout
- Stripe checkout
- partial OZ redemption per line

### Inputs it expects

- `$cartItems`

### Main UX logic

Each cart row can be toggled as an OZ-paid item.
The page then recalculates:

- remaining cash total
- OZ usage summary
- whether the user has enough cash balance
- whether the user has enough OZ for more toggles

### Important JavaScript function

`updateCalculations()` is the core of the page.
It recomputes the browser preview state every time a redemption checkbox changes.

### Important trust boundary

This page visually enables or disables actions based on current user balances.
That is only a convenience layer.
The true checkout validation still happens in `CheckoutService`.

## `resources/views/user/tangki/index.blade.php`

### Purpose

Shows wallet/Tangki balances, refill actions, and recent ledger-style activity.

### Main sections

- current OZ and cash summary
- refill presets and custom refill form
- recent transaction list

### Browser-side logic

- `quickSubmit(value)`
- `formatDecimal(el)`
- form submit guard for empty/invalid amount
- loading-state handling

### Important trust boundary

The page explains a critical backend rule directly in the copy:
balance changes only after payment confirmation.

That matches the backend payment/wallet rules and helps prevent misleading UX.

## `resources/views/user/tangki/transactions.blade.php`

### Purpose

Paginated user transaction/activity view with filters and order-detail drill-down.

### Runtime role

Usually receives already-filtered query results from `TransactionController` and `TransactionQueryService`.

## `resources/views/user/tangki/order-detail.blade.php`

### Purpose

Detailed customer pickup ticket and order history page.

### Major sections

- order metadata
- status progress
- line items and recipe options
- review form or existing review state
- pickup QR and pickup code
- totals and cancellation block

### Important actions

- `route('orders.reviews.store', $order)`
- `route('order.cancel', $order)`
- browser print action

### Why it matters

This is where several backend concepts become visible together:

- order snapshot data
- order status machine output
- pickup code
- cancellation eligibility
- review ownership after completion

## `resources/views/user/profile/edit.blade.php`

### Purpose

Account management shell for the browser user.

### Structure

It mostly composes three partials:

- `user.profile.partials.update-profile-information-form`
- `user.profile.partials.update-password-form`
- `user.profile.partials.delete-user-form`

This keeps account-management concerns separated even though they share one page.

## Admin Page Views

## `resources/views/admin/dashboard.blade.php`

### Purpose

Admin preparation board.

### Inputs it expects

- `$pendingOrders`

### Main sections

- summary cards
- active preparation queue
- role-aware shortcuts

### Important note

This view directly calls aggregate model queries like `User::sum(...)` and `User::count()`.
That is convenient, but it also means a little reporting logic lives in the Blade layer instead of the controller.

## `resources/views/admin/owner_dashboard.blade.php`

### Purpose

Owner analytics screen.

### Runtime role

Presents reporting data prepared by the admin dashboard controller for higher-permission users.

## `resources/views/admin/orders/index.blade.php`

### Purpose

Main admin order operations list.

### Important actions

- `route('admin.orders.export.page')`
- `route('admin.orders.refunds')`
- `route('admin.orders.complete-by-code')`
- `route('admin.orders.show', $order)`
- `route('admin.orders.advance-status', $order)`

### Why it matters

This page is the admin operational hub for:

- finding live orders
- verifying pickup codes
- stepping orders through the status machine

## `resources/views/admin/orders/show.blade.php`

### Purpose

Detailed admin inspection page for one order.

### Runtime role

Makes status history, line items, pickup code, and transitions visible to staff/admin users.

## `resources/views/admin/orders/refunds.blade.php`

### Purpose

Refund-oriented admin queue/view.

### Runtime role

Lets admins inspect refund-related orders in a dedicated screen instead of mixing them into the active queue.

## `resources/views/admin/orders/export.blade.php`

### Purpose

Export center for report-capable admins.

## `resources/views/admin/products/index.blade.php`

### Purpose

Product management list.

### Runtime role

Lists catalog rows and links into create/edit flows owned by the admin product controller and image service.

## `resources/views/admin/products/create.blade.php`
## `resources/views/admin/products/edit.blade.php`

### Purpose

Create and edit product forms.

### Important behavior

These pages contain browser-side helper logic such as dynamic add-on row creation.
That means they are not just static forms.

## `resources/views/admin/coupons/index.blade.php`
## `resources/views/admin/coupons/create.blade.php`
## `resources/views/admin/coupons/edit.blade.php`
## `resources/views/admin/coupons/partials/form.blade.php`

### Purpose

Admin coupon CRUD screens.

### Runtime role

The partial form keeps common inputs in one place while create/edit pages wrap it with different controller actions.

## `resources/views/admin/payment-events/index.blade.php`
## `resources/views/admin/payment-events/show.blade.php`

### Purpose

Admin audit/inspection pages for payment events.

### Why they matter

These pages surface backend-owned payment processing state, which is critical for debugging webhook and retry behavior.

## Admin Auth And Security Views

## `resources/views/admin/login.blade.php`

Admin guard login UI.

## `resources/views/admin/two-factor-challenge.blade.php`

Second-step admin login challenge UI.

## `resources/views/admin/security/two-factor.blade.php`

Admin security settings screen for enabling/disabling and confirming TOTP plus recovery-code handling.

## Browser Auth Utility Views

## `resources/views/auth/confirm-password.blade.php`
## `resources/views/auth/forgot-password.blade.php`
## `resources/views/auth/reset-password.blade.php`
## `resources/views/auth/verify-email.blade.php`

These are narrow-purpose auth flow pages.
Their business logic lives mostly in Laravel auth controllers and brokers; the views mostly render forms and state messages.

## Reusable Blade Components

## Presentation-shell components

- `components/layout/page-header.blade.php`
  - standardized page title, description, and action-slot header
- `components/ui/card.blade.php`
  - reusable bordered surface container
- `components/ui/button.blade.php`
  - variant/size-aware button or anchor
- `components/ui/badge.blade.php`
  - reusable status/label pill
- `components/ui/price.blade.php`
  - consistent money formatting
- `components/ui/table-shell.blade.php`
  - shared table framing for admin/data pages

## Form/helper components

- `input-label.blade.php`
- `input-error.blade.php`
- `text-input.blade.php`
- `primary-button.blade.php`
- `secondary-button.blade.php`
- `danger-button.blade.php`
- `dropdown.blade.php`
- `dropdown-link.blade.php`
- `nav-link.blade.php`
- `responsive-nav-link.blade.php`
- `modal.blade.php`
- `auth-session-status.blade.php`

Most of these are Laravel Breeze-style primitives reused by auth/profile forms.

## Domain-flavored components

- `components/tank-visualization.blade.php`
  - visual Tangki balance representation
- `components/ticket-perforation.blade.php`
  - stylized ticket divider for pickup/order screens
- `components/order-modals.blade.php`
  - confirmation/success modal pair used by product detail add-to-cart flow
- `components/product-offer.blade.php`
  - product presentation helper
- `components/auth-modals.blade.php`
  - guest login/register modal with fetch-based submission
- `components/application-logo.blade.php`
  - reusable brand mark

## `resources/views/components/auth-modals.blade.php`

### Why it deserves special attention

This is one of the most behavior-heavy Blade components in the repo.

### What it does

- owns login/register tab state
- collects form data through Alpine
- posts JSON to `route('login')`
- posts JSON to `route('register')`
- reads JSON validation errors
- reloads the page on success

### Why it matters

It turns the normal Laravel auth flow into a modal-first UX without introducing a full SPA.

## `resources/views/components/order-modals.blade.php`

### Purpose

Reusable confirm/success modals for add-to-cart.

### Runtime role

It works together with JavaScript functions defined in `user/products/detail.blade.php`.
That is an example of a split interaction:

- HTML modal markup in a component
- modal control logic in the page

## Summary

The view layer in Coffee-Plus is not a full SPA and not purely static templating either.
It is a hybrid Blade application with:

- layout wrappers
- reusable design-system-like components
- page-local Alpine state
- page-local fetch handlers
- backend-owned business truth

When learning the project, read views with this question in mind:

- is this file only presenting backend data?
- or is it also coordinating a browser action?

That distinction tells you whether you also need to read the matching controller/service path.
