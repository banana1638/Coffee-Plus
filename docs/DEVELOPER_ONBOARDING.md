# Coffee-Plus 開發者完整上手指南

> 適用專案：Coffee-Plus Laravel 後端與 Blade Web 介面
>
> 最後核對日期：2026-07-11
>
> 主要技術：PHP 8.2+、Laravel 12、MySQL/SQLite、Sanctum、Stripe、Reverb、Vite、Tailwind CSS

## 1. 文件目的

這份文件協助新開發者從零完成本機安裝，理解專案架構，安全地修改功能，執行測試，並準備部署。

Coffee-Plus 是後端真實資料來源。Flutter 客戶端位於另一個 repository，不應從本 repository 修改。即使 Web 或 App 傳來價格、角色、支付結果或訂單狀態，後端也必須重新驗證，不可直接信任。

閱讀順序建議：

1. 完成「快速啟動」。
2. 閱讀「系統入口」和「架構與責任邊界」。
3. 若修改付款、錢包或結帳，必須閱讀「金流與 Tangki 安全模型」。
4. 修改 API 前閱讀 `docs/AI_API_PROVIDER_CONTRACT.md`。
5. 提交前執行「驗證清單」。

## 2. 核心安全原則

後端必須控制以下資料：

- 商品目前價格與附加項價格。
- Coupon 是否有效、折扣額與使用次數。
- 訂單小計、折扣、最終金額及 OZ 使用量。
- Tangki 餘額、Ledger 方向與變動金額。
- Stripe 是否真正付款成功。
- 使用者角色、管理員權限及資源所有權。
- 訂單狀態轉移與退款資格。
- 商品庫存及扣減時機。

禁止以下做法：

- 直接使用客戶端傳入的商品價格或最終金額。
- 在 Stripe success redirect 中建立訂單或增加錢包餘額。
- 只因客戶端回報 `success=true` 就標記付款成功。
- 使用未限制 `user_id` 的訂單、交易或 PaymentEvent 查詢。
- 在 Controller 中重複實作付款、Ledger 或 Coupon 核心規則。
- 將 `.env`、API key、Webhook secret 或正式帳號提交到 Git。

## 3. Repository 範圍

主要目錄：

```text
app/
  Console/Commands/          Artisan 維護與安全檢查命令
  Contracts/                 Service 介面與依賴反轉邊界
  DataTransferObjects/       支付等跨層資料物件
  Events/                    領域事件，例如 OrderPlaced
  Http/
    Controllers/             Blade Web Controller
    Controllers/API/         JSON API Controller
    Controllers/Admin/       管理端 Controller
    Middleware/              權限與安全標頭
    Requests/                Web/API 共用 FormRequest
    Requests/API/            API 專用 FormRequest
    Resources/Api/           穩定 API JSON 輸出
  Models/                    Eloquent Model 與資料關聯
  Observers/                 Model 生命週期處理
  Providers/                 容器綁定、限流器與 View composer
  Services/                  核心業務、查詢與協調服務
  Services/Payment/          Stripe 完成付款後的處理器
  Support/                   Money、附加項正規化等共用工具
bootstrap/app.php            Laravel 12 路由、中介層及例外設定
config/                      環境驅動設定
database/migrations/         Schema 與資料庫約束
database/seeders/            本機測試資料
resources/views/             Blade Web/Admin 頁面
resources/css/, resources/js/ Vite 前端入口
routes/web.php               顧客 Blade 網站
routes/api.php               Flutter/外部 JSON API
routes/admin.php             獨立管理員 Guard 路由
routes/channels.php          私有廣播頻道授權
routes/console.php           Scheduler 定時工作
tests/Feature/               主要行為及安全測試
tests/Unit/                  純單元測試
```

AI 維護文件位於 `docs/AI_*.md`。這些檔案記錄架構、安全基線、付款規則與 API 合約。`docs/` 已納入版本控制；功能、環境、API 或安全行為改變時，必須在同一變更中同步相關文件。

## 4. 環境需求

