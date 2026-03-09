<?php

namespace App\Http\Controllers\API;

use \Illuminate\Support\Facades\Route;
use \App\Http\Controllers\Api\Auth\LoginController;
use \App\Http\Controllers\Api\Auth\RegisterController;
use \App\Http\Controllers\Api\CartController;
use \App\Http\Controllers\Api\DashboardController;
use \App\Http\Controllers\Api\OrderController;
use \App\Http\Controllers\Api\ProductController;
use \App\Http\Controllers\Api\ProfileController;
use \App\Http\Controllers\Api\TangkiController;
use \App\Http\Controllers\Api\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::post('/login', [LoginController::class, 'login']);
Route::post('/register', [RegisterController::class, 'register']);
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout']);

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