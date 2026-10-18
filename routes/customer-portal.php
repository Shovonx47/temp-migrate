<?php

use App\Http\Controllers\Api\V1\Customer\AnalyticsController;
use App\Http\Controllers\Api\V1\Customer\AuthController;
use App\Http\Controllers\Api\V1\Customer\BatchApiController;
use App\Http\Controllers\Api\V1\Customer\ContactApiController;
use App\Http\Controllers\Api\V1\Customer\DepositApiController;
use App\Http\Controllers\Api\V1\Customer\DeveloperApiController;
use App\Http\Controllers\Api\V1\Customer\KycController;
use App\Http\Controllers\Api\V1\Customer\LiveQueueController;
use App\Http\Controllers\Api\V1\Customer\MeController;
use App\Http\Controllers\Api\V1\Customer\MessageApiController;
use App\Http\Controllers\Api\V1\Customer\OtpController;
use App\Http\Controllers\Api\V1\Customer\PricingApiController;
use App\Http\Controllers\Api\V1\Customer\SenderApiController;
use App\Http\Controllers\Api\V1\Customer\TemplateApiController;
use App\Http\Controllers\Api\V1\Customer\TransactionApiController;
use App\Http\Controllers\Api\V1\IpWhitelistController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/v1/customer')
    ->name('api.v1.customer.')
    ->group(function (): void {
        Route::middleware('throttle:20,1')->group(function (): void {
            Route::post('otp/send', [OtpController::class, 'send'])->name('otp.send');
            Route::post('otp/verify', [OtpController::class, 'verify'])->name('otp.verify');
            Route::post('login', [AuthController::class, 'login'])->name('login');
            Route::post('register', [AuthController::class, 'register'])->name('register');
            Route::get('register/plan/{slug}', [AuthController::class, 'plan'])
                ->where('slug', '[A-Za-z0-9-]+')
                ->name('register.plan');
            Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->name('forgot-password');
            Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('reset-password');
        });

        Route::middleware(['customer.guard', 'auth:customer'])->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me', [MeController::class, 'show'])->name('me');
            Route::put('profile', [MeController::class, 'update'])->name('profile.update');
            Route::put('password', [MeController::class, 'updatePassword'])->name('password.update');
            Route::get('dashboard/stats', [MeController::class, 'dashboard'])->name('dashboard.stats');

            Route::get('kyc', [KycController::class, 'show'])->name('kyc.show');
            Route::post('kyc', [KycController::class, 'submit'])->name('kyc.submit');

            Route::get('analytics/messaging', [AnalyticsController::class, 'messaging'])->name('analytics.messaging');
            Route::get('analytics/billing', [AnalyticsController::class, 'billing'])->name('analytics.billing');
            Route::get('live-queue', LiveQueueController::class)->name('live-queue');

            // Messaging
            Route::get('messages', [MessageApiController::class, 'index'])->name('messages.index');
            Route::post('messages/send', [MessageApiController::class, 'send'])->name('messages.send');
            Route::post('messages/schedule', [MessageApiController::class, 'schedule'])->name('messages.schedule');
            Route::post('messages/bulk-send', [MessageApiController::class, 'bulkSend'])->name('messages.bulk-send');

            // Batches
            Route::get('batches', [BatchApiController::class, 'index'])->name('batches.index');
            Route::get('batches/{id}', [BatchApiController::class, 'show'])->whereNumber('id')->name('batches.show');

            // Senders
            Route::get('senders', [SenderApiController::class, 'index'])->name('senders.index');
            Route::post('senders', [SenderApiController::class, 'store'])->name('senders.store');

            // Templates
            Route::get('templates', [TemplateApiController::class, 'index'])->name('templates.index');
            Route::post('templates', [TemplateApiController::class, 'store'])->name('templates.store');
            Route::delete('templates/{id}', [TemplateApiController::class, 'destroy'])->whereNumber('id')->name('templates.destroy');

            // Audience (Contacts & Groups)
            Route::get('contacts', [ContactApiController::class, 'index'])->name('contacts.index');
            Route::post('contacts', [ContactApiController::class, 'store'])->name('contacts.store');
            Route::delete('contacts/{id}', [ContactApiController::class, 'destroy'])->whereNumber('id')->name('contacts.destroy');
            Route::get('groups', [ContactApiController::class, 'groups'])->name('groups.index');
            Route::post('groups', [ContactApiController::class, 'storeGroup'])->name('groups.store');
            Route::delete('groups/{id}', [ContactApiController::class, 'destroyGroup'])->whereNumber('id')->name('groups.destroy');

            // Billing & Pricing
            Route::get('deposits', [DepositApiController::class, 'index'])->name('deposits.index');
            Route::post('deposits', [DepositApiController::class, 'store'])->name('deposits.store');
            Route::get('transactions', [TransactionApiController::class, 'index'])->name('transactions.index');
            Route::get('pricing', [PricingApiController::class, 'index'])->name('pricing.index');

            // Developer Hub (Tokens & IP Whitelists)
            Route::get('tokens', [DeveloperApiController::class, 'tokens'])->name('tokens.index');
            Route::post('tokens', [DeveloperApiController::class, 'storeToken'])->name('tokens.store');
            Route::delete('tokens/{id}', [DeveloperApiController::class, 'destroyToken'])->whereNumber('id')->name('tokens.destroy');
            Route::get('ip-whitelists', [IpWhitelistController::class, 'index'])->name('ip-whitelists.index');
            Route::post('ip-whitelists', [IpWhitelistController::class, 'store'])->name('ip-whitelists.store');
            Route::delete('ip-whitelists/{id}', [IpWhitelistController::class, 'destroy'])->whereNumber('id')->name('ip-whitelists.destroy');
        });
    });

