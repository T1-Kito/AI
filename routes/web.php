<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\ApiDocsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ShopController::class, 'index'])->name('shop.index');
Route::post('/products/{product}/buy', [ShopController::class, 'buy'])->middleware('auth')->name('shop.buy');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

Route::get('/guides/cursor-pro', function () {
    return view('guides.cursor-pro');
})->name('guides.cursor-pro');

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/wallet/topup', [WalletController::class, 'topup'])->name('wallet.topup');
    Route::post('/wallet/topup', [WalletController::class, 'store'])->name('wallet.store');
    Route::get('/wallet/topup/{deposit}/status', [WalletController::class, 'status'])->name('wallet.status');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/api-docs', [ApiDocsController::class, 'show'])->name('api.docs');
    Route::post('/api-docs/rotate', [ApiDocsController::class, 'rotate'])->name('api.rotate');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('products/sync', [ProductController::class, 'sync'])->name('products.sync');
    Route::post('products/markup', [ProductController::class, 'applyMarkup'])->name('products.markup');
    Route::resource('products', ProductController::class)->except('show');
    Route::delete('product-images/{image}', [ProductController::class, 'destroyImage'])->name('product-images.destroy');
    Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::post('finance/users/{user}/balance', [FinanceController::class, 'adjustBalance'])->name('finance.adjust-balance');
    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::post('orders/{order}/retry', [AdminOrderController::class, 'retry'])->name('orders.retry');
    Route::post('orders/{order}/refund', [AdminOrderController::class, 'refund'])->name('orders.refund');
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings/api', [SettingController::class, 'updateApi'])->name('settings.api');
});
