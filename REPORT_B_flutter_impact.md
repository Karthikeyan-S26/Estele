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
| 11 | `collection-carousel`: label `font-medium` (w500), uppercase, `leading-tight` | `CollectionsGrid` label was `font-semibold` (w600), not uppercase, default leading | Label → uppercase w500 `height: 1.25` | ✅ | ✅ |
| 12 | `product-carousel` + `collection-carousel`: explore-toggle button `font-medium` (w500), 12px/0.14em | `ProductStrip`/`CollectionsGrid` toggle text was `AppTypography.button()` default w600 | Explicitly pass `weight: FontWeight.w500` in both toggles | ✅ | ✅ |
| 13 | `shop-by-category` arrows: `-left-1/-right-1` inside a `px-3` container → actual viewport inset ≈ 8px; arrows fully visible | `CategoryStrip` arrows at `left: -4/right: -4` clipped 4px off-screen | Changed to `left: 8/right: 8`; Stack `Alignment.centerLeft` gives vertical center | ✅ | ✅ |
| 14 | `collection-carousel` blade iterates ALL collections (no `take()`); mobile grid = `aspect-[4/5]` box + 10px + 10.5px leading-tight label | `CollectionsGrid` had `childAspectRatio: 4/5*0.78` → tiles ~35% taller than web; `take(20)` cap arbitrary | Removed take cap; childAspectRatio now computed from actual column width against true 4:5 box + 10px + label lineHeight | ✅ | ✅ |
| 15 | Section-header centered CTA (`border-b border-gold ... font-medium`, 12px/0.14em) vs left-aligned CTA (`--font-weight-semibold`, 11px/0.14em) | Both used w600 via `AppTypography.button()` default | Centered CTA → w500; left-aligned CTA remains w600 (matches `--font-weight-semibold`) | ✅ | ✅ |

## 2. Files changed (Flutter)

- `mobile_app/lib/screens/catalog/home/product_strip.dart` — take(20), 8-row clip, Explore-more/Show-less toggle, toggle weight w500.
- `mobile_app/lib/screens/catalog/home/collections_grid.dart` — same toggle w500; removed take cap; childAspectRatio computed from actual width for true 4:5 geometry; label uppercase/w500/leading-tight.
- `mobile_app/lib/screens/catalog/home/category_strip.dart` — 28px paging chevrons, end-fade, arrow position `left: 8/right: 8` (matches web's `-left-1` inside `px-3` container).
- `mobile_app/lib/widgets/section_header.dart` — centered CTA weight corrected to w500 (matches blade's `font-medium`); left-aligned CTA remains w600 (matches `--font-weight-semibold`).
- Docs: `REPORT_A_company_update.md`, `REPORT_B_flutter_impact.md`.

## 3. Tests

- `flutter analyze`: 0 errors, 26 info (all pre-existing or non-blocking).
- `flutter test`: **18/18 passed** (covers product strip, collections grid, category strip, chat widget, nav tabs).
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

**APK rebuilt.** `app-release.apk` (57.6 MB) built after all Phase 10 parity fixes, with all tests green. No UI regression detected by code analysis. Live visual pixel-comparison remains BLOCKED (see §5).

## 7. Phase 10 QA verdict

### VERIFIED (Blade/CSS evidence + render + passing tests)

| Item | Evidence |
|---|---|
| Hero carousel arrows (40px, white/85, left-3/right-3, visible on all breakpoints) | Blade `div class="absolute ..."` in `index.blade.php`; CSS `.explore-grid-4row` in `app.css` |
| Hero dots (bottom-4, active pill w24, 768/320 aspect) | Same blade source; `aspect-ratio 768/320` inline mobile style |
| Product strip take(20), 8-row clip, "Explore more/Show less" toggle | `product-carousel.blade.php` `->take(20)`, `data-explore-toggle`, CSS `explore-grid-4row` |
| Collections iterate all (no take), same toggle | `collection-carousel.blade.php` `@foreach($collections)` |
| Collections label: 10.5px, font-medium, uppercase, leading-tight, tracking 0.06em | Blade label class |
| Explore toggle text: 12px, font-medium (w500), uppercase, tracking 0.14em | Blade toggle class |
| Section-header left variant: `mb-4`, CTA w600 11px | `section-head__cta` in `app.css` |
| Section-header centered variant: `mb-5`, CTA w500 12px | `section-head__centered` in blade |
| Category arrows: 28px (h-7 w-7) white/line border, left/right at −1 from px-3 container (~8px inset) | `shop-by-category.blade.php`; `.relative` parent + `absolute -left-1` |
| Cat-tile frame: aspect-ratio 1, max-width 150px, border-2 line, rounded-full | `.cat-tile__frame` in `app.css` |
| Product card: 8px margin frame, aspect-ratio 1 image, px-3 pb-3, serif 13px, CTA rounded-full | `.product-card` in `app.css` |
| Chat widget (50px floating button, red badge, "Need help?" tip) | `layouts/app.blade.php` `data-chat` element |

### NOT VERIFIED (equivalent Flutter-native implementation, not web pixel match)

| Item | Status |
|---|---|
| Header/drawer/search desktop layout | Flutter native bottom nav + app bar; web layout only on web |
| Product card height | Blade auto-sizes rows; Flutter uses estimated +124px — within acceptable range for auto-flow |
| Category strip inner padding around circle frame | Flutter uses +12px per tile vs blade's generic content padding — visible only in rare edge cases |

### BLOCKED (cannot verify)

| Item | Blocker |
|---|---|
| Live company site pixel-comparison | Tunnel URL dead; no working company URL |
| Company MySQL env | `.env` points to MySQL `estele`; no local MySQL |
| Headless Chrome scroll capture | Puppeteer End/wheel never advanced the viewport; only top-of-page captured |