# Coffee-Plus 完整安裝、啟動與運維手冊

> 適用專案：Coffee-Plus Laravel 後端與 Blade Web
>
> 預設 Windows/Laragon 路徑：`C:\laragon\www\Coffee-Plus`
>
> 最後核對日期：2026-07-16

## 1. 文件用途

本文件提供一條從乾淨電腦到完整運行 Coffee-Plus 的可執行流程，包含：

- PHP、Composer、Node.js、資料庫與 Stripe CLI 需求。
- SQLite 與 MySQL 兩種本機初始化方式。
- HTTP、Vite、Queue、Reverb、Scheduler 與 Stripe Webhook 的啟動方式。
- Seeder 帳號、首次登入與 smoke test。
- 即時訂單通知和取餐提醒驗證。
- Windows/Laragon 與 Linux 正式部署差異。
- 更新、停止、重啟、備份和常見故障處理。

如果只想了解架構和業務規則，請先讀 `docs/DEVELOPER_ONBOARDING.md`。Flutter 即時通知實作請讀 `docs/FLUTTER_REALTIME_NOTIFICATION_INTEGRATION_REPORT.md`。

## 2. 完整運行需要哪些程序

Coffee-Plus 不是只啟動一個 HTTP server 就完成。完整開發環境包含：

| 程序 | 用途 | 缺少時的影響 |
|---|---|---|
| HTTP server | Web、Admin、API、Stripe Webhook、broadcast auth | 整個應用無法存取 |
| Vite | 開發中的 CSS/JavaScript HMR | 開發資源不更新；production build 不受影響 |
| Queue worker | queued database/broadcast notification | 通知工作停留在 jobs table |
| Reverb server | 前台 WebSocket 即時事件 | API 仍可用，但前台不會即時收到通知 |
| Scheduler | 取餐提醒、Sanctum prune、Telescope prune | 定時任務不會執行 |
| Stripe CLI | 本機接收 Stripe test webhook | 只有需要測試 Stripe 時才需要 |

正式環境還需要 Web server、TLS、process manager、cron、database backup 和監控。

## 3. 環境需求

### 3.1 必要版本

- PHP 8.2 以上，建議 PHP 8.3。
- Composer 2。
- Node.js 18 以上與 npm。
- SQLite 或 MySQL 8。
- PHP extensions：OpenSSL、PDO、Mbstring、Tokenizer、XML、Ctype、JSON、Fileinfo、GD、Sodium。
- Stripe CLI：只在本機測試 Stripe 時需要。
- Redis：正式環境建議使用，本機可使用 database cache/session/queue。

### 3.2 Windows/Laragon 檢查

```powershell
php -v
composer --version
node --version
npm.cmd --version
```

若 `php` 不在 PATH：

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan --version
```

Laragon 使用者應確認 Apache/Nginx 與 MySQL 的實際啟動狀態，不要只確認 Laragon UI 已開啟。

## 4. 取得專案

```powershell
Set-Location C:\laragon\www
git clone https://github.com/banana1638/Coffee-Plus.git
Set-Location C:\laragon\www\Coffee-Plus
```

如果 repository 已存在：

```powershell
git status --short --branch
git remote -v
```

在更新或初始化前先查看 `git status`，不要覆蓋尚未提交的本機修改。

## 5. 最快初始化：SQLite

只可在新的開發 checkout 第一次執行：

```powershell
composer setup
```

此命令會：

1. 執行 `composer install`。
2. 在 `.env` 不存在時複製 `.env.example`。
3. 建立 `database/database.sqlite`。
4. 產生 `APP_KEY`。
5. 執行 migration。
6. 安裝 npm dependencies。
7. 執行 Vite production build。

不要在已經有正式資料或有效 encrypted data 的環境重複執行初始化流程。尤其不要任意更換 `APP_KEY`，否則 encrypted phone/address、session 和其他 encrypted values 可能無法解密。

如需示範登入帳號：

```powershell
php artisan db:seed
```

## 6. 手動初始化

### 6.1 安裝依賴

```powershell
composer install
npm.cmd install
Copy-Item .env.example .env
php artisan key:generate
```

不要覆蓋已存在的 `.env`。

### 6.2 SQLite

`.env`：

```env
DB_CONNECTION=sqlite
```

建立檔案：

```powershell
New-Item database\database.sqlite -ItemType File -Force
php artisan migrate
```

### 6.3 MySQL/Laragon

先建立 database：

```sql
CREATE DATABASE coffee_plus CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

