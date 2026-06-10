<?php

use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\CouponAdminController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\ProductAdminController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1')->name('login.post');
});

Route::middleware(['auth:admin'])->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/owner/dashboard', [DashboardController::class, 'ownerDashboard'])
        ->middleware('admin.permission:report.export')
        ->name('owner.dashboard');

    Route::controller(ProductAdminController::class)->prefix('products')->name('products.')->group(function () {
        Route::get('/', 'index')->middleware('admin.permission:product.update')->name('index');
        Route::get('/create', 'create')->middleware('admin.permission:product.create')->name('create');
        Route::post('/', 'store')->middleware('admin.permission:product.create')->name('store');
        Route::get('/{id}/edit', 'edit')->middleware('admin.permission:product.update')->name('edit');
        Route::put('/{id}', 'update')->middleware('admin.permission:product.update')->name('update');
        Route::delete('/{id}', 'destroy')->middleware('admin.permission:product.delete')->name('destroy');
    });

    Route::get('/coupons', [CouponAdminController::class, 'index'])->middleware('admin.permission:coupon.view')->name('coupons.index');
    Route::get('/coupons/create', [CouponAdminController::class, 'create'])->middleware('admin.permission:coupon.create')->name('coupons.create');
    Route::post('/coupons', [CouponAdminController::class, 'store'])->middleware('admin.permission:coupon.create')->name('coupons.store');
    Route::get('/coupons/{coupon}/edit', [CouponAdminController::class, 'edit'])->middleware('admin.permission:coupon.update')->name('coupons.edit');
    Route::put('/coupons/{coupon}', [CouponAdminController::class, 'update'])->middleware('admin.permission:coupon.update')->name('coupons.update');
    Route::delete('/coupons/{coupon}', [CouponAdminController::class, 'destroy'])->middleware('admin.permission:coupon.delete')->name('coupons.destroy');

    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/export-center', [AdminOrderController::class, 'exportPage'])->middleware('admin.permission:report.export')->name('export.page');
        Route::get('/export/download', [AdminOrderController::class, 'export'])->middleware('admin.permission:report.export')->name('export.download');
        Route::get('/refunds', [AdminOrderController::class, 'refunds'])->middleware('admin.permission:order.view')->name('refunds');
        Route::post('/complete-by-code', [AdminOrderController::class, 'completeByPickupCode'])->middleware('admin.permission:order.status.update')->name('complete-by-code');
        Route::get('/', [AdminOrderController::class, 'index'])->middleware('admin.permission:order.view')->name('index');
        Route::get('/{order}', [AdminOrderController::class, 'show'])->middleware('admin.permission:order.view')->name('show');
        Route::match(['post', 'patch'], '/{order}/advance-status', [AdminOrderController::class, 'advanceStatus'])->middleware('admin.permission:order.status.update')->name('advance-status');
        Route::match(['post', 'patch'], '/{order}/complete', [AdminOrderController::class, 'complete'])->middleware('admin.permission:order.status.update')->name('complete');
    });
});
