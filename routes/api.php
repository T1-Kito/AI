<?php

use App\Http\Controllers\Api\PurchaseApiController;
use App\Http\Controllers\BankWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/bank-webhook', [BankWebhookController::class, 'store']);
Route::post('/v1/bank/callback', [BankWebhookController::class, 'store']);

Route::middleware(['api.key', 'throttle:60,1'])->group(function () {
    Route::get('/balance', [PurchaseApiController::class, 'balance']);
    Route::get('/products', [PurchaseApiController::class, 'products']);
    Route::post('/buy-key', [PurchaseApiController::class, 'buyKey']);
    Route::get('/purchase-history', [PurchaseApiController::class, 'purchaseHistory']);
});
