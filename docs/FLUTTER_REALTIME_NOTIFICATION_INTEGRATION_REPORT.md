# Coffee-Plus Flutter 实时通知配合改动报告

> 报告日期：2026-07-15
>
> 后端：`C:\laragon\www\Coffee-Plus`
>
> Flutter：`C:\Users\LOQ\Coffee-Plus-App`
>
> 范围：订单状态、取餐提醒、Stripe 付款结果、Tangki 补值结果的 Reverb 实时通知

> 2026-07-15 实施状态：本报告中的后端阶段已经完成并通过自动化测试；Flutter typed model、事件路由、页面刷新、点击导航、客户端去重和真机 Reverb 联调尚未实施。

### 后端已完成项目

- 用户私有广播频道已统一为 UUID：`private-App.Models.User.{uuid}`。
- database 与 broadcast 通知已统一使用 `notification_id/event/title/message/occurred_at/action/data` envelope。
- 已完成订单接受、制作中、可取餐、临近取餐、完成和取消事件。
- 已完成 Stripe checkout 成功/失败以及 Tangki 补值成功/失败事件。
- 取餐提醒使用每分钟 scheduler、行锁和 `pickup_reminder_sent_at` 幂等标记。
- Stripe Webhook 重送和重复提醒调度已有自动化测试，通知不会重复触发业务入账。
- 后端通知是业务状态变化后的提示；Flutter 仍必须重新读取订单、付款状态或 Tangki API。

## 1. 执行摘要

Coffee-Plus-App 已经具备 Reverb 基础设施，不需要从零重写：

- 已安装 `pusher_reverb_flutter: 0.0.4`。
- 已安装 `flutter_local_notifications: ^22.0.1`。
- 已有 Sanctum Bearer Token 私有频道认证。
- 已有 App lifecycle 重连。
- 已有 Android/iOS 本地通知初始化。
- 已有通知列表、未读数量、标记已读和批量删除 API。
- 已有 Tangki Stripe 付款状态轮询。

后端通知契约、UUID 私有频道、订单状态通知、Stripe/Tangki 结果通知及取餐提醒现已补齐。剩余工作集中在 Flutter 消费端：

1. Flutter 必须持续使用后端公开的用户 UUID 订阅，不得回退到数据库数字 ID。
2. Flutter 只读取 `message`，没有稳定的事件类型和 typed payload。
3. 收到通知后只更新未读数量，没有刷新订单、付款或 Tangki 数据。
4. 点击本地通知不会进入对应订单、Tangki 或付款状态页面。
5. Reverb 只能可靠覆盖 App 前台或进程仍存活的场景，不能替代 FCM/APNs 后台推送。
6. 生命周期处理没有在 background/paused 时主动断开，可能产生无效重连和电量消耗。
7. 重新订阅时缺少明确的 subscription/listener 去重和释放机制。

建议先建立稳定的后端通知契约，再修改 Flutter。客户端不得根据 Stripe redirect、WebSocket 连接状态或本地计时自行宣告付款、补值或订单完成。

## 2. 当前 Flutter 实现盘点

### 2.1 已安装依赖

`pubspec.yaml` 当前包含：

```yaml
pusher_reverb_flutter: 0.0.4
flutter_local_notifications: ^22.0.1
dio: ^5.10.0
flutter_secure_storage: ^10.3.1
```

项目刻意固定 `pusher_reverb_flutter` 0.0.4，因为注释说明 0.0.8 的内部重连循环无法取消，会与 App 自己的生命周期重连策略冲突。升级前必须重新验证连接释放、后台行为和重复订阅。

### 2.2 Reverb 初始化

`lib/main.dart` 在首帧之后调用：

```dart
await NotificationService().init();
```

优点：

- 不阻塞首屏渲染。
- NotificationService 为 singleton。
- 初始化失败不会阻止 App 启动。

风险：

- 用户已经登录但延迟初始化失败时，没有明显 UI 状态。
- 服务恢复完全依赖 lifecycle/auth listener，缺少手动诊断入口。

### 2.3 私有频道认证