`.env`：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coffee_plus
DB_USERNAME=root
DB_PASSWORD=
```

清除舊 config 並 migration：

```powershell
php artisan config:clear
php artisan migrate
```

### 6.4 Seeder

```powershell
php artisan db:seed
```

開發帳號：

| 類型 | Email | Password | URL |
|---|---|---|---|
| 顧客 | `test@coffee.com` | `password123` | `/login` |
| 管理員 | `admin@coffee.com` | `password123` | `/admin/login` |

這些是弱密碼，只允許本機開發。Production 禁止執行會建立這些帳號的 seeder。

示範商品資料不是預設 Seeder 的一部分。需要時才執行：

```powershell
php artisan db:seed --class=CoffeeShopSeeder
```

## 7. 本機 `.env` 基準

以下是重要欄位，不要把真實 secret 提交到 Git：

```env
APP_NAME="Coffee Plus"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_KEY=base64:generated-by-artisan

CORS_ALLOWED_ORIGINS=http://127.0.0.1:8000,http://localhost:8000

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=coffee-plus-local
REVERB_APP_KEY=coffepluskey123
REVERB_APP_SECRET=change-me
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_ALLOWED_ORIGINS=http://127.0.0.1:8000,http://localhost:8000

PICKUP_REMINDER_MINUTES=10
PICKUP_REMINDER_GRACE_MINUTES=15

STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

TELESCOPE_ENABLED=false
TELESCOPE_PATH=admin/telescope
TELESCOPE_PRUNE_HOURS=168
```

`REVERB_SERVER_HOST` 是 Reverb process 的監聽位址；`REVERB_HOST` 是 Laravel broadcaster 和 client 使用的可達位址。兩者不要混淆。

修改 `.env` 後：

```powershell
php artisan config:clear
```

如果 database cache 尚未可用，不要直接依賴 `optimize:clear`；可分別執行 `config:clear`、`route:clear` 和 `view:clear`。

## 8. 一條命令啟動整套系統

### 8.1 Artisan HTTP 模式

```powershell
composer run dev:full
```

這會啟動：

- `php artisan serve --host=127.0.0.1 --port=8000`
- `php artisan queue:listen --tries=3 --timeout=60`
- `php artisan pail --timeout=0`
- `npm run dev`
- `php artisan reverb:start`
- `php artisan schedule:work`

任何一個子程序停止時，concurrently 會停止其餘程序。按 `Ctrl+C` 可停止整組程序。

### 8.2 Laragon Apache/Nginx 模式

如果 Laragon 已經把 Coffee-Plus 指向 `public/`：

```powershell
composer run dev:laragon
```

此模式不啟動 `artisan serve`，只啟動 Queue、Pail、Vite、Reverb 和 Scheduler。

Laragon URL 可能是 `http://coffee-plus.test`。此時必須同步修改：

```env
APP_URL=http://coffee-plus.test
CORS_ALLOWED_ORIGINS=http://coffee-plus.test
REVERB_ALLOWED_ORIGINS=http://coffee-plus.test
```

## 9. 分開啟動每個程序

除錯時建議使用不同 terminal。

Terminal 1，HTTP：

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Terminal 2，Vite：

```powershell
npm.cmd run dev
```

Terminal 3，Queue：

```powershell
php artisan queue:work --tries=3 --timeout=60
```

Terminal 4，Reverb：

```powershell
php artisan reverb:start --debug
```

Terminal 5，Scheduler：

```powershell
php artisan schedule:work
```

Terminal 6，Stripe CLI，可選：

```powershell
stripe listen --forward-to http://127.0.0.1:8000/api/stripe/webhook
```

## 10. 第一次啟動驗證

### 10.1 基礎命令

```powershell
php artisan about
php artisan migrate:status
php artisan route:list --except-vendor
php artisan channel:list
php artisan event:list --event=OrderPlaced
php artisan schedule:list
php artisan queue:failed
```

### 10.2 URL

| 功能 | URL |
|---|---|
| 顧客首頁 | `http://127.0.0.1:8000/` |
| 顧客登入 | `http://127.0.0.1:8000/login` |
| 管理員登入 | `http://127.0.0.1:8000/admin/login` |
| API login | `POST http://127.0.0.1:8000/api/login` |
| Stripe webhook | `POST http://127.0.0.1:8000/api/stripe/webhook` |
| Broadcast auth | `POST http://127.0.0.1:8000/api/broadcasting/auth` |
| Telescope | `/admin/telescope`，只有明確啟用及授權時可用 |

### 10.3 API 登入 smoke test

```powershell
$body = @{ email='test@coffee.com'; password='password123'; device_name='startup-check' } | ConvertTo-Json
Invoke-RestMethod -Method Post -Uri http://127.0.0.1:8000/api/login -ContentType application/json -Body $body
```

不要把輸出的 Sanctum token 放入 issue、截圖、log 或文件。

## 11. Stripe 本機測試

