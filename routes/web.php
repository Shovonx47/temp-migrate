<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\SmsWebhookController;
use App\Models\MessageBatch;
use Illuminate\Support\Facades\Storage;

Route::match(['post', 'get'], 'payments/{gateway}/callback/{deposit}', PaymentController::class)
    ->name('payments.callback')
    ->middleware(['auth:customer'])
    ->whereIn('gateway', ['piprapay']);

Route::match(['post', 'get'], 'webhooks/payment/{gateway}', PaymentWebhookController::class)
    ->name('payments.webhook')
    ->whereIn('gateway', ['piprapay']);

Route::match(['post', 'get'], 'webhooks/sms/{gateway}', SmsWebhookController::class)
    ->name('sms.webhook')
    ->whereIn('gateway', ['revesms', 'qsms']);

Route::middleware(['web', 'auth:customer'])
    ->get('downloads/message-batches/{batch}/failed-report/{filename}', function (MessageBatch $batch, string $filename) {
        abort_if($batch->user_id !== auth('customer')->id(), 403);

        $path = "message-batches/{$batch->id}/{$filename}";

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    })
    ->where('filename', '[A-Za-z0-9_\-]+\.csv')
    ->name('customer.messages.failed-report');

Route::redirect('signin', 'login')->name('login');
Route::get('terms', function () {
    return 'Comming soon...';
})->name('terms');