`NotificationService` 使用：

```dart
authEndpoint: AppConfig.reverbAuthEndpoint
```

并从 Secure Storage 读取 Sanctum Token，发送：

```http
Authorization: Bearer <token>
Accept: application/json
```

后端认证端点为：

```http
POST /api/broadcasting/auth
```

该路由位于 `auth:sanctum` 下，并有每分钟 60 次 throttle。这个方向正确，Flutter 不需要使用 session cookie 或 CSRF。

### 2.4 当前订阅频道

Flutter 从 `/api/profile` 取得 `user.id`。后端 `UserResource` 将 UUID 输出为 `id`，所以 Flutter 实际订阅：

```text
private-App.Models.User.<user_uuid>
```

后端 `routes/channels.php` 也使用 UUID 比较：

```php
hash_equals((string) $user->uuid, (string) $id)
```

后端当前由 `User::receivesBroadcastNotificationsOn()` 统一返回 UUID 频道，`RealtimeBusinessNotification` 使用 Laravel notifiable channel 广播，不再手动拼接数据库数字 ID。因此 Flutter 应保持订阅上述 UUID 频道；API 仍刻意隐藏内部 ID。

### 2.5 当前事件监听

Flutter 监听 Laravel 通知广播事件：

```text
Illuminate\Notifications\Events\BroadcastNotificationCreated
```

收到数据后：

- 读取 `message`。
- 显示标题固定为 `Order Notification` 的本地通知。
- 把原始 payload 保存到 local notification payload。
- 调用 `updateNotificationCount()`。

当前没有：

- 事件类型分派。
- 订单列表刷新。
- 订单详情刷新。
- Tangki 余额刷新。
- 付款 session 状态刷新。
- 通知列表实时插入。
- 点击通知页面导航。

## 3. 后端必须先提供的稳定契约

Flutter 改动依赖后端先统一 payload。建议所有业务通知使用相同 envelope：

```json
{
  "notification_id": "uuid",
  "event": "order.preparing",
  "title": "Coffee is being prepared",
  "message": "Order CP-ABC123 is now being prepared.",
  "occurred_at": "2026-07-15T10:30:00+08:00",
  "action": {
    "type": "order_detail",
    "order_id": 123,
    "bill_id": "CP-ABC123"
  },
  "data": {
    "order_id": 123,
    "bill_id": "CP-ABC123",
    "order_status": "preparing"
  }
}
```

字段规则：

| 字段 | 必须 | 说明 |
|---|---:|---|
| `notification_id` | 是 | 用于客户端去重，建议与 database notification UUID 相同 |
| `event` | 是 | 稳定 machine-readable 事件名称 |
| `title` | 是 | 本地通知标题，不由 Flutter 猜测 |
| `message` | 是 | 用户可见文案 |
| `occurred_at` | 是 | ISO-8601，含 timezone |
| `action.type` | 是 | 点击后的导航类型 |
| `data` | 是 | 事件相关字段，不包含 secret 或敏感 Stripe payload |

### 3.1 建议事件名称

```text
order.accepted
order.preparing
order.ready_for_pickup
order.pickup_reminder
order.completed
order.cancelled
payment.checkout_succeeded
payment.checkout_failed
wallet.refill_succeeded
wallet.refill_failed
```

### 3.2 订单事件 payload

```json
{
  "event": "order.ready_for_pickup",
  "title": "Order ready",
  "message": "Your coffee is ready for pickup.",
  "action": {
    "type": "order_detail",
    "order_id": 123,
    "bill_id": "CP-ABC123"
  },
  "data": {
    "order_id": 123,
    "bill_id": "CP-ABC123",
    "order_status": "ready_for_pickup",
    "pickup_code": "PU123ABC",
    "pickup_time": "2026-07-15T11:00:00+08:00"
  }
}
```

`pickup_code` 只发送给订单拥有者的私有频道。不要发送内部 user ID、Stripe secret、完整 PaymentEvent payload 或管理员信息。

### 3.3 Stripe 付款事件 payload

