# Estele — Flutter Storefront App

Cross-platform storefront for the Estele jewellery brand, talking to the Laravel
REST API in `../backend` (stateless JSON, bearer-token auth).

## Run

```sh
flutter pub get
flutter run
```

The API base URL defaults to the Android emulator loopback
(`http://10.0.2.2:8000/api`). Point it at your backend with a `--dart-define`:

```sh
# Physical device / LAN backend
flutter run --dart-define=API_BASE_URL=http://192.168.1.20:8000/api

# iOS simulator / macOS
flutter run --dart-define=API_BASE_URL=http://localhost:8000/api
```

For Android, make sure the emulator has cleartext HTTP to the host allowed
(`android:usesCleartextTraffic="true"` is already set under the debug builds in
`android/app/`). Release builds talk to the HTTPS domain the backend is served
from — change `AppConfig.apiBaseUrl` in `lib/config/app_config.dart`.

## Backend

You need the Laravel backend running first. See `../backend/README*` (or the
root `README`) for install steps — in short:

```sh
cd ../backend
composer install
cp .env.example .env          # set APP_URL, DB_*, RAZORPAY_KEY_ID/SECRET
php artisan key:generate
php artisan migrate --seed
php artisan storage:link      # makes product/media URLs resolve
php artisan serve
```

The demo account seeded by `--seed` is:

```
email:    demo@estele.in
password: password123
```

## Features in this build

- **Guest cart** — every request (even logged-out add-to-cart) sends a device
  `X-Cart-Token`; the server creates/returns the guest cart. Logging in or
  registering with the same device token merges the guest bag automatically.
- **Checkout** — Cash on Delivery works end-to-end. Razorpay orders are created
  server-side and the app shows the payment hand-off instructions (gateway/app
  flow); the verification endpoint (`POST /payment/callback/{orderNumber}`) is
  wired in `CheckoutRepository.verifyPayment` and can be surfaced by the SDK on
  a later pass.
- **Wallet** — balance is read from the profile payload (`/account`). The
  transaction ledger is intentionally empty until a ledger table ships.
- **Reviews, blog, CMS pages, FAQ** — rendering backed by `/blogs`, `/faq`,
  `/pages/{slug}`, `/products/{slug}` (reviews + rating summary).

## Project layout

```
lib/
  config/            AppConfig (API base URL, timeouts)
  theme/             AppColors + AppTypography (gold-leaf gradient wordmark)
  utils/             INR formatters, backend href → route resolver
  data/
    api_client.dart  single HTTP client (bearer + X-Cart-Token, error shapes)
    storage.dart     secure token storage + device cart token
    repositories/    auth, catalog, cart, checkout, account, content
  models/            parsed API payloads (Product, Cart, Order, HomeData, …)
  providers/         Auth, Cart, Wishlist ChangeProviders
  widgets/           ProductCard, RatingStars, PriceText, LoadState, …
  screens/           tabs + feature screens, routed from main.dart
```

## Tests

```sh
flutter analyze    # 0 errors, 0 warnings
flutter test       # model parsing + formatter unit tests
```