最低需求：

- PHP 8.2 或以上，建議使用 PHP 8.3。
- Composer 2。
- Node.js 18 或以上及 npm。
- MySQL 8；測試套件預設使用記憶體 SQLite。
- Stripe 帳號及 Stripe CLI，只有測試金流時需要。
- Reverb/WebSocket 開發時需要額外啟動 Reverb server。

Windows/Laragon 建議：

- 專案路徑可使用 `C:\laragon\www\Coffee-Plus`。
- 若系統 PATH 沒有 PHP，使用 Laragon 的完整 PHP 路徑。
- PowerShell 執行 npm 時可使用 `npm.cmd`。

確認版本：

```powershell
php -v
composer --version
node --version
npm.cmd --version
```

如果 `php` 不在 PATH：

```powershell
& 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe' artisan --version
```

## 5. 快速啟動

### 5.1 安裝依賴

預設 SQLite 的一鍵初始化：

```powershell
composer setup
```

這會安裝 PHP/Node 依賴、複製 `.env`、建立 SQLite 檔案、產生 APP_KEY、執行 migration 並建置前端。若要使用 MySQL，請使用以下手動流程，先修改 `.env` 再執行 migration。

```powershell
composer install
npm.cmd install
Copy-Item .env.example .env
php artisan key:generate
```

不要覆蓋已存在且包含有效本機設定的 `.env`。

### 5.2 選擇資料庫

最簡單的本機 SQLite 設定：

```env
DB_CONNECTION=sqlite
```

建立空檔案：

```powershell
New-Item database\database.sqlite -ItemType File -Force
```

MySQL/Laragon 設定範例：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=coffee_plus
DB_USERNAME=root
DB_PASSWORD=
```

先在 MySQL 建立 `coffee_plus` database，再執行 migration。

### 5.3 Migration 與 Seeder

```powershell
php artisan migrate
php artisan db:seed
```

`DatabaseSeeder` 目前建立以下本機帳號：

| 類型 | Email | Password | 入口 |
|---|---|---|---|
| 一般使用者 | `test@coffee.com` | `password123` | `/login` |
| 管理員 | `admin@coffee.com` | `password123` | `/admin/login` |

這些是開發用弱密碼，禁止在正式環境使用。正式部署不應直接執行會建立預設帳號的 Seeder。

`CoffeeShopSeeder` 不在預設 `DatabaseSeeder` 呼叫清單內。如需範例商品，可在確認不會破壞現有資料後執行：

```powershell
php artisan db:seed --class=CoffeeShopSeeder
```

### 5.4 建置前端資源

```powershell
npm.cmd run build
```

開發模式：

```powershell
npm.cmd run dev
```

### 5.5 啟動必要程序

至少啟動 HTTP server：

```powershell
php artisan serve
```

使用 database queue 時啟動 worker：

```powershell
php artisan queue:work --tries=3
```

需要即時通知時啟動 Reverb：

```powershell
php artisan reverb:start
```

需要定時工作時，本機可執行：

```powershell
php artisan schedule:work
```

`composer run dev` 可同時啟動 HTTP、queue、log 與 Vite，但仍需依本機 PATH 和服務狀態調整。

完整啟動 HTTP、queue、log、Vite、Reverb 與 scheduler：

```powershell
composer run dev:full
```

若 Laragon Apache 已經提供 HTTP，只啟動其餘服務：

```powershell
composer run dev:laragon
```

完整的啟動、停止、更新、驗證和正式部署流程見 `docs/FULL_PROJECT_STARTUP.md`。

## 6. `.env` 設定說明

### 6.1 應用程式

本機：

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
CORS_ALLOWED_ORIGINS=http://127.0.0.1:8000
```

