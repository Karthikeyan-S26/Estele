# REPORT A — Company Repository Update (Phase 4)

**Audited:** `company/main` @ `ef23844b797bbc099ee895d7718e391fae80a665`
**Author:** ayushman1413, **Date:** Sep 14 21:10:24 2026 +0530
**Latest head commit:** `fix(account): move order status tiles below the account menu`
**Scope:** 194 company commits not in local; 529 files changed vs local HEAD (+38,525 / −14,786). Histories are unrelated (no merge base).

---

## 1. Repository shape

- Company repo has **no `mobile_app/`** — the Flutter app is entirely local (KEEP).
- Company top-level: `.github/` (CI), `404.html`, `account.html`, `assets/`, `backend/`, `blog*.html`, `cart.html`, `checkout.html`, `collection.html`, `dist/`, `docs/`, `index.html`, `pages/`, `product.html`, `search.html`, `src/` (only `app.css` + `app.js`), `vite.config.js`, `wishlist.html`, `DEPLOY.md`, `api-reference.md`.
- Local only: `mobile_app/`, `apk/`, `vercel.json`, `.vercelignore`, `AUDIT_REPORT.md`, the local backend `Api/*` layer.
- `src/` in both is a **Vite + Tailwind v4 source** for the *marketing HTML pages*; the Laravel "real" site compiles Tailwind into `backend/public/theme/app.css`.

## 2. Company branch history (last 15 commits, newest first)

| Hash | Message |
|---|---|
| ef23844 | fix(account): move order status tiles below the account menu |
| bc85d27 | fix(seeder): stop hardcoding the admin login in source |
| db96db2 | fix(ci): CI needs PHP 8.4, not 8.3 |
| dafddcc | ci: add GitHub Actions deploy to Hostinger over SSH |
| 2e7b163 | fix(ui): loading feedback and touch targets on the two purchase actions |
| b62cf28 | fix(payment): refuse Razorpay webhooks when the secret isn't configured |
| 65454b3 | fix(admin): clean up sidebar labels, drop single-item Wallet group |
| 758613d | fix(admin): stop dashboard hammering DB every 5s |
| 0772b05 | fix(old-jewellery): vendors couldn't reach the bid page |
| 77a1f26 | fix(old-jewellery): vendor logins can no longer act as admin |
| 95bec53 | fix(vendor): stop the invite flow taking over existing accounts |
| bc41283 | fix(auth): stop pretending an OTP was sent when SMS never went out |
| 056e6b4 | test: fake uploads get real bytes; retarget OTP flow tests |
| bd38b29 | docs: drop AI attribution from admin invite plan |
| 457e6ef | feat(checkout): add express checkout through Shiprocket Fastrr |

## 3. What the company changed that matters to the WEBSITE

Direction note: `git diff company/main HEAD` — `-` lines = company (authority), `+` = local.

### 3.1 Home — Product carousel (`home/blocks/product-carousel.blade.php`)
- Company: `->take(20)` products; when `count > 8` wraps grid in `data-explore` + `explore-grid-4row` class + a mobile-only `sm:hidden` "Explore more" / "Show less" toggle (`data-explore-toggle`, `aria-expanded`, gold underline button, 12px uppercase, 0.14em).
- CSS `.explore-grid-4row > :nth-child(n+9) { display:none }` on mobile; `@media (min-width:640px)` reverts (shows all); `.is-expanded > * { display:revert }` when toggled.
- **Local/HEAD at the diff point had removed this** (`take(10)`, plain grid, no toggle).
- ✅ **Flutter now updated** to `take(20)` + 8-row clip + Explore-more/Show-less toggle (mobile behavior).

### 3.2 Home — Collection carousel (`collection-carousel.blade.php`)
- Company: identical `data-explore` / `explore-grid-4row` / `data-explore-toggle` treatment when `count > 8`. `bg-warmbeige` band.
- ✅ **Flutter now updated** with the same toggle (mobile behavior).

