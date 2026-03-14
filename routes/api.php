<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\TangkiController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\RegisterController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- 公共路由 ---
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/products/{id}', [ProductController::class, 'show']);

/**
 * 优化后的 Dashboard 路由
 * 使用 Sanctum 内置的 guard 尝试获取用户信息，避免手动解析 Token。
 * 如果没有 Token，auth:sanctum 不会报错，只是 auth()->user() 为空。
 */
Route::get('/dashboard', [DashboardController::class, 'index']);

// --- 受保护路由 ---
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout']);

    // 购物车：使用 controller 方法简化
    Route::controller(CartController::class)->prefix('cart')->group(function () {
        Route::get('/', 'index');
        Route::post('/add', 'add');
        Route::post('/update', 'update');
        Route::post('/remove', 'destroy');
    });

    Route::post('/checkout', [OrderController::class, 'checkout']);

    // 个人资料：符合 RESTful 风格的更新/删除
    Route::controller(ProfileController::class)->prefix('profile')->group(function () {
        Route::get('/', 'edit');
        Route::post('/update', 'update');
        Route::post('/delete', 'destroy');
        Route::post('/password', 'updatePassword');
        Route::get('/notifications', 'notifications');
        Route::post('/notifications/{id}/read', 'markAsRead');
    });

    // 储水箱 (Tangki)
    Route::controller(TangkiController::class)->prefix('tangki')->group(function () {
        Route::get('/', 'index');
        Route::post('/refill', 'refill');
    });

    // 交易记录
    Route::controller(TransactionController::class)->prefix('transactions')->group(function () {
        Route::get('/', 'index');
        Route::get('/{bill_id}', 'showOrderDetail');
    });
});