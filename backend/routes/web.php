<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CmsPageController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OtpAuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/sitemap.xml', [SitemapController::class, 'index'])
    ->name('sitemap');

Route::get('/robots.txt', function () {
    $settings = \Illuminate\Support\Facades\Cache::remember(
        'site.settings',
        3600,
        fn () => \App\Models\Setting::pluck('value', 'key')->toArray()
    );

    $content = $settings['robots_txt'] ?? null;

    if (! $content) {
        $content = "User-agent: *\nAllow: /\nDisallow: /cart\nDisallow: /checkout\nDisallow: /account\nDisallow: /login\nDisallow: /register\nDisallow: /forgot-password\nDisallow: /reset-password\nDisallow: /payment\nDisallow: /search\n\nSitemap: ".url('/sitemap.xml');
    }

    return response($content, 200)
        ->header('Content-Type', 'text/plain');
})->name('robots');

Route::get('/', [HomeController::class, 'index'])
    ->name('home');

Route::get('/categories', [CategoryController::class, 'index'])
    ->name('categories.index');

Route::get('/categories/{category:slug}', [CategoryController::class, 'show'])
    ->name('categories.show');

Route::get('/collections', [CollectionController::class, 'index'])
    ->name('collections.index');

Route::get('/collections/{collection:slug}', [CollectionController::class, 'show'])
    ->name('collections.show');

Route::get('/products/{product:slug}', [ProductController::class, 'show'])
    ->name('products.show');

Route::post('/products/{product:slug}/reviews', [ReviewController::class, 'store'])
    ->name('products.reviews.store')
    ->middleware('throttle:5,60');

Route::get('/search', [SearchController::class, 'index'])
    ->name('search')
    ->middleware('throttle:60,1');

Route::get('/search/suggest', [SearchController::class, 'suggest'])
    ->name('search.suggest')
    ->middleware('throttle:60,1');


/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Normal Email + Password Login
    |--------------------------------------------------------------------------
    */

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.attempt')
        ->middleware('throttle:10,1');


    /*
    |--------------------------------------------------------------------------
    | Registration
    |--------------------------------------------------------------------------
    */

    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register'])
        ->name('register.attempt')
        ->middleware('throttle:10,1');


    /*
    |--------------------------------------------------------------------------
    | Registration OTP
    |--------------------------------------------------------------------------
    */

    Route::post('/register/send-otp', [AuthController::class, 'sendRegistrationOtp'])
        ->name('register.otp.send')
        ->middleware('throttle:5,1');

    Route::post('/register/verify-otp', [AuthController::class, 'verifyRegistrationOtp'])
        ->name('register.otp.verify')
        ->middleware('throttle:10,1');


    /*
    |--------------------------------------------------------------------------
    | Forgot / Reset Password
    |--------------------------------------------------------------------------
    */

    Route::get('/forgot-password', [PasswordResetController::class, 'showRequest'])
        ->name('password.request');

    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->name('password.email')
        ->middleware('throttle:5,1');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])
        ->name('password.reset');

    Route::post('/reset-password', [PasswordResetController::class, 'update'])
        ->name('password.update')
        ->middleware('throttle:5,1');


    /*
    |--------------------------------------------------------------------------
    | Mobile OTP Login
    |--------------------------------------------------------------------------
    */

    Route::get('/login/mobile', [OtpAuthController::class, 'showPhone'])
        ->name('login.mobile');

    Route::post('/login/mobile', [OtpAuthController::class, 'sendCode'])
        ->name('login.mobile.send')
        ->middleware('throttle:5,1');

    Route::get('/login/mobile/verify', [OtpAuthController::class, 'showVerify'])
        ->name('login.mobile.verify');

    Route::post('/login/mobile/verify', [OtpAuthController::class, 'verifyCode'])
        ->name('login.mobile.verify.attempt')
        ->middleware('throttle:10,1');

    Route::post('/login/mobile/resend', [OtpAuthController::class, 'resend'])
        ->name('login.mobile.resend')
        ->middleware('throttle:3,1');
});


