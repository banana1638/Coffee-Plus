# Coffee-Plus 安全评估报告

评估日期：2026-06-12
评估范围：本地 Laravel 项目 `C:\laragon\www\Coffee-Plus`
评估方式：授权范围内的静态代码审计、路由/配置检查、依赖漏洞审计、重点测试验证。未进行破坏性攻击、提权、持久化、横向移动或第三方系统测试。

## 执行摘要

本次评估发现 2 个需要优先处理的高风险问题、1 个中风险应用缺陷、若干配置加固项。已在本轮修复两处应用层问题：

- 已修复：通知“标记已读”由 GET 改为 POST，并在前端表单加入 CSRF。
- 已修复：Stripe webhook 的 `PaymentEvent` 模型批量赋值字段缺失，导致合法 webhook 处理 500。

依赖层风险仍需优先处理：`composer audit` 检出 20 条安全公告，涉及 `laravel/framework`、`phpoffice/phpspreadsheet`、`league/commonmark`、多个 Symfony 组件等。其中 `phpoffice/phpspreadsheet` 包含 critical/high 级别公告。

## 关键发现

### 1. PHP 依赖存在已知安全漏洞

风险等级：高
状态：未修复
证据：`composer audit --no-interaction` 检出 20 条安全公告。

重点受影响版本：

- `laravel/framework`：`v12.53.0`
- `phpoffice/phpspreadsheet`：`1.30.2`
- `league/commonmark`：`2.8.0`
- `symfony/http-foundation`：`v7.4.6`
- `symfony/mime`：`v7.4.6`
- `symfony/routing`：`v7.4.6`

影响：

- `phpoffice/phpspreadsheet` 存在 critical/high 级公告，包含 SSRF/RCE、CPU DoS 等类型。项目使用 `maatwebsite/excel` 导出订单报表，虽然当前主要是导出场景，但底层依赖仍应尽快升级。
- Laravel/Symfony 邮件、URL、HTTP 组件相关漏洞可能影响邮件地址校验、URL 生成、请求处理或 SSRF 防护边界。

建议：

- 执行 `composer update laravel/framework phpoffice/phpspreadsheet league/commonmark symfony/* --with-all-dependencies`，然后跑完整测试。
- 若升级跨度较大，先更新 `phpoffice/phpspreadsheet` 和 Laravel/Symfony 安全补丁版本。
- 将 `composer audit` 加入 CI，阻断 high/critical 依赖进入主分支。

### 2. Stripe webhook 付款事件记录失败

风险等级：高
状态：已修复
位置：`app/Models/PaymentEvent.php`

问题：

`payment_events` 表包含 `provider`、`event_id`、`type`、`amount_cents`、`currency`、`status` 等非空或业务关键字段，但 `PaymentEvent::$fillable` 未包含这些字段，并包含不存在的 `event_type`。`StripeWebhookController` 使用 `firstOrCreate()` 创建记录时字段被丢弃，导致合法 webhook 返回 500。

影响：

- 合法 Stripe 付款成功事件可能无法处理。
- 付款事件审计记录不完整。
- 重试/幂等判断依赖 `event_id`、`session_id`、`status`，字段丢失会削弱支付链路可靠性。

修复：

- 已更新 `PaymentEvent::$fillable`，补齐迁移表中的业务字段。

验证：

- `php artisan test tests\Feature\StripeWebhookTest.php tests\Feature\AdminSecurityTest.php tests\Feature\ApiOrderTest.php`
- 结果：14 个测试通过，45 个断言通过。

### 3. 通知标记已读使用 GET 改变状态

风险等级：中
状态：已修复
位置：`routes/web.php`、`resources/views/layouts/navigation.blade.php`

问题：

`/notifications/mark-all-as-read` 和 `/notifications/{id}/mark-as-read` 原本使用 GET 请求改变用户通知状态。浏览器预取、第三方页面诱导加载、或用户点击外部链接时，可能在用户登录状态下触发状态变更。

影响：

