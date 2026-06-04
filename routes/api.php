<?php

use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\FavoriteController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\SharedRecipeController;
use App\Http\Controllers\API\TangkiController;
use App\Http\Controllers\API\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/products/{id}', [ProductController::class, 'show']);

/*
 * Dashboard can also be viewed by guests.
 */
Route::get('/dashboard', [DashboardController::class, 'index']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);

    Route::controller(CartController::class)->prefix('cart')->group(function () {
        Route::get('/', 'index');
        Route::post('/add', 'add');
        Route::post('/update', 'update');
        Route::post('/remove', 'destroy');
    });

    Route::post('/checkout', [OrderController::class, 'checkout'])->middleware('throttle:10,1');

    Route::controller(ProfileController::class)->prefix('profile')->group(function () {
        Route::get('/', 'edit');
        Route::post('/update', 'update');
        Route::post('/delete', 'destroy');
        Route::post('/password', 'updatePassword');
        Route::get('/notifications', 'notifications');
        Route::post('/notifications/{id}/read', 'markAsRead');
        Route::post('/notifications/delete-read', 'deleteReadNotifications');
        Route::post('/notifications/batch-delete', 'batchDeleteNotifications');
    });

    // Tangki
    Route::controller(TangkiController::class)->prefix('tangki')->group(function () {
        Route::get('/', 'index');
        Route::post('/refill', 'refill')->middleware('throttle:10,1');
    });

    // Transactions
    Route::controller(TransactionController::class)->prefix('transactions')->group(function () {
        Route::get('/', 'index');
        Route::get('/{bill_id}', 'showOrderDetail');
    });

    Route::apiResource('favorites', FavoriteController::class)->middleware('throttle:30,1');

    // Shared recipes
    Route::controller(SharedRecipeController::class)->prefix('recipes')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::post('/{id}/import', 'import');
    });
});