/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');


/*
|--------------------------------------------------------------------------
| Authenticated Account Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/account', [AccountController::class, 'index'])
        ->name('account.index');

    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])
        ->name('account.profile');

    Route::patch('/account/password', [AccountController::class, 'updatePassword'])
        ->name('account.password');

    Route::get('/account/orders/{order:order_number}', [AccountController::class, 'orderShow'])
        ->name('account.orders.show');

    Route::get('/account/orders/{order:order_number}/invoice', [AccountController::class, 'orderInvoice'])
        ->name('account.orders.invoice');

    Route::post('/account/orders/{order:order_number}/cancellation-request', [AccountController::class, 'requestCancellation'])
        ->name('account.orders.cancellation-request')
        ->middleware('throttle:10,1');

    Route::get('/account/addresses', [AccountController::class, 'addresses'])
        ->name('account.addresses');

    Route::post('/account/addresses', [AccountController::class, 'addressStore'])
        ->name('account.addresses.store');

    Route::patch('/account/addresses/{address}', [AccountController::class, 'addressUpdate'])
        ->name('account.addresses.update');

    Route::delete('/account/addresses/{address}', [AccountController::class, 'addressDestroy'])
        ->name('account.addresses.destroy');
});


/*
|--------------------------------------------------------------------------
| Newsletter
|--------------------------------------------------------------------------
*/

Route::post('/newsletter/subscribe', [NewsletterController::class, 'store'])
    ->name('newsletter.subscribe')
    ->middleware('throttle:10,60');


/*
|--------------------------------------------------------------------------
| Content
|--------------------------------------------------------------------------
*/

Route::get('/blogs', [BlogController::class, 'index'])
    ->name('blogs.index');

Route::get('/blogs/{blog:slug}', [BlogController::class, 'show'])
    ->name('blogs.show');

Route::get('/faq', [FaqController::class, 'index'])
    ->name('faq.index');

Route::get('/pages/{cmsPage:slug}', [CmsPageController::class, 'show'])
    ->name('pages.show');


/*
|--------------------------------------------------------------------------
| Cart
|--------------------------------------------------------------------------
*/

Route::get('/cart', [CartController::class, 'index'])
    ->name('cart.index');

Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])
    ->name('cart.coupon.apply')
    ->middleware('throttle:20,1');

Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])
    ->name('cart.coupon.remove')
    ->middleware('throttle:20,1');

Route::post('/cart/{product:slug}', [CartController::class, 'store'])
    ->name('cart.store');

Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])
    ->name('cart.update');

Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])
    ->name('cart.destroy');


/*
|--------------------------------------------------------------------------
| Checkout
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    Route::get('/checkout', [CheckoutController::class, 'index'])
        ->name('checkout.index');

    Route::post('/checkout', [CheckoutController::class, 'store'])
        ->name('checkout.store')
        ->middleware('throttle:10,1');
});

Route::get('/checkout/pincode/{postalCode}', [CheckoutController::class, 'pincodeLookup'])
    ->name('checkout.pincode-lookup')
    ->middleware('throttle:30,1')
    ->where('postalCode', '[0-9]{6}');

Route::get('/checkout/confirmation/{order:order_number}', [CheckoutController::class, 'confirmation'])
    ->name('checkout.confirmation')
    ->middleware('throttle:20,1');


/*
|--------------------------------------------------------------------------
| Payment
|--------------------------------------------------------------------------
*/

Route::get('/payment/{order:order_number}', [PaymentController::class, 'show'])
    ->name('payment.show')
    ->middleware('throttle:20,1');

Route::post('/payment/{order:order_number}/callback', [PaymentController::class, 'callback'])
    ->name('payment.callback')
    ->middleware('throttle:20,1');

Route::post('/webhooks/razorpay', [PaymentController::class, 'webhook'])
    ->name('webhooks.razorpay'); 