- 通知可能被非用户意图地标记为已读。
- 虽不属于资金或权限变更，但会影响用户对订单/系统消息的可见性。

修复：

- 路由从 GET 改为 POST。
- 导航通知下拉中的链接改为 POST 表单，并加入 CSRF token。

验证：

- `php artisan route:list --except-vendor` 确认通知标记路由均为 POST。

### 4. 本地环境配置存在泄露与误部署风险

风险等级：中
状态：需环境侧处理
位置：`.env`

发现：

- `.env` 未被 git 跟踪，`.gitignore` 已忽略 `.env`，这是正确的。
- 本地 `.env` 存在真实测试 Stripe key、固定 Reverb secret、`APP_DEBUG=true`、`APP_ENV=local`、`SESSION_ENCRYPT=false`。

影响：

- 若 `.env` 被误上传、截屏、备份或复制到生产，会造成密钥泄露和调试信息泄露。
- 生产环境开启 debug 会暴露堆栈、路径、环境信息，风险很高。

建议：

- 轮换本地已暴露过的 Stripe 测试密钥和 Reverb secret。
- 生产环境必须设置：`APP_ENV=production`、`APP_DEBUG=false`、`SESSION_ENCRYPT=true`、`SESSION_SECURE_COOKIE=true`、`REVERB_SCHEME=https`。
- 使用密码管理器或部署平台 secret store 管理环境变量。

## 正向安全控制

以下控制在本次检查中表现良好：

- 用户订单 API 使用 `auth:sanctum`，订单详情和取消操作按 `user_id` 过滤，已有 IDOR 测试覆盖。
- 管理后台使用 `auth:admin` 和 `admin.permission:*` 权限中间件，已有角色权限测试。
- 登录、注册、checkout、coupon validation、refill 等高频接口有 throttle。
- Stripe webhook 使用 `Stripe-Signature` 验签，并对重复 `session_id` 做处理。
- 上传商品图片使用 Laravel `image`、`mimes`、`max` 校验，并用 UUID 文件名降低路径/覆盖风险。
- `.env` 未被 git 跟踪。
- `npm audit --omit=dev` 未发现生产 npm 依赖漏洞。

## 测试与命令记录

已执行：

```powershell
git ls-files .env .env.example
npm.cmd audit --omit=dev
php artisan route:list --except-vendor
composer audit --no-interaction
php artisan test
php artisan test tests\Feature\StripeWebhookTest.php tests\Feature\AdminSecurityTest.php tests\Feature\ApiOrderTest.php
```

结果摘要：

- `npm audit --omit=dev`：0 个漏洞。
- `composer audit --no-interaction`：20 条安全公告，影响 10 个包。
- 全量测试：99 通过，2 失败。
- 失败项 1：`Tests\Feature\Auth\RegistrationTest` 期望 `/register` 重定向到 `/`，实际为 `/?auth=register`，看起来是测试期望与当前 SPA/modal 路由行为不一致。
- 失败项 2：`Tests\Feature\StripeWebhookTest` 因 `PaymentEvent::$fillable` 缺字段导致 500；已修复。
- 修复后重点测试：14 通过，45 断言通过。

## 优先整改计划

1. 立即升级 Composer 依赖，优先处理 `phpoffice/phpspreadsheet`、`laravel/framework`、Symfony 组件。
2. 轮换本地出现过的 Stripe 测试密钥和 Reverb secret。
3. 为生产部署建立 `.env` 安全基线，确保 debug、session、cookie、HTTPS 配置正确。
4. 将 `composer audit`、`npm audit --omit=dev`、重点 Feature tests 加入 CI。
5. 修正 `RegistrationTest` 的断言，使全量测试回到绿色。
6. 为通知 POST 路由补充 Feature test，防止未来回退为 GET。

## 结论

项目已有不错的认证、授权、幂等和订单隔离意识。当前最大风险不在“明显裸奔的接口”，而在依赖版本滞后和支付事件模型字段不一致。应用层两个可修问题已完成修复；下一步应尽快升级依赖并重新跑完整测试。