```json
{
  "event": "payment.checkout_succeeded",
  "title": "Payment confirmed",
  "message": "Payment for order CP-ABC123 has been confirmed.",
  "action": {
    "type": "order_detail",
    "order_id": 123,
    "bill_id": "CP-ABC123"
  },
  "data": {
    "session_id": "cs_...",
    "payment_status": "processed",
    "order_id": 123,
    "bill_id": "CP-ABC123"
  }
}
```

成功事件只能在 Stripe signature、paid status、currency、owner、type、amount 和幂等处理全部成功后发送。

失败事件应区分：

- 用户支付失败或 session expired。
- Stripe 已付款但后端处理失败。
- 不可信或不匹配的 callback 被忽略。

对于内部处理失败，用户文案应为“付款确认处理中，请稍后刷新”，不要泄漏 exception、SQL 或 metadata。

### 3.4 Tangki 补值事件 payload

```json
{
  "event": "wallet.refill_succeeded",
  "title": "Tangki refill complete",
  "message": "RM25.00 has been added to your Tangki.",
  "action": {
    "type": "tangki"
  },
  "data": {
    "session_id": "cs_...",
    "bill_id": "TOPUP-...",
    "amount_cents": 2500,
    "currency": "myr",
    "payment_status": "processed"
  }
}
```

Flutter 可以显示金额，但最终余额仍必须重新请求 `/api/tangki`，不能用旧余额加上 `amount_cents` 得出新余额。

## 4. Flutter 必须修改的文件

### 4.1 新增 typed 通知模型

建议新增：

```text
lib/models/realtime_notification.dart
```

职责：

- 解析 database notification 和 Reverb broadcast 两种 envelope。
- 对未知事件提供安全 fallback。
- 规范 `orderId`、`billId`、`sessionId`、`amountCents`。
- 解析 ISO-8601 时间。
- 不因新增字段而崩溃。

建议接口：

```dart
enum RealtimeNotificationEvent {
  orderAccepted,
  orderPreparing,
  orderReadyForPickup,
  orderPickupReminder,
  orderCompleted,
  orderCancelled,
  paymentCheckoutSucceeded,
  paymentCheckoutFailed,
  walletRefillSucceeded,
  walletRefillFailed,
  unknown,
}

class RealtimeNotification {
  final String id;
  final RealtimeNotificationEvent event;
  final String title;
  final String message;
  final DateTime? occurredAt;
  final String? actionType;
  final int? orderId;
  final String? billId;
  final String? sessionId;
  final int? amountCents;
  final Map<String, dynamic> rawData;
}
```

不要在 UI 到处直接使用 `Map<String, dynamic>` 和字符串 key。

### 4.2 重构 NotificationService 的事件分派

文件：

```text
lib/services/notification_service.dart
```

当前 `_onNotificationReceived` 只读取 message。建议改为：

1. 标准化 package 返回的 `Map` 或 JSON String。
2. 建立 `RealtimeNotification`。
3. 根据 `notification.id` 去重。
4. 使相关 API cache 失效。
5. 更新全局 notification stream/notifier。
6. App 前台时根据产品策略显示 in-app banner 或 local notification。
7. 保存点击导航 payload。

事件对应刷新策略：

| 事件 | Flutter 动作 |
|---|---|
| `order.*` | 清除 order/transaction cache，通知订单页面刷新 |
| `payment.checkout_succeeded` | 停止对应 session 轮询，重新请求订单 |
| `payment.checkout_failed` | 保持后端 status 查询入口，显示可重试提示 |
| `wallet.refill_succeeded` | 清除 Tangki/transaction cache，重新请求 `/tangki` |
| `wallet.refill_failed` | 停止等待状态，显示失败但不修改余额 |

建议新增广播流：

```dart
final StreamController<RealtimeNotification> _events =
    StreamController<RealtimeNotification>.broadcast();

Stream<RealtimeNotification> get events => _events.stream;
```

页面只订阅自己关心的事件，且必须在 `dispose()` 取消 subscription。

### 4.3 修复生命周期策略

当前只在 `resumed` 时重连，没有在 `paused`、`inactive`、`detached` 时主动停止。

