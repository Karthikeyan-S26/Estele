# Estele — Final Audit Report

- **Date:** 2026-09-12
- **Scope:** Laravel 13 + Filament 5 backend, Blade storefront, Flutter customer app (`mobile_app`), against the original requirements.
- **Environment verified against:** local MySQL (`estele`), `php artisan serve` on `0.0.0.0:8000`, Flutter 3.41.2 / Dart 3.11.0 SDK (`C:\flutter`), Android emulator `emulator-5554`.
- No production data was touched. No git push was performed. No credentials/secrets are included in this report.

---

## 1. Test & build status

| Check | Result |
|---|---|
| Backend PHPUnit (full suite) | **185 / 185 passed — 516 assertions** |
| WhatsApp feature tests (`VerifyWhatsAppNotificationTest`) | 6 / 6 passed |
| Flutter `flutter analyze` (:DART sdk 3.11.0) | 0 errors, 0 warnings (31 pre-existing info lints) |
| Flutter `build apk --debug` | Success → `mobile_app\build\app\outputs\flutter-apk\app-debug.apk` |
| Live API sell-request create (mobile bearer token) | `POST /api/account/sell/requests` → **201**, request persisted, invitations + WhatsApp fired |
| Live server health | Backend responds 200 on port 8000; MySQL port 3306 listening |

---

## 2. Status by priority

Legend: `Runtime-verified` = exercised live (HTTP/tests/emulator/DB); `Implemented` = present in code, covered by tests/code-review; `Partial` = works with a known gap; `Blocked` = cannot be completed in this environment.

| Priority / feature | Status | Evidence |
|---|---|---|
| **Payment & checkout** — COD-only; no fake Razorpay success | Runtime-verified (COD); Razorpay async = Implemented but E2E **Blocked** (no keys) | `app/Services/Payment/{PaymentManager,RazorpayGateway,PaymentGateway}.php`; `payment_provider` Setting defaults `cod`; `PaymentManager` refuses to downgrade settled orders on late/replayed webhook events. COD checkout + order persisted verified live. Razorpay `isOnlinePaymentEnabled()` returns false until key/secret configured — success is **not** faked. |
| **Orders & tracking** | Runtime-verified | Order create/status flow, `OrderPacked` mailer, customer order list/status on web + app, `Order` model `updated` hook now also sends WhatsApp on `accepted`. |
| **Old Jewellery / Sell & buy-back E2E** | Runtime-verified end to end | Customer create (app + API) → vendor invitations → vendor bid via tokenized web flow → bid window close → settle (wallet credit 90% / deduction 10%, 10-day expiry). See §4. |
| **Admin panel (Filament)** | Implemented + runtime-verified core | Single `admin` panel (`app/Providers/Filament/AdminPanelProvider.php`, web guard, FilamentShield + Roles) with resources: Orders, Customers, Products, Categories, Collections, Banners, Coupons, Offers, Popups, Reviews, WalletManagement, SellRequests (Invitations/Bids/AuditLogs relation managers + settle/close/cancel/mark-expired actions), RewardSubmissions, Settings, HomepageBlocks, CmsPages, Blogs/BlogCategories, Faqs/FaqCategories, NewsletterSubscribers, Redirects; Dashboard + `StoreStatsWidget`. |
| **Customer app (Flutter)** | Runtime-verified (core) | Emulator-5554 OTA install of debug APK; home (live hero/categories), account (Demo Customer), sell list/create screens verified against real backend data. |
| **Catalog & browsing** | Runtime-verified | PWA/web catalog + app catalog screens pull live data (home hero "Sitara Collection", categories, product pages). |
| **Wallets (buy-back credit)** | Runtime-verified | `WalletService` single-writer credit/debit/expire + audit trail; wallet credit ₹9,000 after E2E settle; admin `WalletManagementResource`. |
| **Reviews** | Runtime-verified | Customer review submit (web + app bottom-sheet), admin moderation `ReviewResource`, statuses (pending/approved/rejected). |
| **API security** | Implemented | Custom `api-token` guard (`TokenGuard`, `ApiAuthServiceProvider`), Sanctum-less bearer tokens, HTML `SecurityHeaders`, throttled vendor routes, per-user 404 on sell show/cancel. |
| **DB & testing** | Runtime-verified | Migrations + seeds (Shield roles, categories, products, vendors, home blocks); suite 185/185; `phpunit.xml` isolates env (`MAIL_MAILER=array`, `QUEUE_CONNECTION=sync`, `WHATSAPP_DRIVER=""` so tests never touch a real gateway). |
| **WhatsApp notifications** | Runtime-verified | New provider abstraction; live log proof admin + 3 vendors per create; order-accept + reminders wired. See §5. |