1. 使用 Stripe test mode key。
2. 登入 Stripe CLI：`stripe login`。
3. 啟動 HTTP server。
4. 啟動 Stripe listener。
5. 把 CLI 顯示的 `whsec_...` 寫入 `.env` 的 `STRIPE_WEBHOOK_SECRET`。
6. 執行 `php artisan config:clear`。
7. 從 Web 或 Flutter 建立 Stripe checkout/refill。
8. 觀察 Stripe CLI、Laravel log、`payment_events` 與 transaction/ledger。

```powershell
stripe listen --forward-to http://127.0.0.1:8000/api/stripe/webhook
```

安全規則：

- Success redirect 不能建立訂單或增加 Tangki。
- 只有驗證簽名、owner、type、amount、currency 和 server-owned pending event 後才能處理。
- 重送 webhook 不得重複建立訂單或補值。
- Flutter 必須輪詢 server payment status，不能信任本地 redirect 結果。

## 12. Reverb 與即時通知驗證

後端使用 UUID 私有頻道：

```text
private-App.Models.User.{uuid}
```

穩定事件：

- `order.accepted`
- `order.preparing`
- `order.ready_for_pickup`
- `order.pickup_reminder`
- `order.completed`
- `order.cancelled`
- `payment.checkout_succeeded`
- `payment.checkout_failed`
- `wallet.refill_succeeded`
- `wallet.refill_failed`

檢查步驟：

1. Queue、Reverb 和 HTTP 均在運行。
2. 使用者透過 Sanctum 登入。
3. Client 呼叫 `/api/broadcasting/auth`，只授權自己的 UUID channel。
4. 建立或更新訂單狀態。
5. Queue terminal 應處理 `RealtimeBusinessNotification`。
6. `notifications` table 應出現 database notification。
7. Reverb terminal 應顯示連線與 broadcast。
8. Client 收到事件後重新讀取 API，不直接信任 payload 改變金額或終態。

Reverb 只保證 App 前台或進程存活時的即時傳遞。若要求 App 被系統終止後仍收到通知，需要另外整合 FCM/APNs。

## 13. 取餐提醒驗證

確認 scheduler：

```powershell
php artisan schedule:list
```

應看到：

```text
orders:send-pickup-reminders    Every minute
```

手動執行：

```powershell
php artisan orders:send-pickup-reminders
```

命令只處理：

- `status = ready_for_pickup`
- `pickup_time` 位於 reminder/grace window
- `pickup_reminder_sent_at IS NULL`

命令會鎖定訂單並先寫入 `pickup_reminder_sent_at`，因此重複執行不應重複提醒。

## 14. Telescope

預設：

```env
TELESCOPE_ENABLED=false
TELESCOPE_PATH=admin/telescope
TELESCOPE_PRUNE_HOURS=168
```

本機臨時啟用後：

```powershell
php artisan config:clear
```

存取 `/admin/telescope` 仍需要 admin 登入、`telescope.view` permission 和 Telescope Gate。Telescope 可能包含 request、SQL、exception 和 job payload，不得對區網或外網匿名公開。

## 15. 測試與提交前驗證

```powershell
composer validate --no-check-publish
php artisan test
php artisan route:cache
php artisan view:cache
php artisan coffee:security-check
npm.cmd run build
git diff --check
git status --short
```

目前完整基準：

```text
202 tests
806 assertions
```

正式部署前：

```powershell
php artisan coffee:security-check --production
composer audit --locked
php artisan migrate:status
```

## 16. 每日開發流程

開始：

```powershell
git status --short --branch
composer install
npm.cmd install
php artisan migrate
composer run dev:full
```

只有 lock file 改變時才需要重新安裝 dependencies。只有 migration 改變時才需要 migration。

停止：

- 一鍵模式按 `Ctrl+C`。
- 分開 terminal 模式在每個 terminal 按 `Ctrl+C`。
- 不要以強制結束資料庫程序代替正常停止應用 worker。

Queue job code 或 config 改變後：

```powershell
php artisan queue:restart
```

Reverb config 或部署改變後：

```powershell
php artisan reverb:restart
```

## 17. 更新既有安裝

先備份並確認工作樹：

```powershell
git status --short --branch
git pull --ff-only
composer install --no-interaction
npm.cmd install
php artisan migrate
npm.cmd run build
php artisan config:clear
php artisan queue:restart
php artisan reverb:restart
```

如果有未提交修改，不要直接 pull 或覆蓋。先建立 commit、stash，或確認變更歸屬。

## 18. 正式環境基準

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
LOG_LEVEL=warning

CORS_ALLOWED_ORIGINS=https://your-domain.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SECURITY_HSTS_ENABLED=true

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

BROADCAST_CONNECTION=reverb
REVERB_HOST=your-domain.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
REVERB_ALLOWED_ORIGINS=https://your-domain.com