建议：

```dart
switch (state) {
  case AppLifecycleState.resumed:
    connectAndSubscribe();
  case AppLifecycleState.inactive:
  case AppLifecycleState.paused:
  case AppLifecycleState.hidden:
  case AppLifecycleState.detached:
    disconnectWithoutClearingUser();
}
```

要求：

- 断线时取消 reconnect timer。
- 取消旧 channel listener。
- 同一 user/channel 只能存在一个 subscription。
- 成功连接后重新订阅一次。
- 登出后清除 user UUID、去重缓存和 pending navigation。
- Token rotation 后重新建立 authorizer/subscription。

### 4.4 处理重复连接和重复事件

建议保存：

- 当前连接状态 enum。
- 当前 socket ID。
- 当前 channel subscription。
- 当前 event StreamSubscription。
- 最近 100 个 notification UUID 的 bounded set。

不要依赖随机 local-notification ID 进行业务去重。Webhook 重试、queue retry、Reverb reconnect 都可能造成重复接收。

### 4.5 NotificationScreen 使用 typed model

文件：

```text
lib/screens/user/notification_screen.dart
```

建议改动：

- 不再只显示固定铃铛 icon。
- 根据 event 显示订单、取餐、付款、Tangki 图标。
- 显示 title + message，而不是只有 message。
- `order.ready_for_pickup` 突出 pickup code。
- `wallet.refill_succeeded` 显示 `RM amount`，但余额来自 API 刷新。
- 页面打开期间实时插入新通知，或收到 event 后 force refresh。
- 点击卡片先标记已读，再执行 action navigation。
- 未知事件仍显示通用通知，不崩溃。

建议 icon 映射：

| Event | Icon/语义 |
|---|---|
| accepted | receipt/order accepted |
| preparing | coffee/making |
| ready | notifications active/pickup |
| reminder | schedule/alarm |
| completed | check circle |
| cancelled/failed | error/cancel |
| payment succeeded | verified/payment |
| Tangki refill | account balance wallet |

### 4.6 通知点击导航

当前 `onDidReceiveNotificationResponse` 只写 log。需要加入全局导航能力。

建议在 `MaterialApp` 设置：

```dart
final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();
```

并根据 `action.type` 导航：

```text
order_detail -> 请求 GET /api/orders/{order_id} 后打开 OrderDetailScreen
tangki       -> 打开 /tangki 并强制刷新
notifications -> 打开 /notifications
```

不要把整份订单数据放进通知 payload 后直接显示，因为通知到达后订单状态可能已经再次变化。点击时必须重新请求后端。

需要处理 App 尚未初始化的情况：

- 保存 pending action。
- 登录状态确认后再执行。
- Token 已失效则先进入登录流程。
- 订单不是本人时后端返回 404/403，Flutter 显示通用错误。

### 4.7 OrderHistoryScreen 和 OrderDetailScreen 实时刷新

文件：

```text
lib/screens/user/order_history_screen.dart
lib/screens/user/order_detail_screen.dart
```

建议：

- `OrderHistoryScreen` 订阅所有 `order.*` 事件并 debounce refresh。
- `OrderDetailScreen` 只处理相同 `order_id` 的事件。
- 收到事件后调用 API，不直接把 payload status 写入 UI。
- 页面关闭时取消 StreamSubscription。
- 连续 pending -> preparing -> ready 事件应合并重复请求，避免 request storm。

现有订单状态已支持：

```text
pending
preparing
ready_for_pickup
completed
cancelled
```

建议 Flutter 建立 enum/parser，未知状态显示 fallback，不使用散落在 Widget 内的 string switch。

### 4.8 TangkiScreen 与付款轮询协作

文件：

```text
lib/screens/user/tangki_screen.dart
lib/services/payment_service.dart
```

现有 TangkiScreen 在外部 Stripe 页面返回 App 后轮询 `/api/payments/{sessionId}/status`，这个机制必须保留。Reverb 只能作为加速提示，不能替代 status API。

建议流程：