正式環境：

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
CORS_ALLOWED_ORIGINS=https://your-domain.com
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SECURITY_HSTS_ENABLED=true
```

沒有真實域名不影響本機開發與測試，但會影響正式 HTTPS、Secure Cookie、HSTS、CORS、Sanctum stateful domain、Stripe redirect/webhook 與 Reverb TLS 設定。沒有域名時不要假裝已完成正式環境安全驗證。

### 6.2 Stripe

```env
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
```

使用 Stripe CLI 本機轉發：

```powershell
stripe listen --forward-to http://127.0.0.1:8000/api/stripe/webhook
```

將 CLI 顯示的 `whsec_...` 放到 `STRIPE_WEBHOOK_SECRET`，修改後執行：

```powershell
php artisan config:clear
```

### 6.3 Queue、Session 與 Cache

預設 `.env.example` 使用 database：

```env
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

本機沒有 MySQL 時，任何依賴 database cache/session/queue 的命令都可能失敗。測試不受影響，因為 `phpunit.xml` 使用記憶體 SQLite、array cache/session 與 sync queue。

### 6.4 Reverb

本機設定：

```env
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
```

本機可使用 HTTP/WS。正式環境必須使用 HTTPS/WSS、強隨機 key/secret，且 `REVERB_ALLOWED_ORIGINS` 只能列出可信 HTTPS origins，禁止 `*`。不要把 `.env.example` 的 `change-me` 帶到正式環境。

### 6.5 Telescope

Telescope 預設關閉：

```env
TELESCOPE_ENABLED=false
TELESCOPE_PATH=admin/telescope
TELESCOPE_PRUNE_HOURS=168
```

若本機啟用，路由位於 `/admin/telescope`，仍需要：

- `auth:admin`
- `admin.permission:telescope.view`
- Telescope Gate 授權

Scheduler 每天 02:30 清除超過保留時間的記錄。Telescope 會記錄 request、SQL、exception 等敏感診斷資料，不應公開。

### 6.6 即時通知與取餐提醒

通知由 `RealtimeNotificationService` 建立，透過 queued `RealtimeBusinessNotification` 同時寫入 database notification 並廣播到使用者 UUID 私有頻道。Queue worker 未運行時，通知不會被處理；Reverb 未運行時，資料庫通知仍可在 queue 恢復後保存，但前台即時事件不可用。

穩定事件名稱：

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

取餐提醒設定：

```env
PICKUP_REMINDER_MINUTES=10
PICKUP_REMINDER_GRACE_MINUTES=15
```

Scheduler 每分鐘執行 `orders:send-pickup-reminders`。命令使用 row lock 與 `pickup_reminder_sent_at`，避免重複提醒。可手動驗證：

```powershell
php artisan orders:send-pickup-reminders
php artisan schedule:list
```

## 7. 系統入口與認證

### 7.1 顧客 Web

- 路由：`routes/web.php`
- Guard：Laravel 預設 `web`
- 受保護頁面通常需要 `auth` 和 `verified`
- 回應：Blade view、redirect、session flash message

常用入口：

- `/` 商品首頁
- `/login`、`/register`
- `/cart`
- `/tangki`
- `/profile`

### 7.2 JSON API

- 路由：`routes/api.php`
- Prefix：`/api`
- Guard：`auth:sanctum`
- 回應：JSON 或 `App\Http\Resources\Api\*`
- Flutter 使用此介面，但不能定義後端價格或狀態真相

登入後使用 Bearer token：

```http
Authorization: Bearer YOUR_SANCTUM_TOKEN
Accept: application/json
Content-Type: application/json
```

範例：

```powershell
$body = @{ email='test@coffee.com'; password='password123'; device_name='local-dev' } | ConvertTo-Json
Invoke-RestMethod -Method Post -Uri http://127.0.0.1:8000/api/login -ContentType application/json -Body $body
```

API 的 validation、authentication、authorization 與 not-found error 應維持 `status: error` 結構及正確 HTTP status。修改 API 前查看 `docs/AI_API_PROVIDER_CONTRACT.md` 與 `tests/Feature/ApiProviderContractTest.php`。

### 7.3 管理端

- 路由檔：`routes/admin.php`
- Prefix：`/admin`
- Guard：`admin`
- 權限：`admin.permission:<permission>` middleware
- 高風險操作會寫入 Audit Log

