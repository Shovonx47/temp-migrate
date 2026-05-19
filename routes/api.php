<?php

use App\Http\Controllers\Api\V1\BalanceController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\MessageController;
use App\Http\Controllers\Api\V1\SenderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'api.access'])
    ->prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::get('/me', MeController::class)->name('me');
        Route::get('/balance', BalanceController::class)->name('balance');
        Route::get('/senders', SenderController::class)->name('senders');
        Route::match(['get', 'post'], '/messages', [MessageController::class, 'send'])->name('messages.send');
        Route::get('/batches/{batch}', [MessageController::class, 'showBatch'])
            ->whereNumber('batch')
            ->name('batches.show');
    });
