# API Reference

Every backend route as of `ayush-feat` (+ wallet feature, not yet merged).

## How this app works

There is **no separate REST API** (no `routes/api.php`). Everything lives in `routes/web.php` and is one of two kinds:

- **Page routes** — `GET` routes that render a full Blade view. Link to these with a normal `<a href>` or navigate the browser; don't `fetch()` them.
- **Action endpoints** — `POST`/`PATCH`/`DELETE` routes, and a couple of `GET` routes explicitly meant for `fetch()`. Most of these branch on the request: a normal form `POST` gets a redirect back with a flash message (`success`/`error` in the session, read via `session('success')`/`session('error')` in Blade); a `fetch()` call that sends `Accept: application/json` (or `X-Requested-With: XMLHttpRequest`) gets JSON back instead. That's what `$request->wantsJson()` in the controllers checks.

All routes below are relative to the app's base URL. Session-based auth (Laravel's default `web` guard) — no tokens, no `Authorization` header. A logged-in user just needs the session cookie, which the browser sends automatically.

CSRF: every non-GET request needs Laravel's `_token` — either as a hidden form field (`@csrf` in Blade) or, for `fetch()`, the `X-CSRF-TOKEN` header set to the value from `<meta name="csrf-token">` (add that meta tag to the layout if it's not already there).

---

## Storefront pages

| Method | Path | Name | Notes |
|---|---|---|---|
| GET | `/` | `home` | Homepage |
| GET | `/categories` | `categories.index` | All categories |
| GET | `/categories/{slug}` | `categories.show` | Products in one category |
| GET | `/collections` | `collections.index` | All collections |
| GET | `/collections/{slug}` | `collections.show` | Products in one collection |
| GET | `/products/{slug}` | `products.show` | Product detail page |
| GET | `/search` | `search` | Full search results page. Query params: `q`, `sort` (`relevance`\|`price_asc`\|`price_desc`\|`newest`), `min_price`, `max_price`, `in_stock` (`1`), `category[]` (slug, repeatable) |
| GET | `/blogs` | `blogs.index` | Blog listing |
| GET | `/blogs/{slug}` | `blogs.show` | Blog post |
| GET | `/faq` | `faq.index` | FAQ page |
| GET | `/pages/{slug}` | `pages.show` | CMS page (About, Terms, etc.) |
| GET | `/cart` | `cart.index` | Cart page |
| GET | `/checkout` | `checkout.index` | **Auth required.** Checkout form |
| GET | `/checkout/confirmation/{order_number}` | `checkout.confirmation` | Order-placed confirmation |
| GET | `/payment/{order_number}` | `payment.show` | Razorpay payment page |

---

## Auth