管理員登入與一般使用者完全分離。新增管理功能時不能只檢查已登入，還必須指定最小權限，例如：

```php
->middleware('admin.permission:product.update')
```

## 8. 架構與 SOLID 實作方式

標準請求流程：

```text
Route
  -> Middleware / Guard / Permission
  -> FormRequest validation
  -> Controller interface orchestration
  -> Service domain/query logic
  -> Model + transaction + database constraints
  -> Resource or Blade response
```

### Controller

Controller 應處理：

- 接收已驗證 Request。
- 呼叫 Service。
- 選擇 Blade、redirect 或 JSON Resource。
- 將領域例外映射為適當 HTTP 回應。

Controller 不應處理：

- 重複計價。
- Stripe session 驗證。
- Ledger 寫入細節。
- 跨多張表的交易一致性。
- 可在 Web/API 共用的所有權查詢。

### FormRequest

相同契約才應共用 Request。例如 Cart 與 Shared Recipe 的商品選項都需要合法 size/temp 及 add-on 歸屬，因此共用 `ProductOptionsRequest`。Favorite 的歷史 API 契約較寬鬆，所以保留 API 專用 Request，不能為了消除外觀重複而改變行為。

### Service

Service 負責一項明確能力。例如：

- `CartPricingService`：重新讀取目前商品與選項價格。
- `CheckoutService`：交易鎖、庫存、訂單、Coupon、事件協調。
- `RefillInitiationService`：建立 Stripe 補值與 Pending PaymentEvent。
- `TransactionQueryService`：使用者範圍、搜尋及方向過濾。
- `ProductQueryService`：Web/API 共用商品詳情讀取。
- `LedgerService`：錢包不可變 Ledger 與餘額一致性。

當 Service 有多個實作或外部基礎設施依賴時，使用 `app/Contracts` 介面並在 `AppServiceProvider` 綁定。不要為只有一個簡單用途的類別機械式建立介面。

### Model 與資料庫

Model 可以保存 relation、cast、scope 及緊密相關的狀態規則。金額與資料完整性仍應有資料庫約束作最後防線。涉及 wallet、coupon、stock、order 或 payment 狀態變動時使用 `DB::transaction()`，必要時使用 row lock 或 cache lock。

## 9. 商品、購物車與價格流程

商品及附加項是價格來源。Cart 保存的單價只是顯示/暫存資料，不是結帳權威值。

加入購物車：

1. FormRequest 驗證 product、size、temp、quantity。
2. `ProductAddonSelection` 驗證 add-on 屬於該商品。
3. `CartService` 建立或更新 CartItem。

結帳：

1. 後端重新載入 CartItem 與 Product。
2. `CartPricingService` 以目前商品與附加項價格重新計算。
3. 驗證商品仍可購買、選項合法及庫存足夠。
4. 計算 Coupon、OZ 與最終金額。
5. 直接 Tangki 結帳在交易中建立訂單。
6. Stripe 結帳先建立 server-owned CartSnapshot。

新增商品選項時，至少檢查：

- `config/coffee.php`
- `ProductOptionsRequest`
- `ProductAddonSelection`
- `CartPricingService`
- `CartSnapshotService`
- `CheckoutService`
- Web Blade 與 API Resource
- Cart/Checkout/Stripe 測試

## 10. 訂單生命週期

訂單狀態不可由客戶端任意指定。合法轉移由 `OrderStateMachine` 與管理端操作控制，變動記錄在 `order_status_histories`。

取消訂單需要：

- 驗證訂單屬於目前使用者。
- 驗證狀態允許取消。
- 在交易中恢復庫存。
- 若已扣 Tangki/OZ，透過正規 Service/Ledger 退款。
- 保留 transaction、ledger 與 audit evidence。

歷史訂單使用 `order_items.product_name`、價格 cents、Coupon code/discount snapshot，避免商品改名或調價後舊訂單顯示改變。

## 11. 金流與 Tangki 安全模型

