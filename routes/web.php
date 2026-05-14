<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;

Route::match(['post', 'get'], 'payments/{gateway}/callback', PaymentController::class)->name('payments.callback');
Route::match(['post', 'get'], 'payments/{gateway}/webhook', WebhookController::class)->name('payments.webhook');
