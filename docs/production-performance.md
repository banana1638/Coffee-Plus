# Coffee-Plus Production Performance Guide

This guide documents the recommended production settings for Coffee-Plus. It is intentionally operational: applying it should improve runtime performance without changing checkout, payment, wallet, inventory, coupon, or RBAC behavior.

## Goals

- Reduce per-request framework bootstrap work.
- Move cache, session, and queue traffic away from the primary database.
- Keep order-critical work synchronous and move notifications/background tasks to workers.
- Serve optimized product images while keeping original images as fallback.
- Keep every setting reversible if the target server is not ready.

## Recommended `.env`

Use these values for production or staging-like performance tests:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
LOG_LEVEL=warning

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

TELESCOPE_ENABLED=false

REVERB_SCHEME=https
REVERB_PORT=443
REVERB_ALLOWED_ORIGINS=https://your-domain.example
```

Keep these payment values server-side only:

```dotenv
STRIPE_KEY=...
STRIPE_SECRET=...
STRIPE_WEBHOOK_SECRET=...
```

For HTTPS deployments:

```dotenv
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

## Deployment Commands

Run these after installing dependencies and setting `.env`:

```bash
php artisan migrate --force
php artisan products:generate-image-variants
npm run build
php artisan optimize:clear
php artisan optimize
php artisan coffee:security-check --production
```

The deploy user must be able to write `storage/` and `bootstrap/cache/`. `optimize` already rebuilds the config, event, route, and view caches; do not repeat the individual cache commands afterward.

Use this when changing config, routes, or views during a hotfix:

```bash
php artisan optimize:clear
php artisan optimize
```

## Queue Worker

`RealtimeBusinessNotification` is queued after the business transaction commits. In production, a worker must be running or database and broadcast notifications will remain in the queue.

Recommended worker command:

```bash
php artisan queue:work redis --queue=default --sleep=1 --tries=3 --timeout=60
```

Supervisor example:

```ini
[program:coffee-plus-worker]
process_name=%(program_name)s_%(process_num)02d
command=php C:/laragon/www/Coffee-Plus/artisan queue:work redis --queue=default --sleep=1 --tries=3 --timeout=60
directory=C:/laragon/www/Coffee-Plus
autostart=true
autorestart=true
numprocs=1
redirect_stderr=true
stdout_logfile=C:/laragon/www/Coffee-Plus/storage/logs/worker.log
stopwaitsecs=90
```

On Linux, replace the paths with the deployed project path.

## Reverb Server

Run Reverb under a process manager. The public endpoint should use WSS on port 443 through the web server or load balancer; the internal Reverb process may listen on `127.0.0.1:8080`.

```ini
[program:coffee-plus-reverb]
command=php /var/www/coffee-plus/artisan reverb:start --host=127.0.0.1 --port=8080
directory=/var/www/coffee-plus
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/coffee-plus/storage/logs/reverb.log
stopwaitsecs=30
```

Nginx WebSocket proxy example:

```nginx
location /app/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_read_timeout 60s;
}

location /apps/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
}
```

Use unique production `REVERB_APP_KEY` and `REVERB_APP_SECRET`, set `REVERB_SCHEME=https`, and explicitly configure `REVERB_ALLOWED_ORIGINS`. `php artisan coffee:security-check --production` rejects wildcard Reverb origins.

## Scheduler

Production must invoke Laravel's scheduler every minute:

```cron
* * * * * cd /var/www/coffee-plus && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler sends idempotent pickup reminders, prunes expired Sanctum tokens, and prunes Telescope records when enabled. Confirm registration with `php artisan schedule:list`.

## Work Classification

Keep these operations synchronous because they protect money and inventory:

- Deduct wallet balance.
- Deduct OZ.
- Deduct stock.
- Create order and order items.
- Record ledger entries.
- Process Stripe webhook idempotency.

Keep these operations queued or backgrounded:

- Notifications.
- Emails.
- Large exports.
- Image variant generation for existing products.

## Product Image Optimization

New uploads generate:

- Original image: `products.image`
- List thumbnail: `products.image_thumb`
- Detail image: `products.image_detail`

Existing images can be backfilled with:

```bash
php artisan products:generate-image-variants
```

The UI and API keep fallback behavior:

- If `image_thumb` is missing, list views use the original image.
- If `image_detail` is missing, detail views and `image_url` use the original image.
- Original files are not removed by the backfill command.

## Telescope

Telescope is useful locally but expensive in production because it records request, query, exception, job, and model entries.

Production recommendation:

```dotenv
TELESCOPE_ENABLED=false
```

If Telescope must be enabled briefly for debugging:

- Enable it for the shortest possible window.
- Restrict access.
- Run `php artisan telescope:prune` afterwards.

## Database Indexes

The performance indexes added by the application are designed for:

- Owner dashboard revenue queries.
- User order and transaction history.
- Product reviews.
- Shared recipes.
- Notifications.

After deployment, confirm migrations have run:

```bash
php artisan migrate:status
```

## Rollback Plan

If Redis is unavailable, revert only the drivers:

```dotenv
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

Then run:

```bash
php artisan config:clear
php artisan config:cache
```

`config:clear` avoids asking the unavailable Redis cache store to clear itself. This keeps the application functional while Redis is fixed, but it is less performant.

## Verification Checklist

After deployment:

- Login works for admin and customer users.
- Product list loads and images display.
- Product detail page displays image and reviews.
- Use Balance checkout succeeds when wallet balance is sufficient.
- Stripe webhook can create an order from a paid checkout session.
- Admin order dashboard loads.
- Product image upload creates original, thumbnail, and detail files.
- Queue worker processes notification jobs.
- Reverb accepts a private-channel connection through WSS and rejects an untrusted origin.
- Scheduler lists and executes `orders:send-pickup-reminders` every minute.
- `storage/logs/laravel.log` has no repeated queue, Redis, or image processing errors.

## Load Test Targets

Recommended first-pass targets:

- 50 concurrent users: stable response times.
- 100 concurrent users: acceptable response times, no failed checkout operations.
- 300 concurrent users: rate limiting or graceful slowdowns instead of application crashes.

Suggested tools:

- k6 for API and checkout flows.
- Laravel Telescope only in local or staging.
- Server logs plus queue worker logs for production-like tests.
