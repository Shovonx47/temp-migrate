<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Storage;

Route::match(['post', 'get'], 'payments/{gateway}/callback', PaymentController::class)->name('payments.callback');
Route::match(['post', 'get'], 'payments/{gateway}/webhook', WebhookController::class)->name('payments.webhook');

Route::middleware(['web', 'auth:customer'])
    ->get('downloads/failed-reports/{filename}', function (string $filename) {
        $path = 'failed-reports/'.auth('customer')->id().'/'.$filename;

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    })
    ->where('filename', '[A-Za-z0-9_\-]+\.csv')
    ->name('customer.messages.failed-report');
