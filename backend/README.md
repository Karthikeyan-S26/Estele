# Estele — Backend (Laravel)

Laravel 13 + Filament 5 backend for the Estele jewellery storefront. Serves both the customer-facing site (Blade + Tailwind) and the `/admin` panel. See the root [README.md](../README.md) for overall project/phase status.

## Stack

- Laravel 13, PHP 8.3+
- Filament 5 admin panel + Filament Shield (roles/permissions)
- MySQL (SQLite for local dev), Redis (cache/queue/session)
- Laravel Scout + Meilisearch — product search
- Spatie Media Library — image/video uploads
- Laravel DomPDF — invoices
- Razorpay — payments, Shiprocket — shipping
- VAS Multimedia / Twilio — OTP SMS gateway (`App\Services\Otp`)

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan test
php artisan serve
```

App: `http://127.0.0.1:8000` — Admin: `http://127.0.0.1:8000/admin/login`

Default seeded admin: see `database/seeders/DatabaseSeeder.php` (`lavanyagarg500@gmail.com`) — change the password after first login, this is a dev-seed credential only.

## OTP login

Phone-based OTP login (`/login/mobile`) defaults to `LogOtpGateway`, which writes the code to `storage/logs/laravel.log` instead of sending a real SMS — no gateway credentials needed for local dev. To send real SMS, set `SMS_DRIVER` + `VAS_SMS_*` (or `TWILIO_*`) in `.env`. See `app/Services/Otp/OtpManager.php` for gateway selection order.

## Tests

```bash
php artisan test
```

179 tests / 452 assertions as of this writing. On a local PHP build without the `gd` extension's JPEG support, 8 media-upload tests will fail (`imagejpeg function is not defined`) — this is an environment gap, not a code defect; the production Docker image (`Dockerfile`) builds GD with JPEG/WebP support.

## Deploy

`docker/start.sh` runs on container start: `config:cache`, `view:cache`, `migrate --force`, then `supervisord` (nginx + php-fpm). `route:cache` is deliberately skipped — Filament v5 registers admin routes dynamically at boot, which isn't reliably compatible with Laravel's cached route file.
