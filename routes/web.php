<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\SmsWebhookController;
use App\Models\Batch;
use Illuminate\Support\Facades\Storage;

// The payment gateway return URL is reached by any tenant (Customer, S P,
// or Superman). The controller resolves the correct panel by the deposit
// owner's user type, so the callback authenticates on all guards.
Route::match(['post', 'get'], 'payments/{gateway}/callback/{deposit}', PaymentController::class)
    ->name('payments.callback')
    ->middleware(['auth:customer', 'auth:sp', 'auth:superman'])
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

Route::redirect('signin', 'login')->name('login');
Route::get('terms', function () {
    return 'Comming soon...';
})->name('terms');