// SvelteKit Static SPA Delivery
Route::get('_app/{path}', function (string $path) {
    $filePath = base_path("frontend/build/_app/{$path}");
    abort_unless(file_exists($filePath), 404);

    $mime = str_ends_with($filePath, '.css')
        ? 'text/css'
        : (str_ends_with($filePath, '.js') ? 'application/javascript' : mime_content_type($filePath));

    return response()->file($filePath, [
        'Content-Type' => $mime,
        'Cache-Control' => 'public, max-age=31536000, immutable',
    ]);
})->where('path', '.*')->name('svelte.asset');

$spaHandler = function () {
    $path = base_path('frontend/build/index.html');
    abort_unless(file_exists($path), 404);

    return response()->file($path, [
        'Content-Type' => 'text/html',
        'Cache-Control' => 'no-cache, private',
    ]);
};

Route::get('/', $spaHandler)->name('svelte.root');
Route::get('customer/{any?}', $spaHandler)->where('any', '.*')->name('svelte.customer');
Route::get('messaging/{any?}', $spaHandler)->where('any', '.*')->name('svelte.messaging');
Route::get('audience/{any?}', $spaHandler)->where('any', '.*')->name('svelte.audience');
Route::get('billing/{any?}', $spaHandler)->where('any', '.*')->name('svelte.billing');
Route::get('developer/{any?}', $spaHandler)->where('any', '.*')->name('svelte.developer');
Route::get('analytics/{any?}', $spaHandler)->where('any', '.*')->name('svelte.analytics');
Route::get('account/{any?}', $spaHandler)->where('any', '.*')->name('svelte.account');
Route::get('password-reset/{any?}', $spaHandler)->where('any', '.*')->name('svelte.password-reset');
Route::get('pricing', $spaHandler)->name('svelte.pricing');
Route::get('profile', $spaHandler)->name('svelte.profile');
Route::get('superman/{any?}', $spaHandler)->where('any', '.*')->name('svelte.superman');
Route::get('sp/{any?}', $spaHandler)->where('any', '.*')->name('svelte.sp');
Route::get('{authRoute}', $spaHandler)->whereIn('authRoute', ['login', 'register', 'forgot-password', 'reset-password'])->name('svelte.auth');