### Stripe 訂單付款

```text
Authenticated checkout request
  -> backend reprices cart
  -> server creates CartSnapshot
  -> server creates Stripe Checkout Session
  -> server records pending PaymentEvent
  -> user pays on Stripe
  -> Stripe sends signed webhook
  -> webhook verifies signature, status, currency, owner, type and amount
  -> StripeCheckoutHandler validates CartSnapshot amount
  -> CheckoutService creates order once
```

### Tangki 補值

```text
POST refill amount
  -> InitiateRefillRequest validates RM5-RM500 and 0-2 decimals
  -> RefillInitiationService converts to integer cents
  -> Stripe Checkout Session + pending PaymentEvent
  -> signed webhook confirms actual payment
  -> RefillHandler cross-validates paid amount
  -> TangkiService/LedgerService credits wallet once
```

Stripe success URL 只顯示結果，不得執行訂單建立或入帳。Webhook 必須保持 idempotent；重送同一事件不能產生第二筆訂單或第二次補值。

金額程式碼規則：

- 計算與比較優先使用 integer cents。
- 使用 `App\Support\Money` 轉換。
- 不直接以 float 比較金額。
- Ledger amount 必須大於零。
- 餘額不能因 debit 變成負數。
- PaymentEvent 的預期 owner/type/amount/currency 必須匹配 provider 結果。

## 12. Coupon、OZ、庫存與併發

Coupon 使用時必須重新檢查有效期、狀態、總使用限制、每人限制及最低消費。兌換在交易中完成，不能只依賴前端先前的 validate 結果。

OZ 與 Tangki 是不同資產，不可混用欄位或 Ledger type。任何 reward/refund 必須可追溯且具 idempotency key 或唯一業務參考。

庫存商品結帳時需要鎖定及原子扣減。取消時只恢復真正扣除過的庫存，避免重複取消增加庫存。

## 13. 新增功能的標準流程

以新增受保護 API 為例：

1. 在 `routes/api.php` 加入 `auth:sanctum` 內的 route。
2. 加入限流，若端點會改變付款、訂單、帳號或資源狀態。
3. 建立 FormRequest，明確列出型別、範圍、enum、exists 與長度限制。
4. Controller 使用 `$request->validated()`，不要使用未篩選的 `$request->all()`。
5. 將可重用業務規則放入單一職責 Service。
6. 所有查詢限制目前 user/owner；管理端使用 permission middleware。
7. 多表寫入使用 transaction，並評估 idempotency 和 row lock。
8. 使用 API Resource 維持穩定欄位，不直接回傳任意 Model。
9. 加入成功、validation、unauthenticated、forbidden、other-user、duplicate/concurrency 測試。
10. 更新 `docs/AI_API_PROVIDER_CONTRACT.md` 及任務狀態。

## 14. 測試策略

測試環境由 `phpunit.xml` 固定：

- SQLite `:memory:`
- array cache/session
- sync queue
- null broadcasting
- Telescope 關閉

完整測試：

```powershell
php artisan test
```

單一檔案：

```powershell
php artisan test tests/Feature/ApiProviderContractTest.php
```

依名稱篩選：

```powershell
php artisan test --filter="user cannot view another users order"
```

高風險修改最低測試範圍：

| 修改區域 | 必跑測試 |
|---|---|
| API shape | `ApiProviderContractTest`, `ApiErrorContractTest` |
| Cart/價格 | `ApiCartSecurityTest`, `CheckoutTest` |
| Stripe | `StripeWebhookTest`, `StripeMetadataTest`, `PaymentStatusApiTest` |
| Tangki/Ledger | `TangkiRefillApiTest`, `WalletLedgerTest` |
| 訂單所有權 | `ApiOrderTest`, transaction/refund tests |
| Admin 權限 | `AdminPermissionTest`, `AdminSecurityTest` |
| Product upload | `ProductAdminTest` |
| CORS/環境 | `CorsSecurityTest`, `SecurityCheckCommandTest` |

