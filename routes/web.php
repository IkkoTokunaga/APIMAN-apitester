<?php

use App\Http\Controllers\ApiProxyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('api')->group(function () {
    Route::post('/proxy',          [ApiProxyController::class, 'send']);
    Route::get('/history',         [ApiProxyController::class, 'history']);
    Route::get('/history/{apiHistory}',    [ApiProxyController::class, 'show']);
    Route::delete('/history/{apiHistory}', [ApiProxyController::class, 'destroy']);
    Route::delete('/history',      [ApiProxyController::class, 'clearAll']);
});
