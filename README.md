# Estele — D2C Jewellery Platform

Single-project D2C jewellery/fashion storefront (reference: Estele.co): one Laravel-13 backend serves a **Blade + Tailwind web storefront**, a **Filament 5 centralized admin dashboard**, and the **Flutter Android mobile app** through one shared **Laravel REST API** — all sharing a single MySQL database.

`AUDIT_REPORT.md` in the repo root contains the full handoff audit (implemented / runtime-verified / partial / blocked, verified test counts, changed files).

## Architecture at a glance

```
backend/      Laravel 13 + Filament 5 — web storefront, REST API, scheduler, mail
mobile_app/   Flutter (Android-first) customer app, talks to the same REST API
AUDIT_REPORT.md  Full implementation & verification audit
```

- **One backend, one database.** The web storefront, admin panel, and mobile API are not separate systems — they read/write the same MySQL database through the same models/services.
- **Web storefront** (« `routes/web.php`): Blade + Tailwind CSS + Alpine.js, no SPA framework on the public site (SEO/speed first). The existing storefront is kept in full — the mobile app complements it, it does not replace it.
- **Admin dashboard** (`/admin`): Filament 5 panel with resources for orders, customers, products, categories, collections, banners, coupons, offers, popups, reviews, wallet management, sell/buy-back requests (invitations, bids, audit log, settle/close/cancel/mark-expired actions), reward submissions, settings, homepage blocks, CMS pages, blogs, FAQs, newsletter subscribers, redirects; plus roles/permissions (FilamentShield) and a dashboard store-stats widget.
- **REST API** (« `routes/api.php` »): custom `api-token` auth guard (opaque bearer tokens), JSON responses; home/catalog/cart/checkout/account endpoints used by the mobile app.

## Stack

Laravel 13 · Filament 5 · MySQL · Spatie Permission & Media Library · Intervention Image · Redis (optional cache) · Blade + Tailwind + Alpine · Flutter 3.41 / Dart 3.11 (`mobile_app`) · Razorpay SDK and Shiprocket/Twilio/VAS-SMS clients behind config toggles.

## Core modules

- **Catalog & storefront:** home, category, collection, product, search, CMS pages, blog, FAQ, popups, newsletters — all DB-driven.
- **Cart & checkout:** guest cart, COD (default), stock-safe ordering with row locking, coupon validation re-checked at checkout, guarded order status pipeline (Placed → Packed → Shipped → Delivered, branching Cancelled/Returned), auto-restock, PDF invoices.
- **OTP login:** mobile-OTP login with abstracted gateway (`LogOtpGateway` default; VAS Multimedia / Twilio behind config). No hardcoded credentials.
- **Wallet (buy-back credits):** single-writer credit/debit/expire with audit trail; credit valid for a fixed window, then swept.
- **Old Jewellery — vendor bidding workflow:**
  1. Customer submits a sell/buy-back request (web or mobile API): item type + description + city + optional image + **required short video**.
  2. Backend creates the request with a 3-hour bidding window and invites every `vendor` user — each invitation carries a tokenized web link (the token IS the auth; vendors need no account/session).
  3. Vendors open the link, accept, and place bids (throttled; each vendor sees only their own bid).
  4. Bids close automatically (`sell:bids-close`), admin selects a result and settles.
  5. Settlement credits the customer's wallet (90/10 credit/deduction split) and notifies them; credit expires after 10 days (`sell:wallet-expire`), with 3-day/1-day/post-expiry reminders (`sell:send-reminders`).
  - Scheduler is registered in `bootstrap/app.php`; commands: `sell:bids-close`, `sell:wallet-expire`, `sell:send-reminders`.
- **WhatsApp notifications:** provider abstraction (`App\Services\WhatsApp\WhatsAppManager` + `LogWhatsAppSender` / `TwilioWhatsAppSender`). Wired into sell-request creation (admin alert), vendor invitations, settlement, wallet-expiry reminders, and order-accepted status. With no provider configured it falls back to a log sender — notifications are never faked or silently dropped. Phone numbers come from `users.phone` / `orders.customer_phone` (database) and the Twilio From number from config/environment; nothing is hardcoded.
- **Reviews:** customer submission, admin approve/reject.
- **SEO & analytics hooks:** sitemap, JSON-LD, per-page meta, admin-editable script injection slots (blank until a real GA/GTM/pixel snippet is pasted), Sentry DSN-gated error tracking (inactive by default).

## Verification (current)

- Backend test suite: **185 / 185 passed — 516 assertions** (`php artisan test` from `backend/`).
- WhatsApp feature coverage: `tests/Feature/VerifyWhatsAppNotificationTest.php` — 6 tests (fallback, twilio-details fallback, twilio `whatsapp:` request shape, sell-workflow vendor + settlement, no-vendor no-crash, order-accept).
- Flutter: `flutter analyze` → 0 errors, 0 warnings (31 pre-existing info lints); `flutter build apk --debug` succeeds.
- Live runtime-verified: checkout (COD), orders, sell-request create via mobile API (201) with vendor invitations + admin/vendor WhatsApp log records, settle → wallet credit, missing-phone safe-skip (no exception, notification skipped or logged).

## Honest remaining external blockers

- **Razorpay real delivery E2E — NOT verified.** Integration is code-complete and tested against faked HTTP, but checkout stays COD-only (online-payment radio hidden) until real `RAZORPAY_KEY_ID/KEY_SECRET` are set and `payment_provider` is switched. Success is never faked.
- **Twilio / VAS-SMS real delivery E2E — NOT verified.** OTP and WhatsApp paths are wired and unit-tested, but real delivery needs live credentials (`TWILIO_*` / `VAS_SMS_*` / `WHATSAPP_DRIVER` + `TWILIO_WHATSAPP_FROM`).
- **Emulator stability:** the dev emulator frequently dies (ANR dialogs); the app is verified on momentary windows and otherwise covered by feature tests.
- **Mobile-UI screenshot coverage:** functional flows verified live, but this tooling cannot capture true mobile-viewport screenshots.
- **Staging:** deliberately skipped (cost tradeoff); changes are tested locally and verified live before release.

## Local setup (quick)

- Database: MySQL on port 3306 (database `estele`).
- Backend: `cd backend && php artisan serve --host=0.0.0.0 --port=8000` (migrations + seeds apply).
- Mobile app: `cd mobile_app && flutter run` — API base URL in `lib/config/app_config.dart` (dev default `http://10.0.2.2:8000`). Use a Flutter SDK that satisfies `pubspec.yaml`'s `sdk: ^3.11.0` (Dart 3.11+).
- Tests: `cd backend && php artisan test` (sqlite in-memory, isolated env — never touches a real gateway or database).

Environment variables with real credentials live **only** in the uncommitted `backend/.env` — `.env.example` ships placeholders. Never commit keys, tokens, passwords, or keystores.