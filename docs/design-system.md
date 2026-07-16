# Design System

This design system is the default source of truth for modern Laravel Blade UI in this project.

## Stack

- Laravel Blade for views and composition.
- Tailwind CSS for styling.
- Alpine.js only for local interaction such as dropdowns, modals, tabs, disclosure panels, and mobile navigation.
- Livewire only when explicitly requested.

## Coffee Plus Visual Direction

- Customer ordering uses a cafe menu board, drink recipe card, receipt, and pickup-ticket information structure.
- Admin operations use a preparation-station board with compact queues, clear state, and restrained density.
- Login and 2FA use a restrained security entrance metaphor with one clear action and minimal decoration.
- Every visual element must map to a real cafe object, workflow signal, or operational state. Remove visual choices that exist only as decoration.
- Clean, practical, professional, and easy to scan without generic SaaS styling.
- Use unframed sections first; reserve cards for repeated items or genuinely bounded tools.
- Use subtle borders, small shadows, and corners no larger than `rounded-lg` by default.
- Clear typography hierarchy and predictable spacing.
- Responsive layouts that work well on mobile first, then expand for desktop.

## Design Tokens

Use these Tailwind patterns unless the existing project already defines equivalent tokens.

### Color

- Semantic token intent:
  - Ink `#18201d`: primary text and serious reading surfaces.
  - Brand `#136f54`: primary actions, active states, and backend-confirmed positive states.
  - Accent `#b87e2d`: money, prices, balances, and key numeric value only.
  - Canvas `#f6f7f3`: page background.
  - Surface `#fffffc`: card and tool surfaces.
  - Line `#dadfd8`: dividers and borders.
- Page background: `rgb(var(--cp-canvas))` (`#f6f7f3`)
- Surface: `rgb(var(--cp-surface))` (`#fffffc`)
- Muted surface: `bg-stone-100`
- Border: `rgb(var(--cp-line))` (`#dadfd8`)
- Divider: `divide-slate-200`
- Primary text: `text-slate-950`
- Secondary text: `text-slate-600`
- Muted text: `text-slate-500`
- Primary action: `rgb(var(--cp-brand))` (`#136f54`), hover `#0d523f`
- Secondary action: `bg-white text-slate-700 border border-slate-300 hover:bg-slate-50`
- Danger action: `bg-rose-600 text-white hover:bg-rose-700`
- Success: `text-emerald-700 bg-emerald-50 border-emerald-200`
- Warning: `text-amber-700 bg-amber-50 border-amber-200`
- Error: `text-rose-700 bg-rose-50 border-rose-200`

Avoid dominant gradients, glass effects, decorative color overload, large brown surfaces, stacked shadows, and over-rounded UI. Do not use accent/caramel as a warning color because its semantic role is money.

### Spacing

- Page shell: `px-4 py-6 sm:px-6 lg:px-8`
- Section stack: `space-y-6`
- Card padding: `p-4 sm:p-6`
- Compact controls: `gap-2`
- Form groups: `space-y-2`
- Dense lists/tables: `px-4 py-3`

### Radius And Shadow

- Default radius: `rounded-lg`
- Small controls: `rounded-lg`
- Cards: `rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] shadow-sm`
- Dropdowns and modals: `rounded-lg border border-[rgb(var(--cp-line))] bg-[rgb(var(--cp-surface))] shadow-lg`

### Typography

- Page title: `text-2xl font-semibold text-slate-950`
- Section title: `text-base font-semibold text-slate-950`
- Card title: `text-sm font-semibold text-slate-950`
- Body: `text-sm text-slate-600`
- Muted helper: `text-xs text-slate-500`
- Labels: `text-sm font-medium text-slate-700`

Typography roles:

- Serif/display role: use `Fraunces` for brand, page titles, menu-board headings, and recipe-card headers.
- Monospace/tabular role: prices, balances, order numbers, pickup codes, and ledger rows.
- Sans/body role: use `Figtree` for descriptions, helper text, labels, controls, and dense admin content.

Repeated RM, cents, balance, and price output should use a shared component or helper pattern instead of page-specific formatting.

## Layout Rules

