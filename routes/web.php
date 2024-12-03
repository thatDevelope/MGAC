<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;

Route::get('/', function () {
    return view('welcome');
});


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('api/validate/', [AuthController::class, 'validateToken']);
Route::post('api/refresh', [AuthController::class, 'refreshToken']);
Route::post('/api/wallet', [WalletController::class, 'transfer']);
Route::get('/api/balance', [WalletController::class, 'fetchBalance']);
Route::post('/api/order', [WalletController::class, 'initiateOrder']);
Route::post('/api/order/status', [WalletController::class, 'getOrderStatus']);
