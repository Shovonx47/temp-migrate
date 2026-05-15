<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use App\Models\Campaign;
use Illuminate\Support\Facades\Storage;

Route::match(['post', 'get'], 'payments/{gateway}/callback', PaymentController::class)->name('payments.callback');
Route::match(['post', 'get'], 'payments/{gateway}/webhook', WebhookController::class)->name('payments.webhook');

Route::middleware(['web', 'auth:customer'])
    ->get('downloads/campaigns/{campaign}/failed-report/{filename}', function (Campaign $campaign, string $filename) {
        abort_if($campaign->user_id !== auth('customer')->id(), 403);

        $path = "campaigns/{$campaign->id}/{$filename}";

        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    })
    ->where('filename', '[A-Za-z0-9_\-]+\.csv')
    ->name('customer.campaigns.failed-report');
