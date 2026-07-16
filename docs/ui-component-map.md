# UI Component Map

Use this map to decide where UI code belongs and when to create reusable Blade components.

## Layouts

| Pattern | Preferred File | Purpose |
| --- | --- | --- |
| Admin dashboard shell | `resources/views/layouts/admin.blade.php` | Sidebar, navbar, page container, shared scripts |
| Guest/auth shell | `resources/views/layouts/guest.blade.php` | Login, register, password screens |
| App shell | `resources/views/layouts/app.blade.php` | General authenticated pages when no admin shell exists |

## Core Components

| Component | Preferred File | Notes |
| --- | --- | --- |
| Admin sidebar nav item | `resources/views/components/nav/admin-sidebar-item.blade.php` | Permission-wrapped admin route with active state, icon slot, and compact label |
| Top navbar | `resources/views/components/layout/navbar.blade.php` | Page actions, search, account controls |
| Page header | `resources/views/components/layout/page-header.blade.php` | Title, description, optional action slot |
| Customer page header | `resources/views/components/layout/customer-page-header.blade.php` | Editorial customer-page title, context label, description, and action slot |
| Card | `resources/views/components/ui/card.blade.php` | Header, body, footer slots |
| Button | `resources/views/components/ui/button.blade.php` | Variants: primary, secondary, subtle, danger |
| Badge | `resources/views/components/ui/badge.blade.php` | Status and metadata labels |
| Price / amount | `resources/views/components/ui/price.blade.php` | Shared RM, cents, balance, and amount formatting with numeric typography |
| Empty state | `resources/views/components/ui/empty-state.blade.php` | Icon/title/body/action slots |
| Modal | `resources/views/components/ui/modal.blade.php` | Alpine-powered only when interaction is required |
| Table shell | `resources/views/components/ui/table-shell.blade.php` | Responsive wrapper and standard table chrome |
| Product offer | `resources/views/components/product-offer.blade.php` | Customer menu item with image, price, rating, OZ value, and configuration action |
| Ticket perforation | `resources/views/components/ticket-perforation.blade.php` | Reserved for pickup/order-ticket surfaces only; do not reuse on generic cards |
| Form field | `resources/views/components/forms/field.blade.php` | Label, help, error wrapper |
| Text input | `resources/views/components/forms/input.blade.php` | Standard text-like inputs |
| Select | `resources/views/components/forms/select.blade.php` | Standard select control |
| Textarea | `resources/views/components/forms/textarea.blade.php` | Standard multiline control |

## Page-Level Code

Keep page-specific composition in the existing view folder. Examples:

- `resources/views/dashboard.blade.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/settings/edit.blade.php`

Page views should compose layouts and components. Avoid repeating large button, card, form, table, modal, navbar, or sidebar markup directly in pages.

## Creation Rules

- Create a component when a pattern appears twice or is likely to appear across pages.
- Keep single-use layout composition in the page view when abstraction adds no value.
- Match existing component names before adding new ones.
- Do not add Livewire components unless explicitly requested.
- Do not add Alpine.js unless local interaction is needed.
- Do not introduce a new icon package from a component.
- Use shared money/price formatting for repeated amount display.
- Keep pickup-ticket perforation as a single signature component; do not generalize it into admin, wallet, payment, or catalog cards.

## Variant Rules

- Buttons: `primary`, `secondary`, `subtle`, `danger`.
- Badges: `neutral`, `success`, `warning`, `danger`, `info`.
- Cards: default, compact, metric.
- Tables: default, compact.
- Empty states: default, action, error.

## Documentation Rule

When adding a reusable UI component, update this map with the component path and intended usage.