```text
Stripe external checkout
  -> App resumed
  -> status API polling starts
  -> Reverb refill_succeeded may arrive
  -> immediately re-check status API
  -> only processed response clears pending state
  -> refresh /api/tangki
```

如果 Reverb 没有连接，轮询仍能完成流程；如果轮询结束但 webhook 稍后到达，Reverb 可以触发第二次 API refresh。

### 4.9 ApiService cache invalidation

当前收到 Reverb 事件只执行 `updateNotificationCount()`。建议增加公开的精确 invalidation 方法：

```dart
void invalidateOrders();
void invalidateTransactions();
void invalidateTangki();
void invalidateNotifications();
```

不要为了一个订单事件清空所有图片、dashboard 和 profile cache。

### 4.10 登录、登出和 Token rotation

现有 NotificationService 监听 `authStateNotifier`，但 auth 状态只是 bool。密码更新会签发新 Token，如果 bool 保持 true，连接可能继续使用旧 token 直到下次鉴权。

建议 ApiClient 增加 auth session generation 或 token-changed notifier：

```dart
ValueNotifier<int> authSessionGeneration
```

以下动作增加 generation 并触发 Reverb 重连：

- 登录成功。
- 注册成功。
- 密码更新取得新 token。
- 当前设备 token 被撤销。
- 401 清除认证。
- 登出。

日志不得打印完整 Token、auth response 或私人频道 payload。

## 5. Reverb 与后台推送的边界

### 5.1 Reverb 能可靠覆盖

- App 在前台。
- App 进程仍存活且平台允许 socket 活动。
- 即时更新订单页面。
- 即时更新未读数量。
- 前台显示 local notification/banner。

### 5.2 Reverb 不能保证

- Android App 被系统强制停止。
- iOS App 被 suspend 或杀死。
- 手机长时间锁屏。
- 网络切换期间必达。
- 用户离线后的通知立即弹出。

Database notification 是离线补偿来源。App 每次登录、resume 或进入通知页面都应从 API 同步。

如果目标包括真正后台推送，需要第二阶段加入：

- Firebase Cloud Messaging for Android。
- APNs，通过 Firebase 或直接 APNs for iOS。
- 后端保存每个设备的 push token。
- Token rotate/revoke/失效清理。
- Laravel Notification 增加 push channel。
- 推送 payload 仍只带 action identifier，不携带敏感数据。

Reverb 和 FCM/APNs 不冲突：Reverb 负责前台实时 UI，Push 负责后台唤醒和系统通知栏。

## 6. Flutter 环境配置

当前 App 使用 `--dart-define-from-file`，本机示例应保持：

```json
{
  "COFFEE_API_BASE_URL": "http://192.168.1.120/Coffee-Plus/public/api",
  "COFFEE_PUBLIC_ORIGIN": "http://192.168.1.120/Coffee-Plus/public",
  "COFFEE_STORAGE_ORIGIN": "http://192.168.1.120/Coffee-Plus/public",
  "COFFEE_REVERB_ENABLED": true,
  "COFFEE_REVERB_HOST": "192.168.1.120",
  "COFFEE_REVERB_PORT": 8080,
  "COFFEE_REVERB_APP_KEY": "same-as-backend-reverb-app-key",
  "COFFEE_REVERB_TLS": false,
  "COFFEE_REVERB_AUTH_ENDPOINT": "http://192.168.1.120/Coffee-Plus/public/api/broadcasting/auth"
}
```

要求：

- API、auth endpoint 和 Reverb host 必须指向同一台可达服务器。
- 后端 `REVERB_ALLOWED_ORIGINS` 必须包含实际 Web/Flutter Web origin；正式环境禁止 `*`。原生 Android/iOS WebSocket 通常没有浏览器 Origin，但仍应按实际 client 和 proxy 行为联调。
- Flutter 只需要 app key，不需要 app secret。
- App secret 绝对不能放进 Flutter。
- Android 本机 HTTP 测试需要允许 cleartext；正式环境必须 HTTPS/WSS。
- iOS 本机 HTTP 默认受 ATS 限制，不应永久开启 arbitrary loads；建议使用可信开发 TLS 或按开发 host 最小化 exception。