| Method | Path | Name | Body | Response |
|---|---|---|---|---|
| GET | `/login` | `login` | — | Login page |
| POST | `/login` | `login.attempt` | `email`, `password`, `remember` (opt.) | Redirect → `/account`, or back with `error` |
| GET | `/register` | `register` | — | Registration page |
| POST | `/register` | `register.attempt` | `name`, `email`, `phone` | Redirect. **Requires** `phone` to have already passed OTP verify below in the same session — a phone that wasn't OTP-verified is rejected even if the form fields are valid |
| POST | `/register/send-otp` | `register.otp.send` | `phone` (10 digits) | **JSON**: `{success, message}` or `422 {message}` if phone already registered |
| POST | `/register/verify-otp` | `register.otp.verify` | `code` (6 digits) | **JSON**: `{success, message}` or `422 {message}` |
| GET | `/forgot-password` | `password.request` | — | Page |
| POST | `/forgot-password` | `password.email` | `email` | Redirect back, generic success message (doesn't reveal if email exists) |
| GET | `/reset-password/{token}` | `password.reset` | `?email=` | Page |
| POST | `/reset-password` | `password.update` | `token`, `email`, `password`, `password_confirmation` | Redirect → `/login` |
| GET | `/login/mobile` | `login.mobile` | — | Phone-entry page (OTP login) |
| POST | `/login/mobile` | `login.mobile.send` | `phone` (10–15 digits) | Redirect → verify page |
| GET | `/login/mobile/verify` | `login.mobile.verify` | — | Code-entry page |
| POST | `/login/mobile/verify` | `login.mobile.verify.attempt` | `code` (6 digits) | Redirect → `/account` if the phone matches an existing user, or → `/register` (prefilled) if it's a new number |
| POST | `/login/mobile/resend` | `login.mobile.resend` | — | Redirect back, `success` flash |
| POST | `/logout` | `logout` | — | **Auth required.** Redirect → `/` |

Note the asymmetry: `/register/send-otp` and `/register/verify-otp` (new-account OTP) always return JSON. `/login/mobile*` (OTP *login*) always redirects — it's a classic multi-page flow, not fetch-driven.

---

## Account (auth required on all of these)

| Method | Path | Name | Body | Notes |
|---|---|---|---|---|
| GET | `/account` | `account.index` | — | Dashboard + paginated order list |
| PATCH | `/account/profile` | `account.profile` | `name`, `email` | Redirect back |
| PATCH | `/account/password` | `account.password` | `current_password`, `password`, `password_confirmation` | Redirect back |
| GET | `/account/orders/{order_number}` | `account.orders.show` | — | One order's detail. 404s if it's not the logged-in user's order |
| GET | `/account/orders/{order_number}/invoice` | `account.orders.invoice` | — | Streams a PDF download |
| POST | `/account/orders/{order_number}/cancellation-request` | `account.orders.cancellation-request` | `reason` | Flags the order for admin review; doesn't cancel it directly. `422` if the order isn't in a cancellable state |
| GET | `/account/addresses` | `account.addresses` | — | Address book page |
| POST | `/account/addresses` | `account.addresses.store` | see [Address fields](#address-fields) | Redirect back |
| PATCH | `/account/addresses/{id}` | `account.addresses.update` | see [Address fields](#address-fields) | Redirect back |
| DELETE | `/account/addresses/{id}` | `account.addresses.destroy` | — | Redirect back |

#### Address fields
`label`, `line1`, `line2` (opt.), `city`, `state`, `postal_code` (6 digits), `country`, `phone` (opt., 10–15 digits), `is_default` (opt. bool).

---

## Cart (session-based — works for guests too, no auth needed)

All of these support both modes: a plain form `POST` redirects back with a flash message; a `fetch()` with `Accept: application/json` gets JSON back.

| Method | Path | Name | Body | JSON response shape |
|---|---|---|---|---|
| GET | `/cart` | `cart.index` | — | Page (not an endpoint) |
| POST | `/cart/{product_slug}` | `cart.store` | `quantity` (opt., default 1), `product_variant_id` (opt.) | see below |
| PATCH | `/cart/items/{cart_item_id}` | `cart.update` | `quantity` (required) | see below |
| DELETE | `/cart/items/{cart_item_id}` | `cart.destroy` | — | see below |
| POST | `/cart/coupon` | `cart.coupon.apply` | `code` | see below |
| DELETE | `/cart/coupon` | `cart.coupon.remove` | — | see below |

**Success JSON** (all five above, on success):
```json
{
  "success": true,
  "message": "Added to cart.",
  "cartCount": 3,
  "html": "<!-- rendered cart-drawer-items partial, drop straight into the drawer -->"
}
```
**Error JSON** (validation failure, out of stock, bad coupon, etc.) — HTTP `422`:
```json
{ "success": false, "message": "This product is out of stock." }
```

---

## Checkout & payment

| Method | Path | Name | Body | Response |
|---|---|---|---|---|
| GET | `/checkout` | `checkout.index` | — | **Auth required.** Page |
| POST | `/checkout` | `checkout.store` | see [Checkout fields](#checkout-fields) | **Auth required.** Redirect → confirmation (COD) or → `/payment/{order}` (Razorpay), or back with `error` |
| GET | `/checkout/pincode/{postal_code}` | `checkout.pincode-lookup` | — | **JSON**, 6-digit numeric path param. `{"city": "...", "state": "..."}` or `404 {"message": "PIN code not found."}` |
| GET | `/checkout/confirmation/{order_number}` | `checkout.confirmation` | — | Page |
| GET | `/payment/{order_number}` | `payment.show` | — | Page — embeds Razorpay checkout JS with a key/order id |
| POST | `/payment/{order_number}/callback` | `payment.callback` | `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature` | Redirect → confirmation or back with `error` |
| POST | `/webhooks/razorpay` | `webhooks.razorpay` | Razorpay's own webhook payload | Server-to-server only — not for frontend use. **JSON** `{"status": "ok"}` |

#### Checkout fields
`customer_first_name`, `customer_last_name`, `customer_email`, `customer_phone`, `shipping_address_line1`, `shipping_address_line2` (opt.), `shipping_city`, `shipping_state`, `shipping_postal_code`, `order_note` (opt.), `payment_method` (`cod` or `razorpay`).

---

## Content & misc

| Method | Path | Name | Body | Response |
|---|---|---|---|---|
| GET | `/search/suggest` | `search.suggest` | `?q=` | **JSON, always.** `{"results": [{title, url, price, thumbnail}, ...]}` (top 6 matches). Header search-bar autocomplete |
| POST | `/products/{slug}/reviews` | `products.reviews.store` | `customer_name`, `customer_email`, `rating` (1–5), `title` (opt.), `body`, `photos[]` (opt., max 3 images) | Redirect back. Review starts `pending`, needs admin approval before it shows |
| POST | `/newsletter/subscribe` | `newsletter.subscribe` | `email`, `popup_id` (opt.) | JSON `{"message": "Subscribed."}` if `Accept: application/json`, else redirect back |

---

## Wallet & rewards — ⚠️ not yet merged into `ayush-feat`

These exist on the `feat/reward-submissions-wallet` branch/worktree only. Don't build against them until that branch merges — routes or field names may still shift.

| Method | Path | Name | Body | Notes |
|---|---|---|---|---|
| GET | `/account/rewards` | `account.rewards.index` | — | **Auth required.** Shows eligible orders, past submissions, wallet balance + paginated transaction history |
| POST | `/account/rewards` | `account.rewards.store` | `order_id`, `image` (required, max 3MB), `video` (required, mp4/webm, max 10MB) | **Auth required.** Order must be `delivered` and not already have a submission. `422` on either violation |

Wallet balance during checkout is read off `auth()->user()->wallet_balance` server-side — there's no separate "get my wallet balance" endpoint; it comes back as part of whatever page already shows it (`/account/rewards`, checkout page once wallet-at-checkout ships).

---

## Quick reference — which ones actually return JSON

Everything else above is either a page or a redirect-back. These are the ones worth `fetch()`-ing directly:

- `GET /search/suggest`
- `GET /checkout/pincode/{postal_code}`
- `POST /register/send-otp`
- `POST /register/verify-otp`
- `POST /newsletter/subscribe` (JSON *only if* you ask for it via `Accept: application/json`)
- `POST /cart/{slug}`, `PATCH /cart/items/{id}`, `DELETE /cart/items/{id}`, `POST /cart/coupon`, `DELETE /cart/coupon` (JSON *only if* you ask for it)