### 3.3 Home — Shop by category (`shop-by-category.blade.php`)
- Company: **visible mobile carousel arrows** — `grid h-7 w-7` (28px) at `-left-1/-right-1`, growing `md:h-10 md:w-10`; border-line circle, white bg, shadow-sm, chevrons, hover rose, `disabled:opacity-0` at the ends. `carousel-track` = `scroll-snap-type:x mandatory`, 4 tiles/viewport on mobile, `gap-2`, `data-carousel-prev/next` scroll one tile.
- Direction: **company shows mobile arrows**; local had made them `hidden md:grid` (no mobile arrows).
- ✅ **Flutter CategoryStrip now updated** with 28px edge chevrons that page the track and fade at the ends.

### 3.4 Design tokens (`src/app.css` `@theme`) — used by marketing pages and the Laravel site
```
--font-sans: 'Plus Jakarta Sans';  --font-serif: 'Playfair Display';
--font-display: 'Cinzel';          --font-script: 'Allura';
--color-accent:#CB6B88;  --color-accent-dark:#AD3D5F;  --color-rose:#CB6B88; --color-rose-dark:#AD3D5F;
--color-gold:#D4AF37;    --color-gold-hover:#B38728;    --color-gold-light:#F5E6BE;
--color-ink:#33302F;     --color-heading:#1F1D1D;       --color-muted:#666666;
--color-ivory:#FAF8F5;   --color-warmbeige:#F3EDE7;     --color-pinksoft:#FBF1F4;
--color-greysoft:#F2EFEB; --color-cream:#FAF8F5;         --color-placeholder:#F2EFEB;
--color-paper:#FFFFFF;   --color-deepwine:#2B141C;
--color-line:#EAE4DE;    --color-line-strong:#D9D0C8;
--color-price:#1F1D1D;   --color-salebadge:#AD3D5F;     --color-newbadge:#CB6B88;
--color-soldout:#8C807B; --color-star:#D4AF37;          --color-headergold:#FAF8F5;
--color-announce:#AD3D5F;
--container-wrapper: 1480px
```
- ✅ Flutter `AppColors` already matches these hex values exactly (NA colour change). Font family set matches `AppTypography` (Cinzel/Playfair/Allura/Plus Jakarta Sans). **No `--color-deeppink/mediumrose/lightrose/darkblush` on this branch** — those are NOT company tokens.

### 3.5 Shared CSS components (company `src/app.css`)
- `.product-card` — rounded-xl, border-line, hover border-gold + soft shadow, image scale on hover, hover second gallery image fade-in.
- `.cat-tile__frame` — circular 150px max, 2px border-line, hover border-gold, image scale.
- `.section-head__eyebrow` — PJS 600, 10.5→11.5px, uppercase, 0.22em, rose.
- `.section-head__title` — Cinzel 600, 17→26→30→34px, uppercase, 0.08em→0.1em, heading.
- `.section-head__rule` — 48px × 1px gold.
- `.section-head__sub` — muted 13.5→15px, max 46ch.
- `.section-head__cta` — 11→12px semibold uppercase 0.14em, hover rose.
- `.carousel-track` — scroll-snap-x, hidden scrollbar; dots 6px → active 20px heading.
- ✅ All already mirrored in Flutter widgets (SectionHeader, ProductCard, CategoryStrip, HeroCarousel).

### 3.6 JS behaviors (company `src/app.js`)
- `initCarousel` — prev/next `scrollBy(±step)`, dots per page, `data-autoplay` interval + hover pause, loop padding. ✅ mirrored (HeroCarousel autoplay 5000ms / 700ms fade). CategoryStrip arrows ✅ added.
- Hero cross-fade `data-fade` — `.is-active` slides, active dot `w-6 bg-white`, inactive `w-1.5 bg-white/55`. ✅ mirrors HeroCarousel.
- Explore toggle `data-explore` — class `is-expanded`, swaps text (more/less), sets `aria-expanded`. ✅ added to ProductStrip/CollectionsGrid.
- Read-more clamp, zoom-lock, sticky header shadow + back-to-top, announcement rotation, mobile drawer, server cart drawer, gallery thumbnail swap, localStorage wishlist. (Flutter has native equivalents.)
- **Zoom-lock** note: company blocks Ctrl+wheel / pinch zoom site-wide — web-only, not applicable to the app.

