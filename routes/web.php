<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PhonePhotoController;
use App\Http\Controllers\SignupLinkController;
use App\Http\Controllers\SmsWebhookController;
use App\Models\Batch;
use Illuminate\Support\Facades\Storage;

require __DIR__.'/customer-portal.php';

Route::match(['post', 'get'], 'payments/{gateway}/callback/{deposit}', PaymentController::class)
    ->name('payments.callback')
    ->middleware(['auth:customer,sp,superman'])
    ->whereIn('gateway', ['piprapay']);

Route::match(['post', 'get'], 'webhooks/payment/{gateway}', PaymentWebhookController::class)
    ->name('payments.webhook')
    ->whereIn('gateway', ['piprapay']);

Route::match(['post', 'get'], 'webhooks/sms/{gateway}', SmsWebhookController::class)
    ->name('sms.webhook')
    ->whereIn('gateway', ['revesms', 'qsms']);

Route::middleware(['web', 'auth:customer'])
    ->get('downloads/batches/{batch}/failed-report/{filename}', function (Batch $batch, string $filename) {
        abort_if($batch->user_id !== auth('customer')->id(), 403);

        $path = "batches/{$batch->id}/{$filename}";

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    })
    ->where('filename', '[A-Za-z0-9_\-]+\.csv')
    ->name('customer.messages.failed-report');

Route::prefix('phone-photo')
    ->name('phone-photo.')
    ->group(function () {
        Route::post('session', [PhonePhotoController::class, 'createSession'])->name('create-session');
        Route::get('{token}', [PhonePhotoController::class, 'showCapturePage'])->name('capture');
        Route::post('{token}/upload', [PhonePhotoController::class, 'uploadPhoto'])->name('upload');
        Route::get('{token}/status', [PhonePhotoController::class, 'checkStatus'])->name('status');
    });

Route::redirect('signin', 'login')->name('login');
Route::get('terms', function () {
    return 'Comming soon...';
})->name('terms');

// Public tariff signup links: /register/{slug}. Hands off to the Filament register page.
Route::get('register/{slug}', SignupLinkController::class)
    ->middleware(['web', 'throttle:60,1'])
    ->where('slug', '[A-Za-z0-9-]+')
    ->name('register.slug');

// Admin-only viewer for private KYC files (session login, no signed URLs)
Route::middleware(['web', 'auth:superman'])
    ->get('kyc-files/{user}/{field}', function (string $user, string $field) {
        abort_unless(in_array($field, ['front_nid', 'back_nid', 'live_photo', 'trade_license_file'], true), 404);

        $path = \App\Models\User::findOrFail($user)->{$field};
        $disk = \Illuminate\Support\Facades\Storage::disk('private');

        abort_if(blank($path) || str_contains($path, '..') || ! $disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    })
    ->whereNumber('user')
    ->name('kyc-file');

// Leave impersonation: safely restore impersonator and redirect back to admin panel
Route::get('filament-impersonate/leave', function () {
    $impersonatorId = session(\STS\FilamentImpersonate\ImpersonateManager::SESSION_KEY);
    $impersonatorGuard = session(\STS\FilamentImpersonate\ImpersonateManager::SESSION_GUARD) ?: 'superman';
    $backTo = session()->pull('impersonate.back_to')
        ?: config('filament-impersonate.redirect_to')
        ?: \Filament\Facades\Filament::getPanel($impersonatorGuard)->getUrl();

    session()->forget('url.intended');

    if (\STS\FilamentImpersonate\Facades\Impersonation::isImpersonating()) {
        \STS\FilamentImpersonate\Facades\Impersonation::leave();
    }

    if ($impersonatorId && $user = \App\Models\User::find($impersonatorId)) {
        auth()->guard($impersonatorGuard)->login($user, remember: true);
        auth()->shouldUse($impersonatorGuard);
    }

    session()->forget([
        'password_hash_superman',
        'password_hash_customer',
        'password_hash_sp',
        'password_hash_web',
        \STS\FilamentImpersonate\ImpersonateManager::SESSION_KEY,
        \STS\FilamentImpersonate\ImpersonateManager::SESSION_GUARD,
        \STS\FilamentImpersonate\ImpersonateManager::SESSION_GUARD_USING,
        'impersonate',
    ]);

    return redirect($backTo);
})
    ->name('filament-impersonate.leave')
    ->middleware(config('filament-impersonate.leave_middleware', 'web'));

