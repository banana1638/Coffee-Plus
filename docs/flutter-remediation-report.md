# Flutter Remediation Report

Backend status: Laravel security remediations have been applied. The Flutter repository was not present locally under `C:\laragon\www`, so app code was not modified in this workspace.

## Required Flutter Changes

### 1. Checkout Idempotency

Flutter must keep sending:

```http
Idempotency-Key: <stable random key>
```

Do not move this key into the JSON body. Backend still accepts body fallback for legacy clients, but the contract is the header.

Retry behavior:

- same key + same payload: backend returns original completed response
- same key + changed payload: backend returns conflict
- missing key: backend returns validation error

### 2. Tangki Refill Flow

Endpoint:

```http
POST /api/tangki/refill
Authorization: Bearer <token>
Content-Type: application/json
```

Request:

```json
{"amount":50}
```

Response:

```json
{
  "status": "success",
  "redirect_url": "https://checkout.stripe.com/..."
}
```

Flutter behavior:

- open `redirect_url` with `url_launcher` or the existing browser mechanism
- show a pending state after launching Stripe
- do not increment local Tangki balance after session creation
- refresh `GET /api/tangki` after returning from Stripe
- display success only when the refreshed server balance or transactions confirm it
- cancelled or failed Stripe checkout must not increase balance

### 3. Cart Validation Contract

`POST /api/cart/add` now enforces:

- `quantity`: integer 1 to 20
- `size`: backend option name, currently `Regular` or `Large`
- `temp`: backend option, currently `Hot` or `Iced`
- `addons`: array of add-on names belonging to the selected product

Flutter should populate size, temp, and add-ons from `GET /api/dashboard` or `GET /api/products/{id}`, not unrelated hardcoded values.

### 4. Shared Recipe Validation Contract

`POST /api/recipes` enforces:

- recipient must be an accepted friend
- size/temp must be valid backend options
- add-ons must belong to the selected product
- remark max length is 1000 characters

Flutter should block invalid payloads before submit, but backend remains authoritative.

### 5. Password Update Token Rotation

If `POST /api/profile/password` returns a new `access_token`, Flutter must immediately store it in `flutter_secure_storage` and ensure Dio uses the new token for subsequent calls.

Pseudo-flow:

```dart
final response = await profileService.updatePassword(...);
final token = response['access_token'] ?? response['data']?['access_token'];
if (token is String && token.isNotEmpty) {
  await apiClient.persistAuthToken(token);
}
```

If no token is returned, Flutter should either keep the current token if backend confirms it remains valid, or gracefully force re-login.

### 6. Error Handling

Flutter should handle:

- `401`: clear token and show login
- `403`: show forbidden/no-permission state
- `409`: idempotency or business conflict; do not retry with same key after payload change
- `422`: display field validation errors
- `429`: show rate limit message and avoid immediate retry

## Backend Changes Relevant to Flutter

- Checkout idempotency header is explicitly supported and tested.
- Stripe webhook ignores unpaid, wrong-currency, invalid-mode, missing-user, duplicate event/session cases.
- Cart and shared recipe validation are stricter.
- Tangki refill remains webhook-driven; redirects never credit wallet.
- API contract is documented in `docs/api-contract.md`.

## Flutter Test Recommendations

- Checkout service sends `Idempotency-Key` header.
- Retry with same checkout key does not create duplicate UI order entries.
- Tangki refill opens Stripe URL and refreshes server balance.
- Cart service prevents invalid size/temp/add-ons before request.
- Password update persists returned new token.