### 3.7 Modified blades with no Flutter impact (web-only deltas)
| File | Change | Why no Flutter impact |
|---|---|---|
| `partials/footer.blade.php` | newsletter form → AJAX fetch (`data-newsletter-form` + error pill), Terms link → `terms-and-conditions` page | App footer is native; newsletter is web form; CMS page slug is backend-only |
| `products/show.blade.php` | PDP: qty stepper tightened (h-12 w-32, 32px buttons), express "Checkout" button replaced by **"Buy It Now"** outline-accent button | Company uses Fastrr express on web; app uses native Buy It Now checkout → matches company's *web display* intent; no API change |
| `account/index.blade.php` | 3-column account layout (200px nav + orders + details), order tiles moved below menu, avatar removed | Company web layout; app has native Account screen with own API |
| `auth/login*.blade.php`, `register`, `checkout/index` | removed `data-loading-submit`; input base → 14px | Form-loading UX; app uses native forms |

## 4. Backend deltas (app-facing / API)

- **Company has NO `Api/AuthController`, `Api/CatalogController`, `Api/CartController`, `Api/CheckoutController`, `Api/AccountController`, `Api/SellRequestController`** — the whole mobile API layer is **local-only (KEEP)**.
- Company `Api/` = `ApiController`, `OldJewelleryRequestController`, `VendorOldJewelleryController`, `AdminOldJewelleryController`, `WalletController`, `FastrrCatalogController`, `FastrrWebhookController` — the Fastrr/vendor old-jewellery flows (their web + vendor mobile flow).
- Company uses **old-jewellery naming** (`vendors`, `old_jewellery_*`, `VendorTokenAuth`); local uses **sell-requests naming** (`sell_requests`, `sell_bids`, `SellAuditLog`, etc.) — functionally equivalent local evolution (KEEP local).
- Company migrations `103947_create_personal_access_tokens_table` and `2026_09_09_*` (vendors/old-jewellery) replaced locally by `2026_09_10_000000_add_personal_access_tokens_and_user_carts`, `2026_09_11_*`, `2026_09_12_*` (verified_token, user_id on reviews, product_views, sell_* tables, wallet expiry, email OTP channel). → Local schema is a superset for the mobile app (KEEP).
- Company latest web/backend work: Fastrr express checkout (`payment/show`, `payment/unavailable`), GitHub Actions Hostinger deploy, PHP 8.4 CI, seeder cleanup, Razorpay webhook secret guard. Local equivalents already present; **no web-route conflict that affects the app**.

## 5. Commits / files changed that do NOT affect Flutter (informational)

- `.github/` CI (deleted locally — company uses it, local deploys via Vercel). No conflict.
- `docs/`, `README*`, `DEPLOY.md`, `api-reference.md`, `dist/*`, HTML marketing pages — copy/docs only.
- Filament admin resources (Customers/Orders/Reviews/RewardSubmissions/Roles/Wallet/SellRequests) — admin UI only.
- `docker/`, `composer.lock` (PHP 8.4 now) — infra.
- Payments: Razorpay webhook guard + express; wallet service; OTP gateways (`MailOtpGateway`, `TwilioOtpGateway`, `VasMultimediaOtpGateway`, `LogOtpGateway`) — backend, no Flutter shape change (API payloads unchanged).

## 6. Summary for Flutter

| Change | Company site | Old Flutter | New Flutter |
|---|---|---|---|
| Product carousel count | take(20), clip 8 + toggle | take(10), no toggle | take(20), clip 8 + toggle ✅ |
| Collection carousel | explore-grid-4row + toggle | all rows, no toggle | clip 8 + toggle ✅ |
| Category strip arrows | visible 28px mobile arrows | none | 28px paging arrows ✅ |
| Design tokens | token set (see 3.4) | matched | still matched ✅ |
| Fonts | PJS/Playfair/Cinzel/Allura | matched | still matched ✅ |
| PDP / account / footer / auth | web-only changes | native screens | unchanged (native) ✅ |
| Colors | — | — | no change needed ✅ |

**Backend/API:** Company mobile API absent → local layer + tests preserved. Company PHP 8.4 requirement already satisfied locally (320/358→358 tests green w/ PHP 8.4.25).