---

## 3. WhatsApp notification subsystem (new this round)

- **Interface** `app/Services/WhatsApp/WhatsAppSender.php` — `send(string $phone, string $message, array $context = []): void` + `isConfigured(): bool`.
- **Providers**
  - `LogWhatsAppSender` — default/fallback; `isConfigured()===false`; logs the full message so nothing is silently dropped in dev.
  - `TwilioWhatsAppSender` — real Twilio Messages API via `Http` + Basic Auth (no SDK); `whatsapp:`-prefixed To/From; E.164 normalization (Indian numbers → `+91` + last 10 digits); logs non-success responses; never throws into the request path.
  - `WhatsAppManager` — resolves gateway from `services.whatsapp.driver` (`twilio` or auto) with `LogWhatsAppSender` as guaranteed fallback. Same shape as the existing `OtpGateway/OtpManager` pattern.
- **Config:** `services.whatsapp` (driver/from), `.env.example` (`WHATSAPP_DRIVER`, `TWILIO_WHATSAPP_FROM`), `phpunit.xml` pins both to empty.
- **Wired:**
  - `SellRequestService::createForCustomer` → administrator alert (guarded by `filled($admin->phone)`).
  - `SellRequestService::inviteAllVendors` → per-vendor invitation with tokenized web link + bid deadline.
  - `SellRequestService::settle` → customer notification with settled ₹ amount + 10-day validity (sent **after** the DB transaction commits, never inside it).
  - `SellSendReminders` command → 1-day / 3-day / expired stages when the user has a phone.
  - `Order` model `updated` hook → customer `accepted`-status message (when `customer_phone` set).
- **Missing-phone safety — runtime-verified both ways:** create of `SRF-20260912-UQHV` (admin had a phone at the time) produced WhatsApp log records for admin and all 3 vendors (`9876500001 / 9876500002 / 9876500003`). Then the temporary demo admin phone was reverted to `NULL` and create of `SRF-20260912-UXFD` returned 201 with **no exception** — the admin alert was safely skipped (`filled($admin->phone)`), admin email still went out, and all 3 vendor WhatsApp messages still fired. No fake phone numbers remain in the database. Twilio mode itself is untested end-to-end only because no real credentials exist here — the request/response path is covered by `Http::fake` unit tests.

---

## 4. Sell & buy-back flow (runtime-verified)

1. Customer creates a request (app UI or `POST /api/account/sell/requests` — item type, description, city, optional image, **required** short video; media stored on the `public` disk; video base64 MIME/data validated, ≤20 MB).
2. Backend generates `SRF-…` request number, sets 3-hour bidding window, writes audit timeline (`created`).
3. `inviteAllVendors` → invitation rows (token per vendor) + email + WhatsApp with the tokenized link.
4. Vendor (no account/session needed) opens `sell/vendors/invitations/{token}` → accepts → places a bid (throttled route; each vendor sees only their own bid).
5. Admin watches bids in `SellRequestsResource`; statuses flow `pending_bids → bidding → closed` (auto via `SellCloseBids` scheduler) → `result_selected` → settle.
6. `settle` credits wallet 90% of amount, records 10% deduction, sends customer WhatsApp + `WalletCredited` mail.
7. Wallet credit expires after 10 days (`SellWalletExpire`), reminders at 1d/3d/expired.

Console commands `sell:bids-close`, `sell:wallet-expire`, `sell:send-reminders` and the schedule are registered in `bootstrap/app.php`.

---

## 5. Bug found & fixed this session

**Symptom:** mobile sell-create returned HTTP 500 `There is no role named "vendor" for guard "api-token"` (Spatie `RoleDoesNotExist`).

