# TCONGS — D2C E-commerce Platform

Custom-coded D2C jewellery/fashion storefront (reference: Estele.co) — Laravel + Filament backend, Blade/Tailwind storefront. Built against a phased developer spec (Foundation → Storefront → Commerce → Content → SEO → QA → Post-launch).

## Build status by phase

### Phase 1 — Foundation ✅

- Laravel 13 + Filament 5 admin panel (`/admin`), MySQL, Redis
- Spatie Permission RBAC — `super_admin` / `marketing` roles, real 403s enforced
- Spatie Media Library + Intervention Image, polymorphic SEO meta table
- Category/Product/Setting admin CRUD

### Phase 2 — Storefront Core ✅

- Home, Category, Collection, Product, Search — all DB-driven, zero hardcoded content
- Guest cart + checkout (COD), stock-safe with row locking
- Real search: Laravel Scout + MeiliSearch (not raw `LIKE`)
- Redis full-page/query caching, invalidated on admin save
- Responsive WebP image pipeline (4 size tiers per §3.1 spec)

### Phase 3 — Commerce Logic ✅

- **Payments:** Razorpay, real API (test mode) — order creation, signature-verified callback, webhook handling
- **Shipping:** flat-rate (live) + full Shiprocket client (auth/rate/create-shipment/track) behind a config toggle, ready for a real account
- **Coupons:** flat/% discounts, product/category/sitewide scope, expiry/min-cart/usage-limit rules, re-validated at checkout
- **Orders:** guarded status pipeline (Placed → Packed → Shipped → Delivered, branching Cancelled/Returned), auto-restock, on-demand PDF invoices, refund bookkeeping

### Phase 4 — Content & Trust ✅ (Store Locator excluded — pending scope confirmation)

- Blog + categories (publishing pipeline), CMS pages, FAQ
- Reviews & Ratings — customer submission, admin approve/reject
- Testimonials & USP/trust badges (via block-based Homepage Builder)
- Popups/announcements, newsletter signup capture

### Phase 5 — SEO & Performance Hardening ✅

- `BreadcrumbList` + `Organization` JSON-LD, auto-regenerating `/sitemap.xml`, hardened `robots.txt`, branded 404 page
- Admin-editable raw script injection (`tracking_head_scripts` / `tracking_body_scripts` Settings) — the hook point for GA4/GTM/Meta Pixel/Search Console verification, blank until a real snippet is pasted in
- Product JSON-LD (offers/aggregateRating/review), canonical tags, per-page-type meta, WebP + lazy + srcset images

### Phase 6 — QA & Launch ✅ (staging skipped — cost tradeoff, user's call)

- **Security audit:** dependency vuln patch (0 advisories), security response headers (X-Frame-Options/HSTS/etc.), JSON-LD XSS hardening, rate limiting on checkout/search, capped admin uploads
- **Load testing:** found the Railway container was running `php artisan serve` (Laravel's single-threaded dev server) in production — 20 concurrent visitors serialized to 7+ seconds. Fixed with a real nginx + php-fpm stack (`backend/docker/`); re-tested live: ~3.4s with genuine parallel handling, not serialized
- **Cross-browser/device testing:** full search → product → cart → checkout flow verified live. Known gap: true mobile-viewport screenshots aren't obtainable with the current tooling in this dev environment (window/device-emulation resize doesn't take effect) — functional correctness confirmed, visual mobile screenshots aren't
- **Staging → production cutover:** deliberately skipped — an extra always-on service isn't worth the ongoing cost at this stage; changes are tested locally + verified live via curl/real flows before merging instead

### Phase 7 — Post-launch readiness 🟡 in progress

- **Analytics / Search Console:** infrastructure already existed (Phase 5's script-injection Settings) and is verified working — just needs a real GA4/GTM/Meta Pixel snippet or Search Console verification tag pasted into Settings once those accounts exist
- **Error tracking:** Sentry SDK installed and wired into `bootstrap/app.php` (`sentry/sentry-laravel`) — inactive by default (no DSN set), becomes live the moment `SENTRY_LARAVEL_DSN` is set
- **Not done — needs a decision-maker with account access, not more code:**
  - Uptime monitoring (e.g. UptimeRobot free tier) — pure third-party account setup, no code involved
  - Actually creating the GA4 / Search Console / Sentry accounts to get real IDs/DSNs
  - `SESSION_SECURE_COOKIE=true` Railway env var (flagged since the Phase 6 security audit, still not set)
  - DB backup verification — Railway's backup feature is volume-based and should apply to the MySQL service, but needs checking/enabling in the Railway dashboard's Backups tab directly

---

## Verification

All Phase 3/4 business logic has automated test coverage — **179 tests, 452 assertions, all passing** (`php artisan test` from `backend/`; local dev environments without the PHP `gd`/jpeg build will see 8 unrelated media-upload tests fail — not a code defect). Covers coupon validation, order status transitions + refunds, Razorpay callback/webhook, Shiprocket shipment actions, blog publishing, reviews, popups, OTP login, wallet/rewards.

## Stack

Laravel 13 · Filament 5 · MySQL · Redis · Laravel Scout + MeiliSearch · Spatie Media Library · Razorpay · Shiprocket · Blade + Tailwind CSS + Alpine.js (no SPA framework on the public storefront, by design — SEO/speed first).