运行示例：

```powershell
flutter run --dart-define-from-file=.env.local.json
```

## 7. 后端配合改动清单

Flutter 开发前，Coffee-Plus 后端至少应完成：

1. 用户广播频道统一为 UUID。
2. Notification payload 统一 event/title/message/action/data envelope。
3. OrderObserver 在真实状态 transition 后发送 accepted/preparing/ready/completed/cancelled。
4. 通知在 DB transaction commit 后排队，避免 rollback 后仍广播。
5. Stripe 付款成功只在 webhook 完整验证并处理成功后发送。
6. Tangki refill success 只在 Ledger、余额、transaction 和 PaymentEvent 完成后发送。
7. Payment failure 定义清楚 provider failure、expired 和 backend processing failure。
8. Pickup reminder 使用 scheduler 和幂等标记，避免每分钟重复通知。
9. Notification API payload 与 Broadcast payload 使用同一个 serializer/source。
10. Queue worker 和 Reverb server 作为必要 runtime process 运行。

### 7.1 “订单已接受”的状态映射

当前后端没有独立 `accepted` 状态。建议第一版不要新增数据库状态，而是在订单真正创建并进入 `pending` 后发送 `order.accepted`：

- Tangki/direct checkout：订单 transaction 成功提交后。
- Stripe checkout：Webhook 验证并创建订单后。

以后若业务需要店员手动接受/拒绝订单，再单独设计 `accepted` 状态迁移和状态机，不应只为通知文案临时改变状态流。

### 7.2 即将超过取餐时间

建议后端 scheduler 每分钟扫描：

```text
status = ready_for_pickup
pickup_time is not null
pickup_time <= now + reminder_window
pickup_reminder_sent_at is null
```

在 transaction 中锁定订单并写入 `pickup_reminder_sent_at`，然后 after-commit 发送通知。Flutter 不负责本地计算逾期时间，否则 timezone、状态更新和离线场景会产生错误提醒。

## 8. 建议 Flutter 实施阶段

### Phase 1：契约与连接可靠性

- 后端修复 UUID 频道。
- 新增 RealtimeNotification model。
- NotificationService typed parsing。
- subscription/listener 去重和释放。
- lifecycle background disconnect。
- auth token rotation reconnect。

完成标准：私有频道 200 授权、单连接、单事件只处理一次。

### Phase 2：页面实时刷新

- NotificationScreen typed UI。
- OrderHistoryScreen event refresh。
- OrderDetailScreen 只刷新相同订单。
- TangkiScreen refill event + status API 协作。
- 精确 cache invalidation。

完成标准：状态变化无需手动 pull-to-refresh，且数据最终来自 API。

### Phase 3：点击导航和离线补偿

- Global navigator/pending action。
- order/tangki action routes。
- resume 时同步 notification/order/payment 状态。
- database notification 去重。

完成标准：点击通知进入正确页面，过期/无权资源安全失败。

### Phase 4：真正后台推送

- FCM/APNs。
- device push token API。
- push token 生命周期。
- 前台 Reverb 与后台 Push 去重。

完成标准：App 被系统挂起时仍能收到系统通知，点击后从后端读取最新状态。

## 9. 测试计划

### 9.1 Dart 单元测试

新增：

```text
test/realtime_notification_test.dart
test/notification_router_test.dart
test/notification_deduplicator_test.dart
```

测试案例：

- 每一种 event payload 正确解析。
- 未知 event 不崩溃。
- 缺少可选 order/payment 字段时 fallback。
- `amount_cents` 不使用 float。
- 同 notification UUID 只处理一次。
- action 只允许 allowlist route。
- 恶意/错误 JSON 不执行导航。

### 9.2 Widget 测试

- 不同事件显示正确 icon/title/message。
- unread/read 样式。
- ready notification 显示 pickup code。
- refill 显示金额。
- 点击 order action 调用订单详情导航。
- 未登录点击通知进入登录或安全忽略。

### 9.3 Service 测试