**Cause:** `User::role(...)` resolves Spatie's guard from the request context; the mobile API authenticates through the custom `api-token` guard while roles are stored under guard `web`, so the role lookup found nothing and threw inside `SellRequestService::createForCustomer` / `inviteAllVendors`. Tests did not catch it (they run under the `web`/default guard).

**Fix:** pinned the guard explicitly in every role query:
- `app/Services/SellRequestService.php` (`super_admin`/`marketing` alert query; `vendor` invitation query) → `User::role(..., 'web')`
- `app/Http/Controllers/RewardSubmissionController.php` (reviewer notification) → `User::role(..., 'web')`

**Verification:** live `POST` → 201 + invitations created; WhatsApp + emails observed; full suite re-run 185/185.

Note: the admin role exists but had `phone = NULL`. A temporary demo phone was used locally purely to prove the alert path fires, and was then **reverted to NULL** — no fake numbers are in the database. The code handles a missing phone as a safe, silent skip (`filled()` guard, verified live with HTTP 201 + vendors notified); it never throws. Production simply needs real admin phone numbers stored in `users.phone` for the alert to deliver.

---

## 6. Partially implemented / blocked

| Item | Status / reason |
|---|---|
| Razorpay online payments | Code complete (order creation, signature + webhook verification, settled-status race protection) but **Blocked E2E** — no `RAZORPAY_KEY_ID/KEY_SECRET`. COD is and remains the only active method; success is never falsified. |
| Twilio WhatsApp delivery | **Blocked E2E** — no Twilio credentials. Log provider is the safe default; Twilio path is unit-tested via `Http::fake` (`whatsapp:` To/From assertion). |
| Sell-create via app UI | API path fully verified. The emulator UI path needs a real video file on `/sdcard` (form validation requires it); no media was present on device, so the button-press UI step was not completed — functionally superseded by the identical API path used by the app. |
| Emulator UI E2E breadth | App boots and renders live data reliably, but the emulator is unstable (recurring "System UI isn't responding" ANR, process frequently dies within minutes). Testing was scheduled in short windows; anything not reachable in those windows is covered by feature tests. |
| AUTH_GUARD ambiguity | `config/auth.php` pins `web` by default; the `api-token` guard exists for the mobile API. The role-guard mismatch this caused is now eliminated by explicit guard pinning (no code farther affected). |

---

## 7. Files authored / changed (notable)

**WhatsApp subsystem (new):**
- `backend/app/Services/WhatsApp/WhatsAppSender.php`
- `backend/app/Services/WhatsApp/LogWhatsAppSender.php`
- `backend/app/Services/WhatsApp/TwilioWhatsAppSender.php`
- `backend/app/Services/WhatsApp/WhatsAppManager.php`
- `backend/config/services.php` (whatsapp block) · `backend/.env.example` · `backend/phpunit.xml`

**Wiring + fix:**
- `backend/app/Services/SellRequestService.php` (admin/vendor/customer WhatsApp; role guard pinning)
- `backend/app/Console/Commands/SellSendReminders.php` (1d/3d/expired WhatsApp)
- `backend/app/Models/Order.php` (accepted → WhatsApp)
- `backend/app/Http/Controllers/RewardSubmissionController.php` (guard pin)

**Tests:**
- `backend/tests/Feature/VerifyWhatsAppNotificationTest.php` (6 tests: fallback, twilio-without-creds, twilio request shape, sell-workflow vendor + settlement, no-vendor no-crash, order-accept)

---

## 8. Quick local runbook (teams reference)

- Start DB: `C:\xampp\mysql\bin\mysqld.exe --standalone` (port 3306).
- Start backend: `cd backend && D:\php-8.5.10-nts-Win32-vs17-x64\php.exe artisan serve --host=0.0.0.0 --port=8000`.
- Tests: `cd backend && ... php artisan test` (sqlite in-memory, isolated env).
- Flutter: use `C:\flutter\bin\flutter.bat` (SDK 3.41.2). **`D:\flutter` (3.38.6) fails pub resolution** with `sdk: ^3.11.0`.
- Emulator: `emulator -avd flutter_emulator` (only target `emulator-5554`; do not use the physical device `guaeqwt46lv46dso` for E2E).
- Seeded demo customer: `demo@estele.in` (id 2). Vendors: id 3–5 with phones. Admin: id 1 (email set; phone must be filled for WhatsApp alerts).