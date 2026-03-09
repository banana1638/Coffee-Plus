<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Auth\LoginController;
use App\Http\Controllers\API\Auth\RegisterController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\TangkiController;
use App\Http\Controllers\API\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add', [CartController::class, 'add']);
        Route::post('/update', [CartController::class, 'update']);
        Route::post('/remove', [CartController::class, 'destroy']);
    });

    Route::post('/checkout', [OrderController::class, 'checkout']);

    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit']);
        Route::post('/update', [ProfileController::class, 'update']);
        Route::post('/delete', [ProfileController::class, 'destroy']);
    });

    Route::prefix('tangki')->group(function () {
        Route::get('/', [TangkiController::class, 'index']);
        Route::post('/refill', [TangkiController::class, 'refill']);
    });

    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{bill_id}', [TransactionController::class, 'showOrderDetail']);
});