目前完整基準為 202 tests、806 assertions。新增功能後數字可以增加，但不應無理由減少。

## 15. 提交前驗證清單

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

正式部署前額外執行：

```powershell
php artisan coffee:security-check --production
composer audit --locked
php artisan migrate:status
```

檢查內容：

- 沒有 `.env`、secret、token、資料庫 dump、log 或使用者資料。
- API response shape 有測試和文件。
- Migration 可向前部署，不依賴開發資料。
- 新 route 有正確 guard、permission 及 throttle。
- 金額使用 cents，且 server 重新計算。
- 寫入具 transaction、idempotency 與 owner scope。
- 沒有修改 `vendor/`、`node_modules/` 或建置產物。

## 16. 正式部署

建議流程：

```powershell
composer install --no-dev --classmap-authoritative
npm.cmd ci
npm.cmd run build
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
php artisan coffee:security-check --production
```

部署帳號必須可寫入 `storage/` 與 `bootstrap/cache/`。`optimize` 已重建 config、event、route、view cache，不需要再逐一重複執行。

正式環境還需要：

- HTTPS 憑證及 HTTP 到 HTTPS redirect。
- Web server document root 指向 `public/`，不可指向 repository root。
- Queue worker 由 Supervisor/systemd 等 process manager 管理。
- 每分鐘執行 `php artisan schedule:run`。
- Stripe webhook endpoint 使用正式 HTTPS URL。
- Reverb 使用 WSS、反向代理及強 secret。
- `REVERB_ALLOWED_ORIGINS` 只列出正式 HTTPS origins，禁止 wildcard。
- 定期備份 database 與必要 storage，並實際測試 restore。
- 日誌輪替、failed jobs、PaymentEvent failure 與 5xx 監控。
- Telescope 正式環境原則上關閉；若啟用只允許授權 owner，且確認 prune scheduler 運作。

部署後 smoke test：

1. 公開 dashboard 可讀取。
2. API login 成功，錯誤密碼不洩漏帳號存在。
3. 其他使用者不能讀取訂單或交易。
4. 測試 Stripe payment/refill webhook 可處理且重送不重複入帳。
5. Admin 權限不足者得到 403。
6. CORS 只回應 allowlist origin。
7. HTTPS 回應具有 CSP、HSTS、X-Frame-Options、X-Content-Type-Options 等標頭。

## 17. 常見問題排查

### `php` command not found

使用 Laragon PHP 完整路徑，或把該 PHP 目錄加入目前 PowerShell PATH。

### `SQLSTATE[HY000] [2002]` / port 3306

MySQL 未啟動或 `.env` host/port 錯誤。啟動 Laragon MySQL，或改用 SQLite。修改 `.env` 後執行 `php artisan config:clear`。

### 修改 `.env` 沒有效果

```powershell
php artisan optimize:clear
```

若 cache/session 使用 database 且 database 未啟動，`optimize:clear` 可能在 database cache 步驟失敗；可逐一執行 `config:clear`、`route:clear`、`view:clear`。

### API 回傳 401

確認：

- Route 位於 `auth:sanctum` 群組。
- Header 是 `Authorization: Bearer ...`。
- Token 未被 logout/password change/裝置管理撤銷。
- `Accept: application/json` 已設定。

### API 回傳 403

通常是資源不屬於目前 user、admin permission 不足、friendship 不成立或 Telescope Gate 拒絕。不要把 403 改成無條件允許；先檢查 owner query 和 middleware。

### API 回傳 422

查看 JSON `errors`。檢查 FormRequest 的 enum、範圍、add-on 商品歸屬及 decimal 規則。不要只在 Controller 補 ad-hoc 驗證。

### Stripe webhook 不工作

依序檢查：

1. Stripe CLI 是否仍在 listen。
2. `STRIPE_WEBHOOK_SECRET` 是否為目前 CLI session 的 secret。
3. Endpoint 是否 `/api/stripe/webhook`。
4. `storage/logs/laravel.log` 與 PaymentEvent 狀態。
5. currency、paid status、owner、type 和 amount 是否匹配。

