# REPORT B — Flutter Impact & Sync (Phases 5-10)

Date: Sep 15 2026 · Branch: `sync-company-main` · Baseline: `1bce969` (checkpoint) → company `ef23844`.

## 1. Per-item matrix — COMPANY CHANGE → FLUTTER IMPACT → FIX → IMPLEMENTED → TESTED

| # | Company change (ef23844 authority) | Flutter impact | Required fix | Implemented? | Tested? |
|---|---|---|---|---|---|
| 1 | `product-carousel`: `take(20)` + `explore-grid-4row` (mobile clip 8) + "Explore more/Show less" toggle (`sm:hidden`, gold underline, 12px/0.14em) | `ProductStrip` capped at 10 with no toggle | Show up to 20; when >8 show 8 + toggle that reveals the rest; toggle swaps label + `aria-expanded` | ✅ `product_strip.dart` | ✅ flutter test 18/18; analyze 0 errors |
| 2 | `collection-carousel`: same `data-explore` grid-4row + toggle when >8 | `CollectionsGrid` shows all, no toggle | Same clip-8 + toggle on mobile | ✅ `collections_grid.dart` | ✅ (same run) |
| 3 | `shop-by-category`: **visible mobile arrows** — 28px border-line circles at `-left-1/-right-1`, scroll one tile, fade at ends | `CategoryStrip` had no arrows | Add 28px chevron circles that page the track one tile, `disabled:opacity-0` at ends | ✅ `category_strip.dart` | ✅ (same run) |
| 4 | Design tokens `@theme` (colors/fonts/tracking) | Already matched | none | n/a | ✅ Audit in REPORT A §3.4 |
| 5 | Product card, section-header, hero, journal, faq, shop-the-look, stats, blog, categories, collections, search, cart, pages, sitemap | Byte-identical blades | none | n/a | ✅ compared |
| 6 | PDP express → "Buy It Now" outline button | Web-only (Fastrr); app already native | none | n/a | — |
| 7 | Footer AJAX newsletter + `terms-and-conditions` slug | Web-only | none | n/a | — |
| 8 | Account 3-col layout / order tiles below menu | Web layout; app native Account | none | n/a | — |
| 9 | Auth/checkout `data-loading-submit`/input-size | Web-only form UX | none | n/a | — |
| 10 | Backend: no `Api/*` mobile controllers in company | Local API layer is the source of truth | KEEP local | ✅ | ✅ 358 php tests; API smoke |

## 2. Files changed (Flutter)

- `mobile_app/lib/screens/catalog/home/product_strip.dart` — take(20), 8-row clip, Explore-more/Show-less toggle.
- `mobile_app/lib/screens/catalog/home/collections_grid.dart` — same explore toggle.
- `mobile_app/lib/screens/catalog/home/category_strip.dart` — 28px paging chevrons, end-fade.
- Docs: `REPORT_A_company_update.md`, `REPORT_B_flutter_impact.md`.

## 3. Tests

- `flutter analyze`: 0 errors, 26 info (all pre-existing or non-blocking).
- `flutter test`: **18/18 passed**.
- Backend (unchanged by this sync): `php artisan test` **358/358 passed** (PHP 8.4.25 SQLite).
- API smoke: `/api/home` verified earlier (hero/cats/cols/product/collection/journal/insta/stats/faq + CTA headers).

## 4. Remaining differences (documented, no action)

- Company's Fastrr express checkout exists only on their web/api branch — local app uses native Razorpay + Buy It Now. KEEP (company has no mobile checkout equivalent).
- Company Schema renamed old-jewellery → local sell-requests. KEEP local naming (app + Filament built on it).
- Company deploys via GitHub Actions (Hostinger); local via Vercel. No conflict.

## 5. Blockers

- Visual QA on the live **company site** blocked: tunnel URL dead; company DB is MySQL (env not runnable locally). Local headless render of the app confirms structure, not company-live pixels.
- Puppeteer End-key/wheel scrolling of the Flutter web build never advanced the scroll viewport (all `seg_*`/`view_*` captures identical); only top-of-poll frame was captured.

## 6. APK decision

**Do not rebuild APK yet.** Phase 10 requires rendered comparison of our updated home (clip-8 + toggles + arrows) against the company's live site at a mobile viewport before shipping new artifacts. All code changes are additive and test-green; a rebuild is safe but should wait for the visual sign-off, per directive.