TELESCOPE_ENABLED=false
```

正式環境必須使用全新強隨機 Reverb key/secret 和 server-only Stripe secrets。

部署命令：

```bash
composer install --no-dev --classmap-authoritative
npm ci
npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan coffee:security-check --production
```

部署帳號必須可寫入 `storage/` 和 `bootstrap/cache/`。`optimize` 已包含 config、event、route 和 view cache；若舊 route cache 因權限無法清除，先修正目錄權限，不能修改 `vendor/` 規避。

Web server document root 必須指向 `public/`，不能指向 repository root。

## 19. Production Process Manager

Queue worker 和 Reverb 必須由 Supervisor、systemd 或等效平台服務管理。

Queue Supervisor：

```ini
[program:coffee-plus-worker]
command=php /var/www/coffee-plus/artisan queue:work redis --sleep=1 --tries=3 --timeout=60
directory=/var/www/coffee-plus
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/www/coffee-plus/storage/logs/worker.log
stopwaitsecs=90
```

Reverb Supervisor：

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

Scheduler cron：

```cron
* * * * * cd /var/www/coffee-plus && php artisan schedule:run >> /dev/null 2>&1
```

## 20. Nginx Reverb Proxy

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

實際 path、load balancer 和 TLS termination 依部署平台調整。部署後必須測試 WebSocket upgrade、private auth 和 server-side broadcast。

## 21. 備份與恢復

至少備份：

- Database。
- `.env` 的安全離線副本。
- `storage/app` 中需要保留的檔案。
- 使用者上傳或產品圖片的實際儲存位置。

備份必須加密並限制存取。只有成功執行 restore drill 的備份才可視為可用。

Migration 前：

1. 建立 database backup。
2. 記錄目前 commit SHA。
3. 執行 `php artisan migrate:status`。
4. 檢查 migration 是否包含不可逆資料轉換。
5. 在 staging 或 backup copy 測試。

## 22. 故障排查矩陣

| 症狀 | 優先檢查 |
|---|---|
| `php` 找不到 | Laragon PHP 路徑與 PATH |
| SQLSTATE 2002 | MySQL 是否啟動、host/port/database |
| SQLite database 不存在 | `database/database.sqlite` 是否建立 |
| `.env` 修改無效 | `php artisan config:clear`、worker 是否重啟 |
| 頁面沒有樣式 | Vite 是否運行，或是否已 `npm run build` |
| Queue 沒有處理 | `QUEUE_CONNECTION`、worker、`queue:failed` |
| 通知沒有保存 | Queue worker、jobs/failed_jobs、Laravel log |
| Reverb 連不上 | server、host/port/scheme、allowed origins、proxy upgrade |
| Private channel 403 | Sanctum token、UUID channel、broadcast auth route |
| 取餐提醒沒發送 | scheduler、訂單狀態、pickup_time、提醒 marker |
| Stripe webhook 400 | webhook secret 或 signature 不一致 |
| Stripe webhook 500 | PaymentEvent、amount/type/currency/owner mismatch |
| Telescope 403 | admin guard、permission、Gate、enabled config |
| `schedule:list` 連 DB 失敗 | database cache lock 與 DB 服務狀態 |
| 圖片 404 | public storage link、APP_URL、檔案位置 |

常用診斷：

```powershell
php artisan about
php artisan route:list --except-vendor
php artisan channel:list
php artisan event:list
php artisan migrate:status
php artisan queue:failed
php artisan schedule:list
php artisan coffee:security-check
Get-Content storage\logs\laravel.log -Tail 100
```

## 23. 安全完成條件

系統只有在以下條件滿足時才可對區網或外網開放：

- `.env` 不在 Git，Web root 指向 `public/`。
- Production 使用 `APP_ENV=production`、`APP_DEBUG=false`。
- Stripe 和 Reverb 使用真實環境專用 secrets。
- `CORS_ALLOWED_ORIGINS` 與 `REVERB_ALLOWED_ORIGINS` 沒有 wildcard。
- HTTPS/WSS 正常，HSTS 只在確認 HTTPS 後啟用。
- Queue、Reverb 和 Scheduler 由 process manager/cron 管理。
- Telescope 預設關閉，或只允許已授權 owner。
- `php artisan coffee:security-check --production` 通過。
- 完整測試與 smoke test 通過。
- Database 和檔案備份已實際驗證可恢復。

## 24. 啟動完成判定

本機完整啟動應同時滿足：

1. 首頁、登入和管理端可開啟。
2. Vite 沒有 build/runtime error。
3. Queue 能處理 notification job。
4. Reverb 能建立 private UUID channel。
5. Scheduler 顯示每分鐘 pickup reminder command。
6. Stripe test webhook 能成功驗證並保持 idempotent。
7. API owner scope、admin permission 和安全 header 正常。
8. `php artisan test`、security check 和 frontend build 通過。

達到以上條件才算「整個 Coffee-Plus 專案已完整啟動」，而不只是 HTTP 首頁可以開啟。