- Use a dashboard shell for authenticated/admin pages.
- Keep customer and admin shells separate. They may share tokens and base components, but customer pages need more decision-friendly breathing room while admin pages need denser operations spacing.
- Keep page headers concise: title, short subtitle, and primary action when needed.
- Use max width only when content benefits from it; dashboards can use full available width.
- Use grids for metrics and card groups:
  - Metrics: `grid gap-4 sm:grid-cols-2 xl:grid-cols-4`
  - Main content: `grid gap-6 lg:grid-cols-12`
- Keep repeated page furniture in layouts or components.

## Sidebar Rules

- Desktop sidebar should be fixed or sticky where appropriate.
- Include product/app name at the top, primary navigation, and optional account/help section.
- Active item: `bg-slate-900 text-white` or the project's primary active treatment.
- Inactive item: `text-slate-600 hover:bg-slate-100 hover:text-slate-950`.
- Use icon plus label when an icon system exists; do not add a new icon library without approval.
- Mobile navigation should collapse behind a button or drawer.

## Navbar Rules

- Include page context, global search, notifications, profile, or actions only when useful.
- Keep navbar height stable: usually `h-16`.
- Use border bottom: `border-b border-slate-200`.
- Avoid duplicating sidebar navigation on desktop.

## Card Rules

- Use cards for grouped content, metrics, tables, forms, and repeated resource summaries.
- Do not nest cards unless the inner card is a repeated item with a real boundary.
- Standard card shell: `rounded-xl border border-slate-200 bg-white shadow-sm`.
- Card header: flex row on desktop, stacked on mobile when actions may wrap.
- Card body should use consistent spacing and avoid crowded text.
- The only approved memorable flourish is pickup-ticket perforation, reserved for order or pickup-ticket surfaces.

## Table Rules

- Wrap tables in `overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm`.
- Add inner horizontal scroll on small screens: `overflow-x-auto`.
- Header: `bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500`.
- Rows: `divide-y divide-slate-200`, optional `hover:bg-slate-50`.
- Include empty states and pagination areas when data can be absent or long.

## Form Rules

- Every input needs a visible label unless the surrounding pattern makes the label redundant and accessible naming remains intact.
- Include help text when the field is not obvious.
- Place validation errors directly under the related field.
- Use consistent input shell: `rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500`.
- Group actions at the bottom with primary first, secondary/cancel next.

## Modal Rules

- Use Alpine.js for simple open/close behavior when no existing modal system exists.
- Include overlay, dialog panel, title, body, and actions.
- Support Escape and click-away where practical.
- Keep modal width constrained: `max-w-lg` for common dialogs, wider only for complex forms.

## Button Rules

- Primary: `inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2`
- Secondary: `inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50`
- Subtle: `inline-flex items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950`
- Danger: `inline-flex items-center justify-center rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-700`
- Icon-only buttons need `aria-label`.

## Empty, Loading, And Error States

- Empty state: concise title, one sentence, optional action.
- Loading state: use skeleton blocks matching final layout, disabled submit buttons, or existing loading conventions.
- Error state: explain what failed and provide a recovery path.
- Trust-critical pending state uses neutral color; confirmed state uses brand green; attention/failure uses dedicated warning/error colors, not accent money color.

## State Architecture Rules

- Blade is server-rendered and request/response based; do not assume Flutter-style persistent widget state.
- Cart, checkout, Tangki, payment, and order state must treat the backend as the only source of truth.
- Every state change should map to a clearly named backend action such as add, remove, update quantity, checkout, refill, debit, refund, mark ready, or verify pickup.
- Payment, refund, balance, order completion, and 2FA screens must never visually imply success before backend confirmation.
- Critical order/refill actions should display frozen backend records or ledger entries, not recomputed client state.

## Responsive Rules

- Start with a single-column mobile layout.
- Use `sm:` for small refinements, `md:` for two-column content, `lg:` for dashboard/sidebar layouts, and `xl:` for dense metrics.
- Ensure buttons and filters wrap cleanly.
- Tables must remain usable on narrow screens through horizontal scroll or stacked cards.

## Accessibility Rules

- Use semantic landmarks: `header`, `nav`, `main`, `section`, `aside`.
- Use real buttons for actions and links for navigation.
- Add labels, `aria-label`, `aria-expanded`, `aria-controls`, and dialog roles where needed.
- Preserve visible focus states.
- Maintain readable contrast.
- Do not rely on color alone to communicate state.