- 登录后连接一次并订阅一次。
- logout 后断开且不重连。
- pause 后取消 timer/connection。
- resume 后指数退避重连。
- token rotation 后重新鉴权。
- 连续断线不会创建多个 timer。
- Reverb event 使正确 cache 失效。
- Reverb 不可用时 API notification 和 payment polling 仍可用。

### 9.4 端到端测试矩阵

| 场景 | 预期 |
|---|---|
| 创建 Tangki/direct order | 收到 `order.accepted`，订单列表刷新 |
| Stripe checkout 成功 | Webhook 后收到 payment success + accepted，不能在 redirect 时提前成功 |
| Admin pending -> preparing | 收到 preparing，详情重新请求 |
| Admin preparing -> ready | 收到 ready + pickup code |
| 接近 pickup time | 只收到一次 reminder |
| Admin ready -> completed | 收到 completed，review capability 由 API 决定 |
| User cancel pending | 收到 cancelled，退款和余额重新请求 |
| Tangki refill success | 收到 refill success，余额从 `/tangki` 刷新 |
| Webhook 重送 | 不重复入账；客户端同 UUID 不重复弹出 |
| Other user event | 无法授权/接收 |
| Token revoked | Reverb auth 401，App 清除 session |
| Reverb offline | API 和 payment polling 仍正常 |

## 10. 本机联调步骤

后端至少运行：

```powershell
php artisan serve --host=0.0.0.0 --port=8000
php artisan reverb:start --debug
php artisan queue:work --tries=3
php artisan schedule:work
```

如果通过 Laragon Apache 提供 HTTP，只需另外运行 Reverb、queue 和 scheduler。

Flutter：

```powershell
flutter run --dart-define-from-file=.env.local.json
```

观察点：

- Reverb terminal 显示连接与消息。
- `/api/broadcasting/auth` 返回 200。
- Channel 名称使用 user UUID。
- Queue job 成功，无 failed job。
- notifications 表产生一条记录。
- Flutter 只显示一次 local notification。
- 点击后重新请求对应 API。

## 11. 安全要求

- Flutter 永远不保存 `REVERB_APP_SECRET`。
- 私有频道 auth 必须使用当前 Sanctum Token。
- 用户只可订阅自己的 UUID channel。
- 不使用客户端 user ID 决定服务端通知接收者。
- Notification payload 不包含 password、token、完整 Stripe object、个人资料或内部 exception。
- 付款和余额成功状态必须来自后端确认。
- App 收到事件后重新读取 API，不以 payload 直接修改余额或订单终态。
- 生产使用 WSS/HTTPS 和明确 allowed origins。
- 日志遮蔽 socket ID、Token、session ID 和敏感 payload。
- Action navigation 使用 allowlist，不接受后端任意 route 字符串。

## 12. 验收标准

功能只有满足以下条件才算完成：

- 五个订单阶段通知均来自后端真实状态变化。
- pickup reminder 由 scheduler 产生且幂等。
- Stripe success/failure 不由 redirect 页面决定。
- Tangki success 发生在 Ledger 和 PaymentEvent 成功之后。
- Flutter 私有频道使用 UUID 并通过 Sanctum 授权。
- App 前台实时更新，Reverb 中断时 API fallback 仍有效。
- 通知点击进入正确页面并重新读取最新数据。
- 重复 webhook、queue retry 和 reconnect 不产生重复用户提示。
- App background/terminated 的限制已明确；若要求必达，FCM/APNs 已加入。
- Flutter analyze、unit/widget tests 和后端 notification/payment tests 全部通过。

## 13. 最终建议

建议第一轮不要立即加入 FCM/APNs，也不要修改订单状态机。先完成：

1. 后端 UUID 频道和统一 payload。
2. Flutter typed notification model。
3. 生命周期、重连和去重修复。
4. 订单/Tangki 页面按事件重新请求 API。
5. 通知点击导航。
6. 完整 Reverb 前台联调。

完成后再把同一个通知契约扩展到 FCM/APNs。这样可以避免同时调试 WebSocket、原生推送和业务状态三个复杂层面。