不要用 success redirect 代替 webhook。

### Queue 工作沒有執行

確認 `QUEUE_CONNECTION`，再啟動 `php artisan queue:work`。修改 job code 或 config 後重啟 worker：

```powershell
php artisan queue:restart
```

### Reverb 無法連線

確認 Reverb server、host/port/scheme、`REVERB_ALLOWED_ORIGINS`、前端環境及 `/api/broadcasting/auth`。使用者頻道必須是 `private-App.Models.User.{uuid}`。正式環境要確認反向代理支援 WebSocket upgrade。

### Route cache 失敗

執行 `php artisan route:list --except-vendor`，檢查重複 route name、Closure route 或錯誤 Controller。Web/API 相同資源名稱要使用不同 name prefix。

### Telescope 看不到或 403

確認 `TELESCOPE_ENABLED=true`、以 admin guard 登入、管理員具 `telescope.view` 權限且 Telescope Gate 允許。正確入口是 `/admin/telescope`。

## 18. Debug 與診斷工具

常用命令：

```powershell
php artisan about
php artisan route:list --except-vendor
php artisan migrate:status
php artisan queue:failed
php artisan schedule:list
php artisan pail
```

可使用 Telescope 查看 request、query、job 與 exception，但不要在輸出、截圖或 issue 中分享 token、cookie、個資、SQL 參數或支付 metadata。

## 19. Code Review 檢查重點

Review 應先找風險，而不是先看格式：

- 是否存在未限制 owner 的 `find()` / `findOrFail()`。
- 是否信任 request 的 price、amount、discount、role、status 或 payment success。
- 是否在 transaction 外分別更新餘額與 Ledger。
- 是否缺少 idempotency，導致重送建立重複訂單或補值。
- 是否在 Web/API Controller 複製同一領域規則而可能漂移。
- 是否把契約不同的類別強行共用，造成狀態碼或 validation 改變。
- 是否有 N+1、無上限 `get()`、未投影欄位或大集合記憶體風險。
- 是否新增公開 route、upload、CORS origin 或 debug surface。
- 是否有相應負向測試及 API contract 測試。

## 20. 完成定義

一項後端工作只有在以下條件全部滿足後才算完成：

- 已定位受影響 module、route、Controller、Request、Service、Model 與 schema。
- API、Web/Admin 行為及安全影響已說明。
- 沒有信任客戶端金額、角色、狀態、所有權或支付結果。
- 變更保持單一職責，沒有不必要或錯誤抽象。
- 針對性測試及完整測試通過。
- route/cache/security/build 等適用驗證通過。
- API 合約、架構決策與任務狀態文件已更新。
- 剩餘風險與未能在本機驗證的外部條件已明確記錄。

## 21. 延伸文件

- `docs/FULL_PROJECT_STARTUP.md`：從零初始化、完整服務啟動、Stripe/Reverb 聯調、部署、更新與故障恢復。
- `docs/FLUTTER_REALTIME_NOTIFICATION_INTEGRATION_REPORT.md`：Flutter typed 通知、Reverb、頁面刷新、導航與測試方案。
- `docs/AI_BACKEND_ARCHITECTURE_MAP.md`：實際模組及資料流。
- `docs/AI_API_PROVIDER_CONTRACT.md`：Flutter 所依賴的 API 合約。
- `docs/AI_SECURITY_BASELINE.md`：認證、授權、CORS、upload、debug 安全基線。
- `docs/AI_PAYMENT_WALLET_RULES.md`：付款、補值、Ledger 與 idempotency 規則。
- `docs/AI_ENVIRONMENT_BACKEND.md`：環境變數及部署差異。
- `docs/AI_BACKEND_VALIDATION_CHECKLIST.md`：修改後驗證命令。
- `docs/AI_BACKEND_DECISIONS.md`：歷史架構決策及 trade-off。

遇到文件與程式碼不一致時，以目前 route、Request、Service、migration 和測試結果為準，並在同一變更中修正文件。
