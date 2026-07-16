# AI Backend Environment Config

Track backend config only.

## Important Backend Env Areas

- APP_ENV
- APP_DEBUG
- APP_URL
- DB connection
- SANCTUM/session config if used
- CORS allowed origins
- filesystem disk
- storage public URL
- Reverb host/port/app key
- Reverb allowed origins
- broadcasting auth route
- payment provider keys if used
- pickup reminder window/grace

## Detected Package/Config Areas

- `config/auth.php`: web and admin session guards.
- Sanctum package installed; API protected routes use `auth:sanctum`.
- `config/broadcasting.php`: Reverb, Pusher, Ably, log, null connections.
- `config/reverb.php`: Reverb server/app settings and comma-separated `REVERB_ALLOWED_ORIGINS`.
- `config/filesystems.php`: local/private and public disks; public URL from `APP_URL`.
- `config/cors.php`: explicit API origin allowlist from `CORS_ALLOWED_ORIGINS`.
- Stripe SDK installed; webhook secret is read from `config('services.stripe.webhook')`.
- `php artisan coffee:security-check --production` validates critical production settings.

## Rules

- Do not commit real secrets.
- Keep .env.example safe.
- Production must use APP_DEBUG=false.
- Public origin must match app config.
- Storage origin must match media URL behavior.
- Reverb auth endpoint must be protected.
- Run `coffee:security-check --production` before deployment.
- Production `CORS_ALLOWED_ORIGINS` must be explicit and must not contain `*`.
- Production `REVERB_ALLOWED_ORIGINS` must be explicit and must not contain `*`.
- Telescope is available at `/admin/telescope` only when explicitly enabled; owner/super_admin authentication is required and records are pruned daily.
- A real domain is not required for CSP or other response headers. Enable HSTS only when the deployed origin consistently uses HTTPS.

## Local Runtime Commands

- `composer setup`: fresh SQLite checkout initialization only.
- `composer run dev:full`: HTTP, queue, Pail, Vite, Reverb, and scheduler.
- `composer run dev:laragon`: queue, Pail, Vite, Reverb, and scheduler when Laragon already serves HTTP.
- `PICKUP_REMINDER_MINUTES` and `PICKUP_REMINDER_GRACE_MINUTES` control the scheduled reminder window.

## Verified Windows Tooling

- Verified on 2026-07-16: PHP 8.3.30 from Laragon is present once in the current user's persistent `PATH`.
- Composer 2.10.1 resolves against PHP 8.3.30 and validates the project manifest.
- Required extensions including PDO MySQL, PDO SQLite, Mbstring, XML, GD, Sodium, Fileinfo, OpenSSL, Ctype, and Tokenizer are enabled.
- Reopen existing terminals after a persistent `PATH` change so they inherit the new value.
